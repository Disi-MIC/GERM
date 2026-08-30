<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\DirectionRepository;
use App\Repository\ServiceRepository;
use App\Service\EcheanceRhService;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Alerte proactive RH : sans elle, une échéance (document administratif,
 * carte professionnelle, décision de congé, délégation — voir
 * EcheanceRhService) ne se voit que si quelqu'un va consulter le tableau de
 * bord concerné. Notifications indépendantes plutôt qu'une seule groupée
 * (contrairement à NotifierAlertesParcInformatiqueCommand) : les rubriques RH
 * ont des publics distincts (ROLE_RH_PERSONNEL/CARTE_PRO/CONGE/RESPONSABLE),
 * une seule ne concerne jamais toutes les rubriques à la fois. Pensée pour un
 * cron quotidien (voir déploiement) — se répète tant que rien n'est résolu
 * (pas d'état "déjà notifié" à ce stade, même simplification volontaire que
 * la commande parc informatique).
 */
#[AsCommand(
    name: 'app:notifier-alertes-rh',
    description: 'Notifie le RH concerné des documents, cartes professionnelles et décisions de congé arrivant à échéance.',
)]
class NotifierAlertesRhCommand extends Command
{
    private const ROLES_PAR_RUBRIQUE = [
        'documents' => [User::ROLE_RH_PERSONNEL, User::ROLE_RH_RESPONSABLE],
        'cartes' => [User::ROLE_RH_CARTE_PRO, User::ROLE_RH_RESPONSABLE],
        'decisions' => [User::ROLE_RH_CONGE, User::ROLE_RH_RESPONSABLE],
        // Délégations : réservé au RH Responsable, comme Delegation::$security (pas de
        // sous-rôle dédié contrairement aux trois autres rubriques).
        'delegations' => [User::ROLE_RH_RESPONSABLE],
    ];

    public function __construct(
        private readonly EcheanceRhService $echeanceRhService,
        private readonly ServiceRepository $serviceRepository,
        private readonly DirectionRepository $directionRepository,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $nbNotifiees = 0;
        $nbNotifiees += (int) $this->notifierRubrique(
            $this->echeanceRhService->calculerDocuments(),
            self::ROLES_PAR_RUBRIQUE['documents'],
            '/dashboard',
            'document(s) administratif(s)',
            fn (array $e) => \sprintf('%s (%s) — %s', $e['libelle'], $e['personnel'], $e['type']),
        );
        $nbNotifiees += (int) $this->notifierRubrique(
            $this->echeanceRhService->calculerCartes(),
            self::ROLES_PAR_RUBRIQUE['cartes'],
            '/dashboard-cartes-professionnelles',
            'carte(s) professionnelle(s)',
            fn (array $e) => \sprintf('%s — %s', $e['numero'], $e['personnel']),
        );
        $nbNotifiees += (int) $this->notifierRubrique(
            $this->echeanceRhService->calculerDecisions(),
            self::ROLES_PAR_RUBRIQUE['decisions'],
            '/dashboard-conges',
            'décision(s) de congé',
            fn (array $e) => \sprintf('%s — %s', $e['numeroDecision'], $e['personnel']),
        );
        $nbNotifiees += (int) $this->notifierRubrique(
            $this->echeanceRhService->calculerDelegations(),
            self::ROLES_PAR_RUBRIQUE['delegations'],
            '/delegations',
            'délégation(s)',
            fn (array $e) => \sprintf('%s — %s → %s', $e['role'], $e['delegant'], $e['delegataire']),
        );
        $nbNotifiees += (int) $this->notifierStructureIncomplete();

        if (0 === $nbNotifiees) {
            $io->success('Aucune échéance RH en retard ou proche — rien à notifier.');
        } else {
            $io->success(\sprintf('%d rubrique(s) RH notifiée(s).', $nbNotifiees));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>} $echeances
     * @param string[]                                                                         $roles
     * @param callable(array<string, mixed>): string                                           $formatLigne
     */
    private function notifierRubrique(array $echeances, array $roles, string $lien, string $libellePluriel, callable $formatLigne): bool
    {
        $enRetard = $echeances['enRetard'];
        $aVenir = $echeances['aVenir'];

        if ([] === $enRetard && [] === $aVenir) {
            return false;
        }

        $lignes = [];
        foreach ($enRetard as $entree) {
            $lignes[] = \sprintf('%s — en retard de %d j', $formatLigne($entree), $entree['jours']);
        }
        foreach ($aVenir as $entree) {
            $lignes[] = \sprintf('%s — dans %d j', $formatLigne($entree), $entree['jours']);
        }

        $titre = \sprintf('%d %s arrivant à échéance', \count($lignes), $libellePluriel);

        $this->notificationService->notifierRoles($roles, $titre, $lien, implode("\n", $lignes));

        return true;
    }

    /** Complétude de l'organigramme (voir ServiceRepository::findSansResponsable()/DirectionRepository::findSansDirecteur()) — pas une échéance datée, notifiée à part. */
    private function notifierStructureIncomplete(): bool
    {
        $services = $this->serviceRepository->findSansResponsable();
        $directions = $this->directionRepository->findSansDirecteur();

        if ([] === $services && [] === $directions) {
            return false;
        }

        $lignes = [];
        foreach ($services as $service) {
            $lignes[] = \sprintf('Service "%s" sans responsable', $service->getNom());
        }
        foreach ($directions as $direction) {
            $lignes[] = \sprintf('Direction "%s" sans directeur', $direction->getNom());
        }

        $titre = \sprintf('%d élément(s) de l\'organigramme incomplet(s)', \count($lignes));

        $this->notificationService->notifierRoles([User::ROLE_RH_RESPONSABLE], $titre, '/dashboard', implode("\n", $lignes));

        return true;
    }
}
