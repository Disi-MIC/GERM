<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\ChangementCartouche;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal des changements de cartouche côté frontend Angular.
 * La ressource ChangementCartouche est exposée en lecture seule via API
 * Platform (journal, pas d'édition — même logique que Maintenance) : la
 * création et la suppression passent par ces actions dédiées.
 */
#[IsGranted('ROLE_IT_STOCK')]
class ChangementCartoucheController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/changements-cartouche', name: 'api_changement_cartouche_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $changement = $this->serializer->deserialize($request->getContent(), ChangementCartouche::class, 'json', ['groups' => ['api:write']]);

        /** @var User $operateur */
        $operateur = $this->getUser();
        $changement->setEnregistrePar($operateur);

        $violations = $this->validator->validate($changement);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($changement);
        $this->em->flush();

        return $this->json($changement, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/changements-cartouche/{id}', name: 'api_changement_cartouche_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(ChangementCartouche $changement): JsonResponse
    {
        $this->em->remove($changement);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
