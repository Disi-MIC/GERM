<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\CategorieAgentConge;
use App\Entity\User;
use App\Repository\ParametreEligibiliteCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Réglages RH Admin du calcul d'éligibilité/octroi de jours de congé (jours
 * acquis par mois, plafond, délai d'éligibilité), un jeu par
 * CategorieAgentConge — voir ParametreEligibiliteConge et
 * EligibiliteDecisionCongeService, seule source de vérité du calcul. Aucune
 * suppression : les deux catégories sont fixes (voir CategorieAgentConge),
 * "ajouter" un jeu de paramètres revient à enregistrer une catégorie pas
 * encore réglée explicitement (recupererOuCreer() la crée aux valeurs par
 * défaut au premier accès).
 */
#[IsGranted('ROLE_RH_RESPONSABLE')]
class ParametreEligibiliteCongeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParametreEligibiliteCongeRepository $repository,
    ) {
    }

    #[Route('/api/parametres-eligibilite-conge', name: 'api_parametres_eligibilite_conge_liste', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        return $this->json($this->repository->recupererTous(), JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/parametres-eligibilite-conge/{categorie}', name: 'api_parametres_eligibilite_conge_update', methods: ['PUT'])]
    public function update(string $categorie, Request $request): JsonResponse
    {
        $categorieEnum = CategorieAgentConge::tryFrom($categorie);
        if (!$categorieEnum) {
            return $this->json(['errors' => ['categorie' => 'Catégorie inconnue.']], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $erreurs = $this->valider($data);
        if ($erreurs) {
            return $this->json(['errors' => $erreurs], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $parametres = $this->repository->recupererOuCreer($categorieEnum);
        $parametres->setJoursParMois((int) $data['joursParMois']);
        $parametres->setPlafondJours((int) $data['plafondJours']);
        $parametres->setDelaiEligibiliteMois((int) $data['delaiEligibiliteMois']);
        $parametres->setUpdatedAt(new \DateTimeImmutable());
        /** @var User $operateur */
        $operateur = $this->getUser();
        $parametres->setMisAJourPar($operateur);

        $this->em->flush();

        return $this->json($parametres, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /** @return array<string, string> */
    private function valider(array $data): array
    {
        $erreurs = [];

        foreach (['joursParMois', 'plafondJours', 'delaiEligibiliteMois'] as $champ) {
            if (!isset($data[$champ]) || !is_numeric($data[$champ]) || (int) $data[$champ] <= 0) {
                $erreurs[$champ] = 'Doit être un nombre entier positif.';
            }
        }

        return $erreurs;
    }
}
