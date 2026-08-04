<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Repository\DirectionRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_dashboard')]
#[IsGranted('ROLE_SUPERADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(
        Request $request,
        PersonnelRepository $personnelRepository,
        MaterielInformatiqueRepository $materielRepository,
        VehiculeRepository $vehiculeRepository,
        ServiceRepository $serviceRepository,
        UserRepository $userRepository,
        DirectionRepository $directionRepository,
    ): Response {
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

        return $this->render('admin/dashboard.html.twig', [
            'nb_personnel' => $personnelRepository->count([]),
            'nb_materiels' => $materielRepository->count([]),
            'nb_vehicules' => $vehiculeRepository->count([]),
            'nb_services' => $serviceRepository->count([]),
            'nb_agents' => $userRepository->count([]),
            'garanties_expirant' => $materielRepository->findGarantiesExpirantBientot(30),
            'echeances_vehicules' => $vehiculeRepository->findEcheancesProches(30),
            'repartition_par_direction' => $repartitionParDirection,
            'repartition_par_service' => $repartitionParService,
            'directions' => $directionRepository->findBy([], ['nom' => 'ASC']),
            'services' => $serviceRepository->findBy([], ['nom' => 'ASC']),
            'filtre_direction' => $filtreDirection,
            'filtre_service' => $filtreService,
        ]);
    }
}
