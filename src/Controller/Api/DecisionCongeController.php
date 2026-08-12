<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les décisions de congé côté frontend Angular. La ressource
 * DecisionConge est exposée en lecture seule via API Platform : toute
 * création/modification/suppression passe par ces actions dédiées. La
 * suppression est bloquée si des demandes de jouissance s'appuient encore
 * sur la décision — même garde-fou que DecisionCongeController::delete()
 * côté Twig.
 */
#[IsGranted('ROLE_RH_CONGE')]
class DecisionCongeController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/decisions-conge', name: 'api_decision_conge_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $decision = $this->serializer->deserialize($request->getContent(), DecisionConge::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($decision);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($decision);
        $this->em->flush();

        return $this->json($decision, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/decisions-conge/{id}', name: 'api_decision_conge_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(DecisionConge $decision, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), DecisionConge::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $decision,
        ]);

        $violations = $this->validator->validate($decision);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($decision, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/decisions-conge/{id}', name: 'api_decision_conge_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(DecisionConge $decision): JsonResponse
    {
        if (!$decision->getDemandesJouissance()->isEmpty()) {
            return $this->json(['errors' => ['demandesJouissance' => 'Impossible de supprimer cette décision : des demandes de jouissance s\'appuient encore sur elle.']], JsonResponse::HTTP_CONFLICT);
        }

        $this->em->remove($decision);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
