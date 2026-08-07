<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\DemandeCartePro;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemande;
use App\Service\CarteProfessionnellePdfStockageService;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Traitement des demandes de carte professionnelle côté frontend Angular :
 * approuver (crée la CarteProfessionnelle correspondante) ou refuser, ainsi
 * que la pièce justificative associée. La création simple d'une demande passe
 * par l'opération Post native d'API Platform (aucun effet de bord) ; seul le
 * traitement, qui crée une carte, reste une action dédiée — même logique que
 * DemandeCarteProController::traiter() côté Twig.
 */
#[IsGranted('ROLE_RH_CARTE_PRO')]
class DemandeCarteProController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CarteProfessionnellePdfStockageService $pdfStockage,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/demandes-carte-pro/{id}/traiter', name: 'api_demande_carte_pro_traiter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeCartePro $demande, Request $request): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $decision = $data['decision'] ?? null;
        $commentaire = $data['commentaire'] ?? null;

        if ('approuver' === $decision) {
            $numero = trim((string) ($data['numero'] ?? ''));
            $dateDelivrance = isset($data['dateDelivrance'])
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDelivrance']) ?: null
                : null;

            if ('' === $numero || null === $dateDelivrance) {
                return $this->json(['errors' => ['numero' => 'Merci de renseigner un numéro et une date de délivrance valides pour approuver.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $nouvelleCarte = new CarteProfessionnelle();
            $nouvelleCarte->setPersonnel($demande->getPersonnel());
            $nouvelleCarte->setNumero($numero);
            $nouvelleCarte->setDateDelivrance($dateDelivrance);
            $nouvelleCarte->setStatut(StatutCarteProfessionnelle::VALIDE);
            $this->pdfStockage->genererEtStocker($nouvelleCarte);
            $this->em->persist($nouvelleCarte);

            $demande->setCarteCreee($nouvelleCarte);
            $demande->setStatut(StatutDemande::APPROUVEE);
            $demande->setDateTraitement(new \DateTimeImmutable());
            $demande->setCommentaireTraitement($commentaire);
            $this->em->flush();

            return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
        }

        if ('refuser' === $decision) {
            $demande->setStatut(StatutDemande::REFUSEE);
            $demande->setDateTraitement(new \DateTimeImmutable());
            $demande->setCommentaireTraitement($commentaire);
            $this->em->flush();

            return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
        }

        return $this->json(['errors' => ['decision' => 'Décision invalide : "approuver" ou "refuser" attendu.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/api/demandes-carte-pro/{id}/piece', name: 'api_demande_carte_pro_piece_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadPiece(DemandeCartePro $demande, Request $request): JsonResponse
    {
        $file = $request->files->get('fichier');

        if (!$file instanceof UploadedFile) {
            return $this->json(['errors' => ['fichier' => 'Aucun fichier reçu.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($demande->getCheminFichier()) {
            $this->fileStorage->delete($demande->getCheminFichier());
        }

        $stocke = $this->fileStorage->store($file, 'demande-carte-pro');
        $demande->setCheminFichier($stocke['path']);
        $demande->setNomOriginal($stocke['originalName']);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-carte-pro/{id}/piece', name: 'api_demande_carte_pro_piece_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPiece(DemandeCartePro $demande): StreamedResponse
    {
        if (!$demande->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($demande) {
            fpassthru($this->fileStorage->readStream($demande->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($demande->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $demande->getNomOriginal() ?? 'piece-justificative'));

        return $response;
    }
}
