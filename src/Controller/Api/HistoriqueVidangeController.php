<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\HistoriqueVidange;
use App\Entity\Vehicule;
use App\Repository\HistoriqueVidangeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal de vidanges côté frontend Angular. Comme
 * MaintenanceController : la ressource HistoriqueVidange est exposée en
 * lecture seule via API Platform (journal, pas d'édition), la création et la
 * suppression passent par ces actions dédiées — qui maintiennent au passage
 * les champs dénormalisés Vehicule::$derniereVidangeKm/Date (voir
 * synchroniserDerniereVidange()).
 */
#[IsGranted('ROLE_SUPERADMIN')]
class HistoriqueVidangeController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly HistoriqueVidangeRepository $historiqueVidangeRepository,
    ) {
    }

    #[Route('/api/historique-vidanges', name: 'api_historique_vidange_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $vidange = $this->serializer->deserialize($request->getContent(), HistoriqueVidange::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($vidange);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($vidange);
        $this->em->flush();

        $this->synchroniserDerniereVidange($vidange->getVehicule());

        return $this->json($vidange, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/historique-vidanges/{id}', name: 'api_historique_vidange_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(HistoriqueVidange $vidange): JsonResponse
    {
        $vehicule = $vidange->getVehicule();

        $this->em->remove($vidange);
        $this->em->flush();

        $this->synchroniserDerniereVidange($vehicule);

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Recalcule Vehicule::$derniereVidangeKm/Date à partir de la vidange la
     * plus récente encore journalisée — appelé après chaque création/
     * suppression plutôt que de faire confiance à la seule entrée qui vient
     * de bouger : une suppression peut retirer l'entrée la plus récente et
     * doit alors retomber sur la précédente (ou null s'il n'en reste
     * aucune).
     */
    private function synchroniserDerniereVidange(?Vehicule $vehicule): void
    {
        if (!$vehicule) {
            return;
        }

        $derniere = $this->historiqueVidangeRepository->findDerniereVidange($vehicule);
        $vehicule->setDerniereVidangeKm($derniere?->getKilometrage());
        $vehicule->setDerniereVidangeDate($derniere?->getDate());
        $this->em->flush();
    }
}
