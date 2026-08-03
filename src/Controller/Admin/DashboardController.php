<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_dashboard')]
#[IsGranted('ROLE_SUPERADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function index(
        PersonnelRepository $personnelRepository,
        MaterielInformatiqueRepository $materielRepository,
        VehiculeRepository $vehiculeRepository,
        ServiceRepository $serviceRepository,
        UserRepository $userRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'nb_personnel' => $personnelRepository->count([]),
            'nb_materiels' => $materielRepository->count([]),
            'nb_vehicules' => $vehiculeRepository->count([]),
            'nb_services' => $serviceRepository->count([]),
            'nb_agents' => $userRepository->count([]),
            'garanties_expirant' => $materielRepository->findGarantiesExpirantBientot(30),
            'echeances_vehicules' => $vehiculeRepository->findEcheancesProches(30),
        ]);
    }
}
