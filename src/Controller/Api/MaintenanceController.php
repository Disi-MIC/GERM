<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\ActionAudit;
use App\Entity\Maintenance;
use App\Repository\ListeValeurRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\TicketIncidentRepository;
use App\Service\AuditLogService;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal de maintenance côté frontend Angular. La ressource
 * Maintenance est exposée en lecture seule via API Platform (journal, pas
 * d'édition — même logique que HistoriqueAffectation) : la création et la
 * suppression passent par ces actions dédiées.
 *
 * create() reçoit un envoi multipart (métadonnées + pièce justificative
 * facultative) plutôt qu'un JSON classique depuis l'ajout de cette pièce —
 * la désérialisation JSON via IRI (App Platform) ne peut pas cohabiter avec
 * un fichier dans la même requête ; chaque référence (matériel, type...) est
 * donc résolue manuellement ici, comme DocumentAdministratifController.
 */
#[IsGranted('ROLE_IT_STOCK')]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly MaterielInformatiqueRepository $materielRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly PersonnelRepository $personnelRepository,
        private readonly TicketIncidentRepository $ticketIncidentRepository,
    ) {
    }

    #[Route('/api/maintenances', name: 'api_maintenance_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $materiel = $this->materielRepository->find((int) $request->request->get('materiel'));
        if (!$materiel) {
            return $this->json(['errors' => ['materiel' => 'Le matériel concerné est obligatoire.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $this->listeValeurRepository->find((int) $request->request->get('type'));
        if (!$type) {
            return $this->json(['errors' => ['type' => 'Le type de maintenance est obligatoire.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $realiseParId = $request->request->get('realisePar');
        $realisePar = $realiseParId ? $this->personnelRepository->find((int) $realiseParId) : null;

        $ticketOrigineId = $request->request->get('ticketOrigine');
        $ticketOrigine = $ticketOrigineId ? $this->ticketIncidentRepository->find((int) $ticketOrigineId) : null;

        $fichier = $request->files->get('fichier');
        if ($fichier && ($erreur = $this->fileStorage->erreurValidation($fichier))) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maintenance = new Maintenance();
        $maintenance->setMateriel($materiel);
        $maintenance->setType($type);
        $maintenance->setDescription((string) $request->request->get('description'));
        $maintenance->setDateRealisation($this->parseDate($request->request->get('dateRealisation')));
        $maintenance->setRealisePar($realisePar);
        $maintenance->setPrestataireExterne($request->request->get('prestataireExterne') ?: null);
        $maintenance->setTicketOrigine($ticketOrigine);
        $maintenance->setObservations($request->request->get('observations') ?: null);

        $violations = $this->validator->validate($maintenance);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        if ($fichier) {
            $stocke = $this->fileStorage->store($fichier, 'maintenance');
            $maintenance->setCheminFichier($stocke['path']);
            $maintenance->setNomOriginal($stocke['originalName']);
        }

        $this->em->persist($maintenance);
        $this->em->flush();

        return $this->json($maintenance, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/maintenances/{id}/piece', name: 'api_maintenance_piece', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function piece(Maintenance $maintenance, AuditLogService $auditLog): StreamedResponse
    {
        if (!$maintenance->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $auditLog->enregistrer(ActionAudit::DOCUMENT_TELECHARGE, entite: 'Maintenance', entiteId: $maintenance->getId());

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Piece justificative maintenance - %s', $maintenance->getMateriel()?->getNumeroInventaire() ?? ''),
            pathinfo($maintenance->getCheminFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($maintenance) {
            fpassthru($this->fileStorage->readStream($maintenance->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($maintenance->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    #[Route('/api/maintenances/{id}', name: 'api_maintenance_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Maintenance $maintenance): JsonResponse
    {
        if ($maintenance->getCheminFichier()) {
            $this->fileStorage->delete($maintenance->getCheminFichier());
        }

        $this->em->remove($maintenance);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function parseDate(?string $date): ?\DateTimeImmutable
    {
        if (!$date) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: null;
    }
}
