<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Conge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les congés côté frontend Angular. La ressource Conge est
 * exposée en lecture seule via API Platform (voir l'entité) : toute
 * création/modification/suppression passe par ces actions dédiées.
 */
#[IsGranted('ROLE_RH_CONGE')]
class CongeController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/conges', name: 'api_conge_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $conge = $this->serializer->deserialize($request->getContent(), Conge::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($conge);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($conge);
        $this->em->flush();

        return $this->json($conge, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/conges/{id}', name: 'api_conge_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Conge $conge, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), Conge::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $conge,
        ]);

        $violations = $this->validator->validate($conge);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($conge, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/conges/{id}', name: 'api_conge_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Conge $conge): JsonResponse
    {
        $this->em->remove($conge);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
