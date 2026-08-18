<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Repository\ParametresDecisionCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Réglages du texte légal par défaut (visas des décrets, articles 2 et 3)
 * inséré automatiquement dans chaque DecisionConge générée — voir le
 * commentaire de classe de ParametresDecisionConge. Backoffice RH Admin
 * uniquement : ce texte n'a rien à voir avec le circuit d'approbation
 * lui-même (ROLE_RH_CONGE), il ne change qu'à l'occasion d'un nouveau
 * décret ou d'une révision réglementaire.
 */
#[IsGranted('ROLE_ADMIN_RH')]
class ParametresDecisionCongeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParametresDecisionCongeRepository $repository,
    ) {
    }

    #[Route('/api/parametres-decision-conge', name: 'api_parametres_decision_conge_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json($this->repository->recupererOuCreer(), JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/parametres-decision-conge', name: 'api_parametres_decision_conge_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $parametres = $this->repository->recupererOuCreer();
        $data = json_decode($request->getContent(), true) ?? [];

        $parametres->setVisasDecrets(isset($data['visasDecrets']) ? (string) $data['visasDecrets'] : null);
        $parametres->setArticle2(isset($data['article2']) ? (string) $data['article2'] : null);
        $parametres->setArticle3(isset($data['article3']) ? (string) $data['article3'] : null);
        $parametres->setUpdatedAt(new \DateTimeImmutable());
        /** @var User $operateur */
        $operateur = $this->getUser();
        $parametres->setMisAJourPar($operateur);

        $this->em->flush();

        return $this->json($parametres, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }
}
