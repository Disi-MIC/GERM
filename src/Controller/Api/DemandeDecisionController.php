<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\StatutDemande;
use App\Entity\Personnel;
use App\Entity\PieceJustificativeDecision;
use App\Entity\User;
use App\Repository\DecisionCongeRepository;
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
 * séparés, comme pour DemandeCartePro), le traitement en cinq étapes — voir
 * le commentaire de classe de DemandeDecision pour le circuit complet :
 *
 *  - valider() : RH Congé, vérifie les pièces, autorise l'agent à déposer
 *    physiquement au service courrier ;
 *  - rejeter() : RH Congé, motif prédéterminé obligatoire (pièces
 *    incomplètes) ;
 *  - (confirmation du dépôt courrier par l'agent lui-même — voir
 *    Api/MeDemandesController::confirmerDepotCourrierDecision()) ;
 *  - retour() : RH Admin, dépose les documents scannés reçus en retour du
 *    circuit ministériel ;
 *  - genererEtTransmettre() : RH Congé, calcule le nombre de jours, crée la
 *    DecisionConge et transmet à l'agent (physiquement et dans l'application).
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
        private readonly DecisionCongeRepository $decisionCongeRepository,
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

        if ($erreur = $this->erreurDecisionEnCours($demande->getPersonnel(), 'Cet agent dispose déjà')) {
            return $erreur;
        }

        $this->em->persist($demande);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    /**
     * Une demande de décision n'a de sens que si l'agent n'a plus de congé
     * annuel disponible : tant qu'une DecisionConge valide (non expirée)
     * existe déjà pour lui, inutile — et source de doublons pour le RH Congé
     * — d'en déposer une nouvelle. Utilisé à la création (create() côté RH,
     * MeDemandesController::creerDemandeDecision() côté agent) ainsi que par
     * decisionValide(), qui permet au formulaire RH de vérifier par avance,
     * dès la sélection de l'agent, avant même la soumission.
     */
    private function erreurDecisionEnCours(?Personnel $personnel, string $sujet): ?JsonResponse
    {
        if (!$personnel) {
            return null;
        }

        $decisionsValides = $this->decisionCongeRepository->findValides($personnel);
        if (!$decisionsValides) {
            return null;
        }

        $decision = $decisionsValides[0];

        return $this->json(['errors' => ['decision' => \sprintf(
            "%s d'une décision de congé valide (%s), jusqu'au %s. Une nouvelle demande ne peut être déposée qu'après son expiration.",
            $sujet,
            $decision->getNumeroDecision(),
            $decision->getDateExpiration()?->format('d/m/Y'),
        )]], JsonResponse::HTTP_CONFLICT);
    }

    /**
     * Permet au formulaire RH de vérifier, dès la sélection de l'agent et
     * avant toute soumission, si celui-ci dispose déjà d'une décision de
     * congé valide — voir erreurDecisionEnCours().
     */
    #[Route('/api/personnels/{id}/decision-valide', name: 'api_personnel_decision_valide', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function decisionValide(Personnel $personnel): JsonResponse
    {
        $decisions = $this->decisionCongeRepository->findValides($personnel);

        return $this->json($decisions[0] ?? null, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
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

    #[Route('/api/demandes-decision/{id}/valider', name: 'api_demande_decision_valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
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

        $demande->setStatut(StatutDemande::VALIDEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre demande de décision de congé est validée',
            '/mon-espace/conges',
            'Pièces vérifiées : vous pouvez déposer votre dossier au service courrier, puis confirmer le dépôt dans l\'application.',
        );
        $this->notificationService->notifierRole(
            User::ROLE_ADMIN_RH,
            'Décision de congé validée par le RH Congé',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a validé la demande de %s : pièces complètes, en attente du dépôt au service courrier.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/rejeter', name: 'api_demande_decision_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isEnAttente()) {
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
        $this->notificationService->notifierRole(
            User::ROLE_ADMIN_RH,
            'Décision de congé rejetée par le RH Congé',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a rejeté la demande de %s (%s).', $demande->getPersonnel()?->getNomComplet(), $motif->getLibelle()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * RH Admin uniquement : le circuit ministériel (courrier → SG → Ministre →
     * DAGE) se déroule entièrement hors application, entre le dépôt courrier
     * de l'agent et cette étape — voir le commentaire de classe de
     * DemandeDecision. Dépose les documents scannés du retour et rend la
     * demande visible au RH Congé pour traitement final.
     */
    #[Route('/api/demandes-decision/{id}/retour', name: 'api_demande_decision_retour', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN_RH')]
    public function retour(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isDeposeeCourrier()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être déposée au service courrier par l'agent."]], JsonResponse::HTTP_CONFLICT);
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stocke = $this->fileStorage->store($file, 'decision');
        $demande->setCheminDocumentRetour($stocke['path']);
        $demande->setNomOriginalDocumentRetour($stocke['originalName']);
        $demande->setStatut(StatutDemande::RETOURNEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CONGE,
            'Décision de congé revenue du circuit, à finaliser',
            '/conges/demandes-decision',
            \sprintf('Le RH Admin a déposé les documents de retour pour %s : calculez le nombre de jours et transmettez à l\'agent.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/document-retour', name: 'api_demande_decision_document_retour_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadDocumentRetour(DemandeDecision $demande): StreamedResponse
    {
        if (!$demande->getCheminDocumentRetour()) {
            throw $this->createNotFoundException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Retour circuit decision - %s', $demande->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($demande->getCheminDocumentRetour(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($demande) {
            fpassthru($this->fileStorage->readStream($demande->getCheminDocumentRetour()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($demande->getCheminDocumentRetour()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    /**
     * Dernière étape, RH Congé : calcule (ou reçoit du client, déjà calculé
     * et éventuellement ajusté — voir le composant Angular) le nombre de
     * jours, crée la DecisionConge et clôt la demande. Un seul appel plutôt
     * qu'un "générer" puis "transmettre" séparés — même simplification que
     * les autres étapes de ce circuit (une vérification/action humaine =
     * un clic).
     */
    #[Route('/api/demandes-decision/{id}/generer-et-transmettre', name: 'api_demande_decision_generer_et_transmettre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function genererEtTransmettre(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isRetournee()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord revenir du circuit papier (RH Admin) avant de pouvoir être générée."]], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $numero = trim((string) ($data['numero'] ?? ''));
        $dateDecision = isset($data['dateDecision'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDecision']) ?: null
            : null;
        $dateExpiration = isset($data['dateExpiration'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateExpiration']) ?: null
            : null;
        $nombreJours = isset($data['nombreJours']) ? (int) round((float) $data['nombreJours']) : null;
        $dateDerniereDecision = isset($data['dateDerniereDecision'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDerniereDecision']) ?: null
            : null;

        if ('' === $numero || null === $dateDecision || null === $dateExpiration || $dateExpiration <= $dateDecision || null === $nombreJours || $nombreJours <= 0) {
            return $this->json(['errors' => ['numero' => "Merci de renseigner un numéro, un nombre de jours et des dates valides (date d'expiration postérieure à la date d'octroi) pour générer la décision."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($nombreJours > 90) {
            return $this->json(['errors' => ['nombreJours' => 'Le nombre de jours de congé ne peut pas dépasser 90 jours.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($dateDerniereDecision) {
            $demande->setDateDerniereDecision($dateDerniereDecision);
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
        $this->em->persist($nouvelleDecision);

        $demande->setDecisionCreee($nouvelleDecision);
        $demande->setStatut(StatutDemande::TRANSMISE_AGENT);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre décision de congé est disponible',
            '/mon-espace/conges',
            \sprintf('Votre décision de congé (%d jours) vous a été remise.', $nombreJours),
        );
        $this->notificationService->notifierRole(
            User::ROLE_ADMIN_RH,
            'Décision de congé générée et transmise',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a généré la décision de %s (%d jours) et l\'a transmise à l\'agent.', $demande->getPersonnel()?->getNomComplet(), $nombreJours),
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
