<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\StatutDemandeCartePro;
use App\Entity\Enum\StatutTicket;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\DecisionCongeRepository;
use App\Repository\DemandeCarteProRepository;
use App\Repository\DemandeDecisionRepository;
use App\Repository\DemandeJouissanceRepository;
use App\Repository\DirectionRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\TicketIncidentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Statistiques des tableaux de bord côté frontend Angular — un par rubrique
 * de traitement (Personnel, Congés, Cartes professionnelles, Informatique),
 * chacun résumant l'activité de la rubrique : ce qui reste à traiter (l'en-
 * cours) et ce qui a été traité, décliné par période (aujourd'hui/cette
 * semaine/ce mois/total). Les compteurs par période sont cumulatifs
 * (aujourd'hui ⊆ cette semaine ⊆ ce mois ⊆ total), pas des tranches
 * exclusives — plus intuitif à lire pour un utilisateur.
 *
 * Agrégés au niveau du rôle (toute l'équipe), pas par utilisateur individuel :
 * les demandes/tickets ne conservent pas qui les a traités (seulement quand),
 * contrairement à TicketIncident::assigneA qui n'existe que pour l'affectation
 * en cours, pas un historique de traitement par agent.
 */
class DashboardController extends AbstractController
{
    #[IsGranted('ROLE_RH_PERSONNEL')]
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

    #[IsGranted('ROLE_RH_CONGE')]
    #[Route('/api/dashboard/conges', name: 'api_dashboard_conges', methods: ['GET'])]
    public function conges(
        DemandeDecisionRepository $demandeDecisionRepository,
        DemandeJouissanceRepository $demandeJouissanceRepository,
        DecisionCongeRepository $decisionCongeRepository,
    ): JsonResponse {
        $bornes = $this->bornesPeriode();
        $traites = $this->bucketsVides(['approuvees', 'refusees']);

        foreach ($demandeDecisionRepository->findBy(['statut' => StatutDemande::APPROUVEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'approuvees', $bornes);
        }
        foreach ($demandeDecisionRepository->findBy(['statut' => StatutDemande::REFUSEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'refusees', $bornes);
        }
        foreach ($demandeJouissanceRepository->findBy(['statut' => StatutDemande::APPROUVEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'approuvees', $bornes);
        }
        foreach ($demandeJouissanceRepository->findBy(['statut' => StatutDemande::REFUSEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'refusees', $bornes);
        }

        return $this->json([
            'enAttente' => [
                'decisions' => $demandeDecisionRepository->count(['statut' => StatutDemande::EN_ATTENTE]),
                'jouissances' => $demandeJouissanceRepository->count(['statut' => StatutDemande::EN_ATTENTE]),
            ],
            'traites' => $traites,
            'decisionsValides' => $decisionCongeRepository->count([]),
        ]);
    }

    #[IsGranted('ROLE_RH_CARTE_PRO')]
    #[Route('/api/dashboard/cartes-professionnelles', name: 'api_dashboard_cartes_professionnelles', methods: ['GET'])]
    public function cartesProfessionnelles(
        DemandeCarteProRepository $demandeCarteProRepository,
        CarteProfessionnelleRepository $carteProfessionnelleRepository,
    ): JsonResponse {
        $bornes = $this->bornesPeriode();
        $traites = $this->bucketsVides(['approuvees', 'refusees']);

        foreach ($demandeCarteProRepository->findBy(['statut' => StatutDemandeCartePro::APPROUVEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'approuvees', $bornes);
        }
        foreach ($demandeCarteProRepository->findBy(['statut' => StatutDemandeCartePro::REFUSEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'refusees', $bornes);
        }

        $dansUnMois = (new \DateTimeImmutable('today'))->modify('+1 month');
        $cartesValides = $carteProfessionnelleRepository->findBy(['statut' => StatutCarteProfessionnelle::VALIDE]);

        return $this->json([
            'enAttente' => $demandeCarteProRepository->count(['statut' => StatutDemandeCartePro::EN_ATTENTE]),
            'transmises' => $demandeCarteProRepository->count(['statut' => StatutDemandeCartePro::TRANSMISE]),
            'traites' => $traites,
            'cartesValides' => \count($cartesValides),
            'cartesExpirantBientot' => \count(array_filter(
                $cartesValides,
                fn ($carte) => $carte->getDateExpiration() && $carte->getDateExpiration() <= $dansUnMois,
            )),
        ]);
    }

    #[Route('/api/dashboard/informatique', name: 'api_dashboard_informatique', methods: ['GET'])]
    public function informatique(
        TicketIncidentRepository $ticketIncidentRepository,
        MaintenanceRepository $maintenanceRepository,
        MaterielInformatiqueRepository $materielInformatiqueRepository,
    ): JsonResponse {
        // Combine stats Stock (matériel/maintenance) et Tickets : #[IsGranted]
        // ne fait pas d'OR entre deux rôles indépendants, d'où ce contrôle
        // manuel plutôt qu'un attribut — ROLE_IT_RESPONSABLE passe aussi,
        // hérité des deux via la hiérarchie.
        if (!$this->isGranted('ROLE_IT_STOCK') && !$this->isGranted('ROLE_IT_TICKETS')) {
            throw $this->createAccessDeniedException();
        }

        $bornes = $this->bornesPeriode();

        $ticketsTraites = $this->bucketsVides(['resolus', 'refuses']);
        foreach ($ticketIncidentRepository->findBy(['statut' => StatutTicket::CLOTURE]) as $ticket) {
            $this->accumulerTraitement($ticketsTraites, $ticket->getDateCloture(), 'resolus', $bornes);
        }
        foreach ($ticketIncidentRepository->findBy(['statut' => StatutTicket::REFUSE]) as $ticket) {
            $this->accumulerTraitement($ticketsTraites, $ticket->getDateResolution() ?? $ticket->getCreatedAt(), 'refuses', $bornes);
        }

        $maintenances = $this->bucketsPeriode();
        foreach ($maintenanceRepository->findAll() as $maintenance) {
            $this->accumulerPeriode($maintenances, $maintenance->getDateRealisation(), $bornes);
        }

        $parEtat = [];
        foreach ($materielInformatiqueRepository->findAll() as $materiel) {
            $etatLibelle = $materiel->getEtat()?->getLibelle() ?? 'Non renseigné';
            $parEtat[$etatLibelle] = ($parEtat[$etatLibelle] ?? 0) + 1;
        }
        ksort($parEtat);

        return $this->json([
            'tickets' => [
                'ouverts' => $ticketIncidentRepository->count(['statut' => StatutTicket::OUVERT]),
                'enCours' => $ticketIncidentRepository->count(['statut' => StatutTicket::EN_COURS]),
                'traites' => $ticketsTraites,
            ],
            'maintenance' => $maintenances,
            'materiel' => [
                'total' => $materielInformatiqueRepository->count([]),
                'parEtat' => $parEtat,
            ],
        ]);
    }

    /**
     * Bornes cumulatives (aujourd'hui ⊆ cette semaine ⊆ ce mois) utilisées par
     * accumulerTraitement()/accumulerPeriode() pour répartir des dates dans les
     * compartiments d'un même tableau de bord.
     *
     * @return array{aujourdhui: \DateTimeImmutable, semaine: \DateTimeImmutable, mois: \DateTimeImmutable}
     */
    private function bornesPeriode(): array
    {
        $aujourdhui = new \DateTimeImmutable('today');

        return [
            'aujourdhui' => $aujourdhui,
            'semaine' => $aujourdhui->modify('monday this week'),
            'mois' => $aujourdhui->modify('first day of this month'),
        ];
    }

    /**
     * @param string[] $cles
     *
     * @return array<string, array<string, int>>
     */
    private function bucketsVides(array $cles): array
    {
        $bucket = array_fill_keys($cles, 0);

        return [
            'aujourdhui' => $bucket,
            'semaine' => $bucket,
            'mois' => $bucket,
            'total' => $bucket,
        ];
    }

    /**
     * @return array{aujourdhui: int, semaine: int, mois: int, total: int}
     */
    private function bucketsPeriode(): array
    {
        return ['aujourdhui' => 0, 'semaine' => 0, 'mois' => 0, 'total' => 0];
    }

    /**
     * @param array<string, array<string, int>> $buckets
     * @param array{aujourdhui: \DateTimeImmutable, semaine: \DateTimeImmutable, mois: \DateTimeImmutable} $bornes
     */
    private function accumulerTraitement(array &$buckets, ?\DateTimeImmutable $date, string $cle, array $bornes): void
    {
        ++$buckets['total'][$cle];
        if (null === $date) {
            return;
        }
        if ($date >= $bornes['mois']) {
            ++$buckets['mois'][$cle];
        }
        if ($date >= $bornes['semaine']) {
            ++$buckets['semaine'][$cle];
        }
        if ($date >= $bornes['aujourdhui']) {
            ++$buckets['aujourdhui'][$cle];
        }
    }

    /**
     * @param array{aujourdhui: int, semaine: int, mois: int, total: int} $buckets
     * @param array{aujourdhui: \DateTimeImmutable, semaine: \DateTimeImmutable, mois: \DateTimeImmutable} $bornes
     */
    private function accumulerPeriode(array &$buckets, ?\DateTimeImmutable $date, array $bornes): void
    {
        ++$buckets['total'];
        if (null === $date) {
            return;
        }
        if ($date >= $bornes['mois']) {
            ++$buckets['mois'];
        }
        if ($date >= $bornes['semaine']) {
            ++$buckets['semaine'];
        }
        if ($date >= $bornes['aujourdhui']) {
            ++$buckets['aujourdhui'];
        }
    }
}
