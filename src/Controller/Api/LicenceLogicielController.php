<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\LicenceLogiciel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le registre des licences logicielles côté frontend Angular.
 * La ressource LicenceLogiciel est exposée en lecture seule via API Platform
 * (journal, pas d'édition — même logique que Maintenance) : la création et
 * la suppression passent par ces actions dédiées.
 */
#[IsGranted('ROLE_IT_STOCK')]
class LicenceLogicielController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/licences-logicielles', name: 'api_licence_logiciel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $licence = $this->serializer->deserialize($request->getContent(), LicenceLogiciel::class, 'json', ['groups' => ['api:write']]);

        // dateExpiration n'est jamais acceptée du client (pas de groupe
        // api:write dessus, voir l'entité) : calculée ici à partir de
        // dateDebut + dureeMois, pour éviter toute incohérence entre les deux.
        if ($licence->getDateDebut() && $licence->getDureeMois()) {
            $licence->setDateExpiration($licence->getDateDebut()->modify(\sprintf('+%d months', $licence->getDureeMois())));
        }

        $violations = $this->validator->validate($licence);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($licence);
        $this->em->flush();

        return $this->json($licence, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/licences-logicielles/{id}', name: 'api_licence_logiciel_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(LicenceLogiciel $licence): JsonResponse
    {
        $this->em->remove($licence);
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
