<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\BonEssence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal des bons d'essence côté frontend Angular. Comme
 * MaintenanceController/HistoriqueVidangeController : la ressource
 * BonEssence est exposée en lecture seule via API Platform (journal, pas
 * d'édition), la création et la suppression passent par ces actions dédiées.
 */
#[IsGranted('ROLE_SUPERADMIN')]
class BonEssenceController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/bons-essence', name: 'api_bon_essence_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $bon = $this->serializer->deserialize($request->getContent(), BonEssence::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($bon);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($bon);
        $this->em->flush();

        return $this->json($bon, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/bons-essence/{id}', name: 'api_bon_essence_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(BonEssence $bon): JsonResponse
    {
        $this->em->remove($bon);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
