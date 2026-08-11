<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Maintenance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal de maintenance côté frontend Angular. La ressource
 * Maintenance est exposée en lecture seule via API Platform (journal, pas
 * d'édition — même logique que HistoriqueAffectation) : la création et la
 * suppression passent par ces actions dédiées.
 */
#[IsGranted('ROLE_IT_TECHNICIEN')]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/maintenances', name: 'api_maintenance_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $maintenance = $this->serializer->deserialize($request->getContent(), Maintenance::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($maintenance);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($maintenance);
        $this->em->flush();

        return $this->json($maintenance, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/maintenances/{id}', name: 'api_maintenance_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Maintenance $maintenance): JsonResponse
    {
        $this->em->remove($maintenance);
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
