<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Repository\DirectionRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Statistiques du tableau de bord Personnel côté frontend Angular. Reproduit
 * l'agrégation PHP (répartition M/F par direction/service) faite jusqu'ici
 * uniquement dans Admin\DashboardController::personnel(), renvoyée en JSON
 * plutôt qu'en Twig — aucune entité Direction/Service exposée pour ça, tout
 * reste calculé côté serveur.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class DashboardController extends AbstractController
{
    #[Route('/api/dashboard/personnel', name: 'api_dashboard_personnel', methods: ['GET'])]
    public function personnel(
        Request $request,
        PersonnelRepository $personnelRepository,
        ServiceRepository $serviceRepository,
        DirectionRepository $directionRepository,
    ): JsonResponse {
        $filtreDirection = $request->query->get('direction')
            ? $directionRepository->find($request->query->get('direction'))
            : null;
        $filtreService = $request->query->get('service')
            ? $serviceRepository->find($request->query->get('service'))
            : null;

        $personnels = $personnelRepository->findForStats($filtreDirection, $filtreService);

        $repartitionParDirection = [];
        $repartitionParService = [];

        foreach ($personnels as $personnel) {
            $sexe = $personnel->getSexe()?->value;
            if ('M' !== $sexe && 'F' !== $sexe) {
                continue;
            }

            $directionNom = $personnel->getService()?->getDirection()?->getNom() ?? 'Non renseigné';
            $repartitionParDirection[$directionNom] ??= ['M' => 0, 'F' => 0];
            ++$repartitionParDirection[$directionNom][$sexe];

            $serviceNom = $personnel->getService()?->getNom() ?? 'Non renseigné';
            $repartitionParService[$serviceNom] ??= ['M' => 0, 'F' => 0];
            ++$repartitionParService[$serviceNom][$sexe];
        }

        ksort($repartitionParDirection);
        ksort($repartitionParService);

        return $this->json([
            'nbPersonnel' => $personnelRepository->count([]),
            'nbServices' => $serviceRepository->count([]),
            'parDirection' => $repartitionParDirection,
            'parService' => $repartitionParService,
            'directions' => array_map(
                fn ($d) => ['id' => $d->getId(), 'nom' => $d->getNom()],
                $directionRepository->findBy([], ['nom' => 'ASC']),
            ),
            'services' => array_map(
                fn ($s) => ['id' => $s->getId(), 'nom' => $s->getNom()],
                $serviceRepository->findBy([], ['nom' => 'ASC']),
            ),
            'filtreDirection' => $filtreDirection?->getId(),
            'filtreService' => $filtreService?->getId(),
        ]);
    }
}
