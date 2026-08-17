<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\StatutDemande;
use App\Entity\PieceJustificativeDecision;
use App\Entity\User;
use App\Repository\ListeValeurRepository;
use App\Service\FileStorage;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les demandes de décision de congé côté frontend Angular. La
 * ressource DemandeDecision est exposée en lecture seule via API Platform :
 * la création simple passe par une action dédiée (pièces jointes en appels
 * séparés, comme pour DemandeCartePro), le traitement en quatre étapes :
 *
 *  - transmettre() : le RH Congé vérifie que les pièces attendues sont
 *    présentes (voir DemandeDecision::$nouvellementAffecte — une pièce si
 *    nouvellement affecté, deux sinon), génère la DecisionConge (numéro,
 *    dates, nombre de jours) et transmet au RH Admin ;
 *  - rejeter() : possible par le RH Congé (avant transmission, pièces
 *    incomplètes) ou par le RH Admin (après transmission, filet de
 *    sécurité) — motif obligatoire, choisi dans une liste prédéterminée ;
 *  - approuver() : réservé au RH Admin, uniquement depuis l'état "transmise"
 *    — valide la DecisionConge déjà créée par transmettre(), n'en crée pas
 *    de nouvelle (voir DecisionConge::valider()) ; déclenche hors application
 *    l'impression, le passage au service courrier et la signature de
 *    l'autorité ;
 *  - confirmerRetour() : réservé au RH Admin, uniquement depuis l'état
 *    "approuvee" — le papier signé est revenu, vérifié, et transmis au RH
 *    Congé ;
 *  - transmettreAgent() : réservé au RH Congé, uniquement depuis l'état
 *    "retournee" — confirme la remise physique et électronique à l'agent.
 */
