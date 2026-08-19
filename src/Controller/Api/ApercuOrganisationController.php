<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Direction;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\DirectionRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Aperçus organisationnels scopés au rôle hiérarchique de l'utilisateur
 * connecté : chef de service (Service::$responsable), directeur
 * (Direction::$directeur) ou direction ministérielle (SG/DC/Ministre,
 * ROLE_DIRECTION_MINISTERIELLE — vue globale du Ministère). Les deux
 * premiers ne sont jamais gérés par rôle Symfony : l'accès découle
 * directement du champ responsable/directeur assigné par le RH Admin
 * (voir ServiceType/DirectionType), une seule source de vérité plutôt que
 * de dupliquer l'information dans un rôle à maintenir à part.
 */
#[IsGranted('ROLE_AGENT')]
class ApercuOrganisationController extends AbstractController
{
    /**
     * Service dont l'agent connecté est le responsable désigné — 404 s'il
     * n'en dirige aucun. Liste des agents triée par grade puis nom (pas un
     * organigramme rapporteur/responsable : le grade/la fonction sont de
     * simples champs texte, aucune relation manager n'existe côté Personnel).
     */
    #[Route('/api/apercu-organisation/mon-service', name: 'api_apercu_organisation_mon_service', methods: ['GET'])]
    public function monService(
        ServiceRepository $serviceRepository,
        PersonnelRepository $personnelRepository,
        MaterielInformatiqueRepository $materielRepository,
    ): JsonResponse {
        $personnel = $this->personnelConnecte();
        $service = $personnel ? $serviceRepository->findOneBy(['responsable' => $personnel]) : null;

        if (!$service) {
            return $this->json(['errors' => ['service' => "Vous n'êtes désigné responsable d'aucun service."]], JsonResponse::HTTP_NOT_FOUND);
        }

        $agents = $personnelRepository->findBy(['service' => $service], ['grade' => 'ASC', 'nom' => 'ASC']);

        return $this->json([
            'service' => [
                'id' => $service->getId(),
                'nom' => $service->getNom(),
                'code' => $service->getCode(),
                'direction' => $service->getDirection() ? [
                    'id' => $service->getDirection()->getId(),
                    'nom' => $service->getDirection()->getNom(),
                ] : null,
            ],
            'nbAgents' => \count($agents),
            'nbMateriels' => $materielRepository->countPourService($service),
            'agents' => array_map($this->agentVersTableau(...), $agents),
        ]);
    }

    /**
     * Direction dont l'agent connecté est le directeur désigné — 404 sinon.
     * Agrège les services qui la composent (avec leur effectif) et la liste
     * complète des agents de la direction (tous services confondus).
     */
    #[Route('/api/apercu-organisation/ma-direction', name: 'api_apercu_organisation_ma_direction', methods: ['GET'])]
    public function maDirection(
        DirectionRepository $directionRepository,
        PersonnelRepository $personnelRepository,
    ): JsonResponse {
        $personnel = $this->personnelConnecte();
        $direction = $personnel ? $directionRepository->findOneBy(['directeur' => $personnel]) : null;

        if (!$direction) {
            return $this->json(['errors' => ['direction' => "Vous n'êtes désigné directeur d'aucune direction."]], JsonResponse::HTTP_NOT_FOUND);
        }

        $agents = $personnelRepository->findForStats($direction, null);
        usort($agents, fn (Personnel $a, Personnel $b) => [$a->getService()?->getNom(), $a->getGrade(), $a->getNom()]
            <=> [$b->getService()?->getNom(), $b->getGrade(), $b->getNom()]);

        $services = $direction->getServices();
        $servicesTableau = [];
        foreach ($services as $service) {
            $servicesTableau[] = [
                'id' => $service->getId(),
                'nom' => $service->getNom(),
                'nbAgents' => \count($service->getPersonnels()),
            ];
        }
        usort($servicesTableau, fn ($a, $b) => $a['nom'] <=> $b['nom']);

        return $this->json([
            'direction' => [
                'id' => $direction->getId(),
                'nom' => $direction->getNom(),
                'code' => $direction->getCode(),
            ],
            'nbServices' => \count($services),
            'nbAgents' => \count($agents),
            'services' => $servicesTableau,
            'agents' => array_map(
                fn (Personnel $p) => [...$this->agentVersTableau($p), 'serviceNom' => $p->getService()?->getNom()],
                $agents,
            ),
        ]);
    }

