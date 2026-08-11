<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\StatutDemande;
use App\Entity\PieceJustificativeDecision;
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
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les demandes de décision de congé côté frontend Angular. La
 * ressource DemandeDecision est exposée en lecture seule via API Platform :
 * la création simple passe par une action dédiée (pièces jointes en appels
 * séparés, comme pour DemandeCartePro), le traitement (approuver crée la
 * DecisionConge correspondante / refuser) et la suppression (bloquée si déjà
 * traitée) aussi — même logique que DemandeDecisionController côté Twig.
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
        $response = new StreamedResponse(function () use ($piece) {
            fpassthru($this->fileStorage->readStream($piece->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($piece->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $piece->getNomOriginal()));

        return $response;
    }

    #[Route('/api/demandes-decision/{id}/traiter', name: 'api_demande_decision_traiter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $decision = $data['decision'] ?? null;
        $commentaire = $data['commentaire'] ?? null;

        if ('approuver' === $decision) {
            $numero = trim((string) ($data['numero_decision'] ?? ''));
            $dateDecision = isset($data['date_decision'])
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['date_decision']) ?: null
                : null;
            $dateExpiration = isset($data['date_expiration'])
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['date_expiration']) ?: null
                : null;

            if ('' === $numero || null === $dateDecision || null === $dateExpiration || $dateExpiration <= $dateDecision) {
                return $this->json(['errors' => ['numero_decision' => "Merci de renseigner un numéro et des dates valides (date d'expiration postérieure à la date d'octroi) pour approuver."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $nouvelleDecision = new DecisionConge();
            $nouvelleDecision->setPersonnel($demande->getPersonnel());
            $nouvelleDecision->setNumeroDecision($numero);
            $nouvelleDecision->setDateDecision($dateDecision);
            $nouvelleDecision->setDateExpiration($dateExpiration);
            $this->em->persist($nouvelleDecision);

            $demande->setDecisionCreee($nouvelleDecision);
            $demande->setStatut(StatutDemande::APPROUVEE);
            $demande->setDateTraitement(new \DateTimeImmutable());
            $demande->setCommentaireTraitement($commentaire);
            $this->em->flush();

            $this->notificationService->notifier(
                $demande->getPersonnel()?->getUser(),
                'Votre demande de décision de congé a été approuvée',
                '/mon-espace/conges',
                $commentaire,
            );

            return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
        }

        if ('refuser' === $decision) {
            $demande->setStatut(StatutDemande::REFUSEE);
            $demande->setDateTraitement(new \DateTimeImmutable());
            $demande->setCommentaireTraitement($commentaire);
            $this->em->flush();

            $this->notificationService->notifier(
                $demande->getPersonnel()?->getUser(),
                'Votre demande de décision de congé a été refusée',
                '/mon-espace/conges',
                $commentaire,
            );

            return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
        }

        return $this->json(['errors' => ['decision' => 'Décision invalide : "approuver" ou "refuser" attendu.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
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

    private function violationsResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
