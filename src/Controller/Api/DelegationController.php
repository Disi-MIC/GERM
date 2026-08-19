<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Delegation;
use App\Entity\Enum\StatutDelegation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les délégations côté frontend Angular. La ressource
 * Delegation est exposée en lecture seule via API Platform : `delegant`
 * n'est jamais dans le groupe api:write (voir l'entité), il est toujours
 * forcé côté serveur à l'utilisateur connecté — jamais depuis le payload,
 * même garde-fou anti-élévation de privilège que DelegationController côté
 * Twig.
 */
#[IsGranted('ROLE_RH_RESPONSABLE')]
class DelegationController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/delegations', name: 'api_delegation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $delegation = $this->serializer->deserialize($request->getContent(), Delegation::class, 'json', ['groups' => ['api:write']]);

        /** @var User $delegant */
        $delegant = $this->getUser();
        $delegation->setDelegant($delegant);

        $violations = $this->validator->validate($delegation);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($delegation);
        $this->em->flush();

        return $this->json($delegation, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/delegations/{id}/revoke', name: 'api_delegation_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(Delegation $delegation): JsonResponse
    {
        $delegation->setStatut(StatutDelegation::REVOQUEE);
        $this->em->flush();

        return $this->json($delegation, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }
}
