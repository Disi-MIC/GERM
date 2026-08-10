<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\HistoriqueAffectation;
use App\Repository\HistoriqueAffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le journal de carrière (HistoriqueAffectation) côté
 * frontend Angular. La ressource est exposée en lecture seule via API
 * Platform (journal append-only, pas d'édition — même en Twig) : la
 * création synchronise Personnel.service/fonction/grade depuis le mouvement
 * le plus récent, et la suppression est bloquée pour le mouvement courant —
 * même logique que CarriereController côté Twig.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class HistoriqueAffectationController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly HistoriqueAffectationRepository $historiqueRepository,
    ) {
    }

    #[Route('/api/historique-affectations', name: 'api_historique_affectation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $mouvement = $this->serializer->deserialize($request->getContent(), HistoriqueAffectation::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($mouvement);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($mouvement);
        $this->em->flush();

        $personnel = $mouvement->getPersonnel();
        $courant = $this->historiqueRepository->findCurrentForPersonnel($personnel);
        if ($courant) {
            $personnel->setService($courant->getService());
            $personnel->setFonction($courant->getFonction());
            $personnel->setGrade($courant->getGrade());
            $this->em->flush();
        }

        return $this->json($mouvement, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/historique-affectations/{id}', name: 'api_historique_affectation_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(HistoriqueAffectation $mouvement): JsonResponse
    {
        $personnel = $mouvement->getPersonnel();
        $courant = $this->historiqueRepository->findCurrentForPersonnel($personnel);

        if ($courant && $courant->getId() === $mouvement->getId()) {
            return $this->json(['errors' => ['mouvement' => 'Impossible de supprimer le mouvement en cours : ajoutez un nouveau mouvement, ou supprimez d\'abord les mouvements plus récents.']], JsonResponse::HTTP_CONFLICT);
        }

        $this->em->remove($mouvement);
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
