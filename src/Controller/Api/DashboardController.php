<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\ChangementCartouche;
use App\Entity\Direction;
use App\Entity\Enum\CouleurCartouche;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\StatutDemandeCartePro;
use App\Entity\Enum\StatutTicket;
use App\Entity\MaterielInformatique;
use App\Entity\Service;
use App\Entity\TicketIncident;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\ChangementCartoucheRepository;
use App\Repository\DecisionCongeRepository;
use App\Repository\DemandeCarteProRepository;
use App\Repository\DemandeDecisionRepository;
use App\Repository\DemandeJouissanceRepository;
use App\Repository\DirectionRepository;
use App\Repository\LicenceLogicielRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\TicketIncidentRepository;
use App\Service\EcheanceMaintenanceService;
use App\Service\EcheanceRhService;
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
        EcheanceRhService $echeanceRhService,
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

        // Filtre direction/service (signalé le 17/09/2026 : ne réduisait que
        // parDirection/parService, pas les autres cartes — même défaut que
        // GERM sur le tableau de bord informatique, corrigé au même moment).
        // Service prioritaire sur direction, même précédence que
        // PersonnelRepository::findForStats().
        $directionEffective = $filtreService?->getDirection() ?? $filtreDirection;

        $nbServices = match (true) {
            null !== $filtreService => 1,
            null !== $filtreDirection => $serviceRepository->count(['direction' => $filtreDirection]),
            default => $serviceRepository->count([]),
        };

        $servicesSansResponsable = $serviceRepository->findSansResponsable();
        if (null !== $filtreService) {
            $servicesSansResponsable = array_filter($servicesSansResponsable, fn (Service $s) => $s === $filtreService);
        } elseif (null !== $filtreDirection) {
            $servicesSansResponsable = array_filter($servicesSansResponsable, fn (Service $s) => $s->getDirection() === $filtreDirection);
        }

        $directionsSansDirecteur = $directionRepository->findSansDirecteur();
        if (null !== $directionEffective) {
            $directionsSansDirecteur = array_filter($directionsSansDirecteur, fn (Direction $d) => $d === $directionEffective);
        }

        return $this->json([
            'nbPersonnel' => \count($personnels),
            'nbServices' => $nbServices,
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
            'documentsExpirants' => $echeanceRhService->calculerDocuments(serviceId: $filtreService?->getId(), directionId: $filtreDirection?->getId()),
            'structureIncomplete' => [
                'servicesSansResponsable' => array_map(
                    fn ($s) => ['serviceId' => $s->getId(), 'nom' => $s->getNom(), 'direction' => $s->getDirection()?->getNom() ?? ''],
                    $servicesSansResponsable,
                ),
                'directionsSansDirecteur' => array_map(
                    fn ($d) => ['directionId' => $d->getId(), 'nom' => $d->getNom()],
                    $directionsSansDirecteur,
                ),
            ],
            // Délégations : réservé au RH Responsable, comme Delegation::$security — un
            // simple ROLE_RH_PERSONNEL (qui a accès à ce dashboard) n'y a pas droit.
            'delegationsExpirantes' => $this->isGranted('ROLE_RH_RESPONSABLE')
                ? $echeanceRhService->calculerDelegations(serviceId: $filtreService?->getId(), directionId: $filtreDirection?->getId())
                : null,
        ]);
    }

    #[IsGranted('ROLE_RH_CONGE')]
    #[Route('/api/dashboard/conges', name: 'api_dashboard_conges', methods: ['GET'])]
    public function conges(
        DemandeDecisionRepository $demandeDecisionRepository,
        DemandeJouissanceRepository $demandeJouissanceRepository,
        DecisionCongeRepository $decisionCongeRepository,
        EcheanceRhService $echeanceRhService,
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
            'decisionsExpirantes' => $echeanceRhService->calculerDecisions(),
        ]);
    }

    #[IsGranted('ROLE_RH_CARTE_PRO')]
    #[Route('/api/dashboard/cartes-professionnelles', name: 'api_dashboard_cartes_professionnelles', methods: ['GET'])]
    public function cartesProfessionnelles(
        DemandeCarteProRepository $demandeCarteProRepository,
        CarteProfessionnelleRepository $carteProfessionnelleRepository,
        EcheanceRhService $echeanceRhService,
    ): JsonResponse {
        $bornes = $this->bornesPeriode();
        $traites = $this->bucketsVides(['approuvees', 'refusees']);

        foreach ($demandeCarteProRepository->findBy(['statut' => StatutDemandeCartePro::APPROUVEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'approuvees', $bornes);
        }
        foreach ($demandeCarteProRepository->findBy(['statut' => StatutDemandeCartePro::REFUSEE]) as $demande) {
            $this->accumulerTraitement($traites, $demande->getDateTraitement(), 'refusees', $bornes);
        }

        return $this->json([
            'enAttente' => $demandeCarteProRepository->count(['statut' => StatutDemandeCartePro::EN_ATTENTE]),
            'transmises' => $demandeCarteProRepository->count(['statut' => StatutDemandeCartePro::TRANSMISE]),
            'traites' => $traites,
            'cartesValides' => $carteProfessionnelleRepository->count(['statut' => StatutCarteProfessionnelle::VALIDE]),
            'cartesExpirantBientot' => $echeanceRhService->calculerCartes(),
        ]);
    }

    #[Route('/api/dashboard/informatique', name: 'api_dashboard_informatique', methods: ['GET'])]
    public function informatique(
        Request $request,
        TicketIncidentRepository $ticketIncidentRepository,
        MaintenanceRepository $maintenanceRepository,
        MaterielInformatiqueRepository $materielInformatiqueRepository,
        LicenceLogicielRepository $licenceLogicielRepository,
        ChangementCartoucheRepository $changementCartoucheRepository,
        EcheanceMaintenanceService $echeanceMaintenanceService,
    ): JsonResponse {
        // Combine stats Stock (matériel/maintenance) et Tickets : #[IsGranted]
        // ne fait pas d'OR entre deux rôles indépendants, d'où ce contrôle
        // manuel plutôt qu'un attribut — ROLE_IT_RESPONSABLE passe aussi,
        // hérité des deux via la hiérarchie.
        if (!$this->isGranted('ROLE_IT_STOCK') && !$this->isGranted('ROLE_IT_TICKETS')) {
            throw $this->createAccessDeniedException();
        }

        // Filtre agent/service (AgentOuServiceFiltreComponent) : réduit
        // désormais l'intégralité du tableau de bord à ce périmètre, pas
        // seulement le bloc Cartouches (voir git blame pour l'historique —
        // signalé le 17/09/2026, le filtre semblait sans effet sur le reste).
        // Deux critères selon la nature de la donnée :
        // - matériel/maintenance/cartouches : équipement rattaché au service
        //   (MaterielInformatique::getService(), déjà le service effectif) ;
        // - tickets : agent qui a SIGNALÉ le ticket (Ticket::$personnel), pas
        //   le matériel concerné — décision du 17/09/2026, pour ne pas faire
        //   disparaître des statistiques filtrées les tickets sans matériel
        //   précis (accès, logiciel).
        // licencesExpirantBientot reste hors périmètre : LicenceLogiciel n'a
        // aucun lien vers un service ou un agent en base.
        $serviceId = $request->query->get('service') ? (int) $request->query->get('service') : null;

        $bornes = $this->bornesPeriode();

        $ticketsOuverts = $this->filtrerTicketsParServiceDeclarant($ticketIncidentRepository->findBy(['statut' => StatutTicket::OUVERT]), $serviceId);
        $ticketsEnCours = $this->filtrerTicketsParServiceDeclarant($ticketIncidentRepository->findBy(['statut' => StatutTicket::EN_COURS]), $serviceId);

        $ticketsTraites = $this->bucketsVides(['resolus', 'refuses']);
        foreach ($this->filtrerTicketsParServiceDeclarant($ticketIncidentRepository->findBy(['statut' => StatutTicket::CLOTURE]), $serviceId) as $ticket) {
            $this->accumulerTraitement($ticketsTraites, $ticket->getDateCloture(), 'resolus', $bornes);
        }
        foreach ($this->filtrerTicketsParServiceDeclarant($ticketIncidentRepository->findBy(['statut' => StatutTicket::REFUSE]), $serviceId) as $ticket) {
            $this->accumulerTraitement($ticketsTraites, $ticket->getDateResolution() ?? $ticket->getCreatedAt(), 'refuses', $bornes);
        }

        $maintenances = $this->bucketsPeriode();
        foreach ($maintenanceRepository->findAll() as $maintenance) {
            if (null !== $serviceId && $serviceId !== $maintenance->getMateriel()?->getService()?->getId()) {
                continue;
            }
            $this->accumulerPeriode($maintenances, $maintenance->getDateRealisation(), $bornes);
        }

        $materiels = $materielInformatiqueRepository->findAll();
        if (null !== $serviceId) {
            $materiels = array_filter($materiels, fn (MaterielInformatique $m) => $serviceId === $m->getService()?->getId());
        }
        $parEtat = [];
        foreach ($materiels as $materiel) {
            $etatLibelle = $materiel->getEtat()?->getLibelle() ?? 'Non renseigné';
            $parEtat[$etatLibelle] = ($parEtat[$etatLibelle] ?? 0) + 1;
        }
        ksort($parEtat);

        $materielsSansNiveau = $materielInformatiqueRepository->findSansNiveauVulnerabilite();
        if (null !== $serviceId) {
            $materielsSansNiveau = array_filter($materielsSansNiveau, fn (MaterielInformatique $m) => $serviceId === $m->getService()?->getId());
        }

        return $this->json([
            'tickets' => [
                'ouverts' => \count($ticketsOuverts),
                'enCours' => \count($ticketsEnCours),
                'traites' => $ticketsTraites,
            ],
            'maintenance' => $maintenances,
            'materiel' => [
                'total' => \count($materiels),
                'parEtat' => $parEtat,
            ],
            'echeancesMaintenance' => $echeanceMaintenanceService->calculer(serviceId: $serviceId),
            'materielsSansNiveauVulnerabilite' => array_map(
                fn ($m) => [
                    'materielId' => $m->getId(),
                    'numeroInventaire' => $m->getNumeroInventaire(),
                    'marque' => $m->getMarque(),
                    'modele' => $m->getModele(),
                ],
                $materielsSansNiveau,
            ),
            'licencesExpirantBientot' => $this->calculerEcheancesLicences($licenceLogicielRepository, $materielInformatiqueRepository),
            'slaTickets' => $this->calculerSlaTickets($ticketIncidentRepository, $serviceId),
            'cartouches' => $this->calculerCartouches($changementCartoucheRepository, $serviceId),
        ]);
    }

    /**
     * Vue "approvisionnement" du journal des cartouches (ChangementCartouche,
     * voir Api/ChangementCartoucheController) : volume mensuel glissant sur 12
     * mois (le mensuel/trimestriel/semestriel/annuel se dérivent tous par
     * simple somme côté Angular, un seul agrégat à maintenir ici), répartition
     * par couleur avec durée moyenne d'écoulement, services/agents/références/
     * imprimantes les plus consommateurs — de quoi décider du rythme de
     * réapprovisionnement, pas seulement consulter le journal brut.
     *
     * $serviceId, quand fourni (filtre agent/service côté Angular, voir
     * AgentOuServiceFiltreComponent — un agent s'y résout toujours en le
     * service de son affectation), réduit tout l'agrégat à ce périmètre :
     * MaterielInformatique::$service est déjà le service effectif (dérivé de
     * l'agent affecté quand il y en a un, sinon propre au matériel), donc un
     * seul filtre sert les deux modes du sélecteur.
     *
     * @return array{
     *     total: int,
     *     parMois: array<string, int>,
     *     parCouleur: array<string, array{count: int, dureeMoyenneJours: ?int}>,
     *     topReferences: list<array{reference: string, count: int}>,
     *     topImprimantes: list<array{materielId: int, label: string, count: int}>,
     *     topServices: list<array{serviceId: int, nom: string, count: int}>,
     *     topAgents: list<array{personnelId: int, nom: string, count: int}>,
     * }
     */
    private function calculerCartouches(ChangementCartoucheRepository $changementCartoucheRepository, ?int $serviceId = null): array
    {
        $changements = $changementCartoucheRepository->findBy([], ['dateChangement' => 'ASC']);
        if (null !== $serviceId) {
            $changements = array_values(array_filter(
                $changements,
                fn (ChangementCartouche $c) => $serviceId === $c->getMateriel()?->getService()?->getId(),
            ));
        }

        $parMois = [];
        $curseur = (new \DateTimeImmutable('first day of this month'))->modify('-11 months');
        for ($i = 0; $i < 12; ++$i) {
            $parMois[$curseur->format('Y-m')] = 0;
            $curseur = $curseur->modify('+1 month');
        }

        $parCouleur = [];
        foreach (CouleurCartouche::cases() as $couleur) {
            $parCouleur[$couleur->value] = ['count' => 0, 'total' => []];
        }

        $comptesReferences = [];
        $comptesImprimantes = [];
        $labelsImprimantes = [];
        $comptesServices = [];
        $labelsServices = [];
        $comptesAgents = [];
        $labelsAgents = [];

        /** @var array<string, ChangementCartouche> $dernierParGroupe */
        $dernierParGroupe = [];

        foreach ($changements as $changement) {
            $date = $changement->getDateChangement();
            if ($date && isset($parMois[$date->format('Y-m')])) {
                ++$parMois[$date->format('Y-m')];
            }

            $couleur = $changement->getCouleur()->value;
            ++$parCouleur[$couleur]['count'];

            $materiel = $changement->getMateriel();
            $groupe = \sprintf('%d::%s', $materiel?->getId() ?? 0, $couleur);
            if (isset($dernierParGroupe[$groupe]) && $date) {
                $precedent = $dernierParGroupe[$groupe]->getDateChangement();
                if ($precedent) {
                    $parCouleur[$couleur]['total'][] = $date->diff($precedent)->days;
                }
            }
            $dernierParGroupe[$groupe] = $changement;

            if ($reference = $changement->getReference()) {
                $comptesReferences[$reference] = ($comptesReferences[$reference] ?? 0) + 1;
            }

            if ($materiel) {
                $id = $materiel->getId();
                $comptesImprimantes[$id] = ($comptesImprimantes[$id] ?? 0) + 1;
                $labelsImprimantes[$id] ??= \sprintf('%s %s (%s)', $materiel->getMarque(), $materiel->getModele(), $materiel->getNumeroInventaire());

                if ($service = $materiel->getService()) {
                    $comptesServices[$service->getId()] = ($comptesServices[$service->getId()] ?? 0) + 1;
                    $labelsServices[$service->getId()] ??= $service->getNom();
                }

                // Contrairement à $service ci-dessus (toujours renseigné dès
                // qu'un matériel appartient à un parc), $affecteA ne l'est que
                // pour une imprimante personnelle plutôt que partagée dans un
                // service — la plupart des imprimantes n'auront donc pas
                // d'entrée ici, volontairement (rien à imputer à un agent
                // précis sur du matériel partagé).
                if ($agent = $materiel->getAffecteA()) {
                    $comptesAgents[$agent->getId()] = ($comptesAgents[$agent->getId()] ?? 0) + 1;
                    $labelsAgents[$agent->getId()] ??= $agent->getNomComplet();
                }
            }
        }

        foreach ($parCouleur as $couleur => $donnees) {
            $ecarts = $donnees['total'];
            $parCouleur[$couleur] = [
                'count' => $donnees['count'],
                'dureeMoyenneJours' => \count($ecarts) > 0 ? (int) round(array_sum($ecarts) / \count($ecarts)) : null,
            ];
        }

        arsort($comptesReferences);
        $topReferences = [];
        foreach (\array_slice($comptesReferences, 0, 5, true) as $reference => $count) {
            $topReferences[] = ['reference' => $reference, 'count' => $count];
        }

        arsort($comptesImprimantes);
        $topImprimantes = [];
        foreach (\array_slice($comptesImprimantes, 0, 5, true) as $materielId => $count) {
            $topImprimantes[] = ['materielId' => $materielId, 'label' => $labelsImprimantes[$materielId], 'count' => $count];
        }

        arsort($comptesServices);
        $topServices = [];
        foreach (\array_slice($comptesServices, 0, 5, true) as $serviceId => $count) {
            $topServices[] = ['serviceId' => $serviceId, 'nom' => $labelsServices[$serviceId], 'count' => $count];
        }

        arsort($comptesAgents);
        $topAgents = [];
        foreach (\array_slice($comptesAgents, 0, 5, true) as $personnelId => $count) {
            $topAgents[] = ['personnelId' => $personnelId, 'nom' => $labelsAgents[$personnelId], 'count' => $count];
        }

        return [
            'total' => \count($changements),
            'parMois' => $parMois,
            'parCouleur' => $parCouleur,
            'topReferences' => $topReferences,
            'topImprimantes' => $topImprimantes,
            'topServices' => $topServices,
            'topAgents' => $topAgents,
        ];
    }

    /**
     * Tickets actifs (ouverts/en cours) dépassant ou approchant leur délai
     * cible de résolution (voir TicketIncident::getEcheanceSla(), seule
     * source du calcul). "À risque" = moins de 20 % du délai restant, seuil
     * relatif plutôt que fixe puisque le délai varie de 4h (critique) à 120h
     * (basse) — un seuil en heures fixes n'aurait pas de sens pour les deux
     * à la fois.
     *
     * @return array{enRetard: list<array<string, mixed>>, aRisque: list<array<string, mixed>>}
     */
    private function calculerSlaTickets(TicketIncidentRepository $ticketIncidentRepository, ?int $serviceId = null): array
    {
        $maintenant = new \DateTimeImmutable();
        $enRetard = [];
        $aRisque = [];

        $ticketsActifs = $this->filtrerTicketsParServiceDeclarant([
            ...$ticketIncidentRepository->findBy(['statut' => StatutTicket::OUVERT]),
            ...$ticketIncidentRepository->findBy(['statut' => StatutTicket::EN_COURS]),
        ], $serviceId);

        foreach ($ticketsActifs as $ticket) {
            $echeance = $ticket->getEcheanceSla();
            $createdAt = $ticket->getCreatedAt();
            if (null === $echeance || null === $createdAt) {
                continue;
            }
            $heuresSla = ($echeance->getTimestamp() - $createdAt->getTimestamp()) / 3600;

            $entree = [
                'ticketId' => $ticket->getId(),
                'titre' => $ticket->getTitre(),
                'priorite' => $ticket->getPriorite()?->getLibelle() ?? '',
                'niveau' => $ticket->getNiveau()->label(),
                'echeance' => $echeance->format('Y-m-d\TH:i:s'),
            ];

            if ($echeance < $maintenant) {
                $entree['heures'] = (int) round(($maintenant->getTimestamp() - $echeance->getTimestamp()) / 3600);
                $enRetard[] = $entree;
                continue;
            }

            $heuresRestantes = ($echeance->getTimestamp() - $maintenant->getTimestamp()) / 3600;
            if ($heuresRestantes <= $heuresSla * 0.2) {
                $entree['heures'] = (int) round($heuresRestantes);
                $aRisque[] = $entree;
            }
        }

        usort($enRetard, fn ($a, $b) => $b['heures'] <=> $a['heures']);
        usort($aRisque, fn ($a, $b) => $a['heures'] <=> $b['heures']);

        return ['enRetard' => $enRetard, 'aRisque' => $aRisque];
    }

    /**
     * Filtre par le service de l'agent qui a SIGNALÉ le ticket
     * (TicketIncident::$personnel), pas par le matériel concerné —
     * contrairement à calculerCartouches()/EcheanceMaintenanceService, qui
     * filtrent par équipement. Décision du 17/09/2026 : un ticket sans
     * matériel précis (accès, logiciel) doit rester compté quand on filtre
     * par l'agent qui l'a soumis, pas disparaître des statistiques.
     *
     * @param TicketIncident[] $tickets
     *
     * @return TicketIncident[]
     */
    private function filtrerTicketsParServiceDeclarant(array $tickets, ?int $serviceId): array
    {
        if (null === $serviceId) {
            return $tickets;
        }

        return array_filter($tickets, fn (TicketIncident $t) => $serviceId === $t->getPersonnel()?->getService()?->getId());
    }

    /**
     * Licences arrivant à expiration (ou déjà expirées) — même fenêtre de 30
     * jours et même logique enRetard/aVenir que calculerEcheancesMaintenance.
     * Chaque ligne de licence (pas seulement la plus récente par logiciel,
     * voir LicenceLogicielRepository::findAvecExpiration) est prise en
     * compte, mais seulement si du matériel y est encore explicitement
     * rattaché : une licence à 0 poste est un renouvellement abandonné,
     * rien à signaler.
     *
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    private function calculerEcheancesLicences(
        LicenceLogicielRepository $licenceLogicielRepository,
        MaterielInformatiqueRepository $materielInformatiqueRepository,
    ): array {
        $aujourdhui = new \DateTimeImmutable('today');
        $limite = $aujourdhui->modify('+30 days');

        $enRetard = [];
        $aVenir = [];

        foreach ($licenceLogicielRepository->findAvecExpiration() as $licence) {
            $echeance = $licence->getDateExpiration();
            if (null === $echeance) {
                continue;
            }

            $nombrePostes = $materielInformatiqueRepository->countParLicence($licence);
            if (0 === $nombrePostes) {
                continue;
            }

            $entree = [
                'licenceId' => $licence->getId(),
                'logicielId' => $licence->getLogiciel()?->getId(),
                'logiciel' => $licence->getLogiciel()?->getLibelle() ?? '',
                'nombrePostes' => $nombrePostes,
                'echeance' => $echeance->format('Y-m-d'),
            ];

            if ($echeance < $aujourdhui) {
                $entree['jours'] = $aujourdhui->diff($echeance)->days;
                $enRetard[] = $entree;
            } elseif ($echeance <= $limite) {
                $entree['jours'] = $echeance->diff($aujourdhui)->days;
                $aVenir[] = $entree;
            }
        }

        usort($enRetard, fn ($a, $b) => $b['jours'] <=> $a['jours']);
        usort($aVenir, fn ($a, $b) => $a['jours'] <=> $b['jours']);

        return ['enRetard' => $enRetard, 'aVenir' => $aVenir];
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
