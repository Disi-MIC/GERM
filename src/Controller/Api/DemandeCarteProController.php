<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\DemandeCartePro;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemandeCartePro;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Entity\User;
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
 * Traitement des demandes de carte professionnelle côté frontend Angular,
 * ainsi que la pièce justificative associée. La création simple d'une
 * demande passe par l'opération Post native d'API Platform (aucun effet de
 * bord) ; le traitement reste une action dédiée, en trois étapes désormais :
 *
 *  - transmettre() : le RH Carte Pro vérifie les pièces et transmet au RH
 *    Admin (aucune carte créée à ce stade) ;
 *  - rejeter() : possible par le RH Carte Pro (avant transmission) ou par le
 *    RH Admin (après transmission, filet de sécurité) ;
 *  - approuver() : réservé au RH Admin, uniquement depuis l'état "transmise"
 *    — crée la CarteProfessionnelle ET la valide dans la foulée (cachet/
 *    signature déjà présents), les deux relevant désormais exclusivement de
 *    son rôle.
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

    #[Route('/api/demandes-carte-pro/{id}/transmettre', name: 'api_demande_carte_pro_transmettre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transmettre(DemandeCartePro $demande): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été transmise ou traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $demande->setStatut(StatutDemandeCartePro::TRANSMISE);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-carte-pro/{id}/rejeter', name: 'api_demande_carte_pro_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(DemandeCartePro $demande, Request $request): JsonResponse
    {
        if ($demande->isTransmise()) {
            // Une fois transmise, seul le RH Admin peut encore rejeter (filet
            // de sécurité) — le RH Carte Pro ne peut plus revenir dessus.
            $this->denyAccessUnlessGranted('ROLE_ADMIN_RH');
        } elseif (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $demande->setStatut(StatutDemandeCartePro::REFUSEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setCommentaireTraitement($data['commentaire'] ?? null);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-carte-pro/{id}/approuver', name: 'api_demande_carte_pro_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN_RH')]
    public function approuver(DemandeCartePro $demande, Request $request): JsonResponse
    {
        if (!$demande->isTransmise()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être transmise par le RH Carte Pro avant de pouvoir être approuvée."]], JsonResponse::HTTP_CONFLICT);
        }

        // Pièce justificative obligatoire pour une nouvelle carte (prise de
        // service ou contrat selon que l'agent a un matricule) ou un
        // renouvellement (copie de l'ancienne carte) — la perte/vol reste
        // libre, comme avant.
        if (\in_array($demande->getTypeDemande(), [TypeDemandeCartePro::NOUVELLE, TypeDemandeCartePro::RENOUVELLEMENT], true) && null === $demande->getCheminFichier()) {
            return $this->json(['errors' => ['fichier' => 'Merci de joindre la pièce justificative avant d\'approuver cette demande.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $numero = trim((string) ($data['numero'] ?? ''));
        $dateDelivrance = isset($data['dateDelivrance'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDelivrance']) ?: null
            : null;

        if ('' === $numero || null === $dateDelivrance) {
            return $this->json(['errors' => ['numero' => 'Merci de renseigner un numéro et une date de délivrance valides pour approuver.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $validateur */
        $validateur = $this->getUser();

        $nouvelleCarte = new CarteProfessionnelle();
        $nouvelleCarte->setPersonnel($demande->getPersonnel());
        $nouvelleCarte->setNumero($numero);
        $nouvelleCarte->setDateDelivrance($dateDelivrance);
        $nouvelleCarte->setStatut(StatutCarteProfessionnelle::VALIDE);
        $nouvelleCarte->valider($validateur);
        $this->pdfStockage->genererEtStocker($nouvelleCarte);
        $this->em->persist($nouvelleCarte);

        $demande->setCarteCreee($nouvelleCarte);
        $demande->setStatut(StatutDemandeCartePro::APPROUVEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setCommentaireTraitement($data['commentaire'] ?? null);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
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
