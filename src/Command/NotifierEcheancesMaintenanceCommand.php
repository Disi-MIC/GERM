<?php

namespace App\Command;

use App\Entity\User;
use App\Service\EcheanceMaintenanceService;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Alerte proactive du parc informatique : sans elle, une échéance de
 * maintenance (calculée à la volée par EcheanceMaintenanceService à partir
 * de MaterielInformatique::periodiciteMois) ne se voit que si quelqu'un va
 * consulter le tableau de bord Informatique. Pensée pour un cron quotidien
 * (voir déploiement) — un seul message groupé par exécution plutôt qu'un par
 * matériel, pour ne pas noyer la cloche de notifications ; se répète tant
 * que l'échéance n'est pas résolue (pas d'état "déjà notifié" à ce stade,
 * volontairement simple).
 */
#[AsCommand(
    name: 'app:notifier-echeances-maintenance',
    description: 'Notifie le rôle IT Stock des échéances de maintenance du parc informatique en retard ou proches.',
)]
class NotifierEcheancesMaintenanceCommand extends Command
{
    private const FENETRE_JOURS = 7;
    private const ROLES_DESTINATAIRES = [User::ROLE_IT_STOCK, User::ROLE_IT_RESPONSABLE];

    public function __construct(
        private readonly EcheanceMaintenanceService $echeanceMaintenanceService,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $echeances = $this->echeanceMaintenanceService->calculer(self::FENETRE_JOURS);
        $enRetard = $echeances['enRetard'];
        $aVenir = $echeances['aVenir'];

        if ([] === $enRetard && [] === $aVenir) {
            $io->success('Aucune échéance de maintenance en retard ou proche — rien à notifier.');

            return Command::SUCCESS;
        }

        $lignes = [];
        foreach ($enRetard as $entree) {
            $lignes[] = \sprintf('%s %s (%s) — en retard de %d j', $entree['marque'], $entree['modele'], $entree['numeroInventaire'], $entree['jours']);
        }
        foreach ($aVenir as $entree) {
            $lignes[] = \sprintf('%s %s (%s) — dans %d j', $entree['marque'], $entree['modele'], $entree['numeroInventaire'], $entree['jours']);
        }

        $titre = \sprintf(
            'Maintenance parc informatique : %d en retard, %d à venir',
            \count($enRetard),
            \count($aVenir),
        );

        $this->notificationService->notifierRoles(
            self::ROLES_DESTINATAIRES,
            $titre,
            '/dashboard-informatique',
            implode("\n", $lignes),
        );

        $io->success(\sprintf('%d matériel(s) signalé(s) (%d en retard, %d à venir).', \count($lignes), \count($enRetard), \count($aVenir)));

        return Command::SUCCESS;
    }
}