    /**
     * Vue globale du Ministère (SG/DC/Ministre) : répartitions par
     * direction/service/grade (chacune déclinée hommes/femmes), filtrables
     * par direction, service et grade — mêmes principe et repository que
     * DashboardController::personnel(), étendu au grade. Les compteurs
     * "nbAgents/nbServices/nbDirections" restent toujours globaux (non
     * filtrés), seules les répartitions ci-dessous suivent le filtre —
     * même convention que DashboardComponent (filtrer resserre les
     * graphiques, pas les compteurs globaux). Plus un résumé du parc
     * informatique (le détail tickets/SLA/licences reste propre au tableau
     * de bord IT, ROLE_IT_*) et, par direction, l'âge/l'ancienneté de son
     * directeur — jamais d'autre donnée personnelle.
     */
    #[IsGranted('ROLE_DIRECTION_MINISTERIELLE')]
    #[Route('/api/apercu-organisation/ministere', name: 'api_apercu_organisation_ministere', methods: ['GET'])]
    public function ministere(
        Request $request,
        PersonnelRepository $personnelRepository,
        ServiceRepository $serviceRepository,
        DirectionRepository $directionRepository,
        MaterielInformatiqueRepository $materielRepository,
    ): JsonResponse {
        $filtreDirection = $request->query->get('direction')
            ? $directionRepository->find($request->query->get('direction'))
            : null;
        $filtreService = $request->query->get('service')
            ? $serviceRepository->find($request->query->get('service'))
            : null;
        $filtreGrade = $request->query->get('grade') ?: null;

        $personnelsFiltres = $personnelRepository->findForStats($filtreDirection, $filtreService, $filtreGrade);

        $repartitionParDirection = [];
        $repartitionParService = [];
        $repartitionParGrade = [];

        foreach ($personnelsFiltres as $personnel) {
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

            $gradeNom = $personnel->getGrade() ?: 'Non renseigné';
            $repartitionParGrade[$gradeNom] ??= ['M' => 0, 'F' => 0];
            ++$repartitionParGrade[$gradeNom][$sexe];
        }

        ksort($repartitionParDirection);
        ksort($repartitionParService);
        ksort($repartitionParGrade);

        $parEtat = [];
        foreach ($materielRepository->findAll() as $materiel) {
            $etatLibelle = $materiel->getEtat()?->getLibelle() ?? 'Non renseigné';
            $parEtat[$etatLibelle] = ($parEtat[$etatLibelle] ?? 0) + 1;
        }
        ksort($parEtat);

        return $this->json([
            'nbAgents' => $personnelRepository->count([]),
            'nbServices' => $serviceRepository->count([]),
            'nbDirections' => $directionRepository->count([]),
            'parDirection' => $repartitionParDirection,
            'parService' => $repartitionParService,
            'parGrade' => $repartitionParGrade,
            'directions' => array_map(
                fn (Direction $d) => [
                    'id' => $d->getId(),
                    'nom' => $d->getNom(),
                    'nbServices' => \count($d->getServices()),
                    'directeur' => $d->getDirecteur() ? [
                        'nom' => $d->getDirecteur()->getNomComplet(),
                        ...$this->ageEtAnciennete($d->getDirecteur()),
                    ] : null,
                ],
                $directionRepository->findBy([], ['nom' => 'ASC']),
            ),
            'services' => array_map(
                fn (Service $s) => ['id' => $s->getId(), 'nom' => $s->getNom()],
                $serviceRepository->findBy([], ['nom' => 'ASC']),
            ),
            'grades' => $personnelRepository->findDistinctGrades(),
            'materiel' => [
                'total' => $materielRepository->count([]),
                'parEtat' => $parEtat,
            ],
            'filtreDirection' => $filtreDirection?->getId(),
            'filtreService' => $filtreService?->getId(),
            'filtreGrade' => $filtreGrade,
        ]);
    }

    /**
     * Âge et ancienneté (années depuis la date d'embauche) — jamais d'autre
     * donnée personnelle sur un directeur affiché dans l'aperçu Ministère.
     *
     * @return array{age: ?int, anciennete: ?int}
     */
    private function ageEtAnciennete(Personnel $personnel): array
    {
        $aujourdhui = new \DateTimeImmutable('today');

        return [
            'age' => $personnel->getDateNaissance() ? $aujourdhui->diff($personnel->getDateNaissance())->y : null,
            'anciennete' => $personnel->getDateEmbauche() ? $aujourdhui->diff($personnel->getDateEmbauche())->y : null,
        ];
    }

    /**
     * @return array{id: int, nomComplet: string, matricule: ?string, fonction: ?string, grade: ?string, statut: ?string}
     */
    private function agentVersTableau(Personnel $personnel): array
    {
        return [
            'id' => $personnel->getId(),
            'nomComplet' => $personnel->getNomComplet(),
            'matricule' => $personnel->getMatricule(),
            'fonction' => $personnel->getFonction(),
            'grade' => $personnel->getGrade(),
            'statut' => $personnel->getStatut()?->value,
        ];
    }

    private function personnelConnecte(): ?Personnel
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getPersonnel();
    }
}
