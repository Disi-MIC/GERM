<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Repository\DirectionRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\VehiculeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard_personnel', methods: ['GET'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function personnel(
        Request $request,
        PersonnelRepository $personnelRepository,
        ServiceRepository $serviceRepository,
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

        return $this->render('admin/dashboard/personnel.html.twig', [
            'nb_personnel' => $personnelRepository->count([]),
            'nb_services' => $serviceRepository->count([]),
            'repartition_par_direction' => $repartitionParDirection,
            'repartition_par_service' => $repartitionParService,
            'directions' => $directionRepository->findBy([], ['nom' => 'ASC']),
            'services' => $serviceRepository->findBy([], ['nom' => 'ASC']),
            'filtre_direction' => $filtreDirection,
            'filtre_service' => $filtreService,
        ]);
    }

    #[Route('/admin/dashboard/informatique', name: 'admin_dashboard_materiel', methods: ['GET'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function materiel(MaterielInformatiqueRepository $materielRepository): Response
    {
        return $this->render('admin/dashboard/materiel.html.twig', [
            'nb_materiels' => $materielRepository->count([]),
            'garanties_expirant' => $materielRepository->findGarantiesExpirantBientot(30),
        ]);
    }

    #[Route('/admin/dashboard/parc-auto', name: 'admin_dashboard_vehicule', methods: ['GET'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function vehicule(VehiculeRepository $vehiculeRepository): Response
    {
        return $this->render('admin/dashboard/vehicule.html.twig', [
            'nb_vehicules' => $vehiculeRepository->count([]),
            'echeances_vehicules' => $vehiculeRepository->findEcheancesProches(30),
        ]);
    }

    /**
     * Page d'atterrissage pour un compte n'ayant aucun rôle métier RH
     * (uniquement ROLE_AGENT) — évite un 403 immédiat après connexion.
     */
    #[Route('/admin/aucun-acces', name: 'admin_no_access', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function noAccess(): Response
    {
        return $this->render('admin/dashboard/no_access.html.twig');
    }
}