#[IsGranted('ROLE_RH_CONGE')]
class DemandeDecisionController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly NotificationService $notificationService,
        private readonly ListeValeurRepository $listeValeurRepository,
    ) {
    }

    #[Route('/api/demandes-decision', name: 'api_demande_decision_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $demande = $this->serializer->deserialize($request->getContent(), DemandeDecision::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($demande);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($demande);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/piece1', name: 'api_demande_decision_piece1', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function piece1(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPiece($demande, $request);
    }

    #[Route('/api/demandes-decision/{id}/piece2', name: 'api_demande_decision_piece2', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function piece2(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPiece($demande, $request);
    }

    private function attacherPiece(DemandeDecision $demande, Request $request): JsonResponse
    {
        $file = $request->files->get('fichier');

        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stocke = $this->fileStorage->store($file, 'decision');
        $piece = new PieceJustificativeDecision();
        $piece->setDemande($demande);
        $piece->setCheminFichier($stocke['path']);
        $piece->setNomOriginal($stocke['originalName']);
        $this->em->persist($piece);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/pieces-decision/{id}', name: 'api_piece_decision_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPiece(PieceJustificativeDecision $piece): StreamedResponse
    {
        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Piece justificative decision - %s', $piece->getDemande()?->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($piece->getCheminFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($piece) {
            fpassthru($this->fileStorage->readStream($piece->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($piece->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    #[Route('/api/demandes-decision/{id}/transmettre', name: 'api_demande_decision_transmettre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transmettre(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été transmise ou traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $piecesAttendues = $demande->isNouvellementAffecte() ? 1 : 2;
        if ($demande->getPieces()->count() < $piecesAttendues) {
            return $this->json(['errors' => ['pieces' => \sprintf(
                'Pièces incomplètes : %d attendue(s) (%s), %d fournie(s).',
                $piecesAttendues,
                $demande->isNouvellementAffecte() ? 'prise de service' : 'prise de service + ancienne décision',
                $demande->getPieces()->count(),
            )]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $numero = trim((string) ($data['numero'] ?? ''));
        $dateDecision = isset($data['dateDecision'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDecision']) ?: null
            : null;
        $dateExpiration = isset($data['dateExpiration'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateExpiration']) ?: null
            : null;
        $nombreJours = isset($data['nombreJours']) ? (int) $data['nombreJours'] : null;

        if ('' === $numero || null === $dateDecision || null === $dateExpiration || $dateExpiration <= $dateDecision || null === $nombreJours || $nombreJours <= 0) {
            return $this->json(['errors' => ['numero' => "Merci de renseigner un numéro, un nombre de jours et des dates valides (date d'expiration postérieure à la date d'octroi) pour transmettre."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $operateur */
        $operateur = $this->getUser();

        $nouvelleDecision = new DecisionConge();
        $nouvelleDecision->setPersonnel($demande->getPersonnel());
        $nouvelleDecision->setNumeroDecision($numero);
        $nouvelleDecision->setDateDecision($dateDecision);
        $nouvelleDecision->setDateExpiration($dateExpiration);
        $nouvelleDecision->setNombreJours($nombreJours);
        $nouvelleDecision->setGenereePar($operateur);
        $nouvelleDecision->marquerEnAttenteValidationAdminRh();
        $this->em->persist($nouvelleDecision);

        $demande->setDecisionCreee($nouvelleDecision);
        $demande->setStatut(StatutDemande::TRANSMISE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_ADMIN_RH,
            'Décision de congé à valider',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a transmis la décision de %s pour validation.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/rejeter', name: 'api_demande_decision_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(DemandeDecision $demande, Request $request): JsonResponse
    {
        if ($demande->isTransmise()) {
            // Une fois transmise, seul le RH Admin peut encore rejeter (filet
            // de sécurité) — le RH Congé ne peut plus revenir dessus.
            $this->denyAccessUnlessGranted('ROLE_ADMIN_RH');
        } elseif (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $motif = !empty($data['motifRejet']) ? $this->listeValeurRepository->find($data['motifRejet']) : null;
        if (!$motif || CategorieListeValeur::MOTIF_REJET_DECISION_CONGE !== $motif->getCategorie()) {
            return $this->json(['errors' => ['motifRejet' => 'Merci de sélectionner un motif de rejet.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $demande->setStatut(StatutDemande::REFUSEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setMotifRejet($motif);
        $demande->setCommentaireTraitement($data['commentaire'] ?? null);
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre demande de décision de congé a été refusée',
            '/mon-espace/conges',
            $motif->getLibelle().($demande->getCommentaireTraitement() ? ' — '.$demande->getCommentaireTraitement() : ''),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/approuver', name: 'api_demande_decision_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN_RH')]
    public function approuver(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isTransmise()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être transmise par le RH Congé avant de pouvoir être approuvée."]], JsonResponse::HTTP_CONFLICT);
        }

        /** @var User $validateur */
        $validateur = $this->getUser();
        $demande->getDecisionCreee()?->valider($validateur);

        $demande->setStatut(StatutDemande::APPROUVEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        // Pas de notification ici : l'approbation ne fait que déclencher le
        // circuit papier hors application (impression, courrier, signature) —
        // personne n'a d'action à faire dans l'app avant que le papier signé
        // ne revienne (voir confirmerRetour()).

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/confirmer-retour', name: 'api_demande_decision_confirmer_retour', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN_RH')]
    public function confirmerRetour(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isApprouvee()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être approuvée avant de confirmer le retour du circuit papier."]], JsonResponse::HTTP_CONFLICT);
        }

        $demande->setStatut(StatutDemande::RETOURNEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CONGE,
            'Décision de congé signée, à remettre à l\'agent',
            '/conges/demandes-decision',
            \sprintf('Le RH Admin a vérifié et transmis la décision signée de %s : à remettre à l\'agent.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/transmettre-agent', name: 'api_demande_decision_transmettre_agent', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transmettreAgent(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isRetournee()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être retournée du circuit papier avant d'être transmise à l'agent."]], JsonResponse::HTTP_CONFLICT);
        }

        $demande->setStatut(StatutDemande::TRANSMISE_AGENT);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre décision de congé est disponible',
            '/mon-espace/conges',
            'Votre décision de congé vous a été remise.',
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}', name: 'api_demande_decision_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Impossible de supprimer une demande déjà traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        foreach ($demande->getPieces() as $piece) {
            $this->fileStorage->delete($piece->getCheminFichier());
        }

        $this->em->remove($demande);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
