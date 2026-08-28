<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\MaterielInformatiqueRepository;
use App\Service\EcheanceMaintenanceService;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Alerte proactive du parc informatique : sans elle, une échéance de
 * maintenance (EcheanceMaintenanceService, à partir de
 * MaterielInformatique::periodiciteMois) ou un matériel jamais évalué en
 * niveau de vulnérabilité ne se voient que si quelqu'un va consulter le
 * tableau de bord Informatique. Pensée pour un cron quotidien (voir
 * déploiement) — un seul message groupé par exécution plutôt qu'un par
 * matériel, pour ne pas noyer la cloche de notifications ; se répète tant
 * que rien n'est résolu (pas d'état "déjà notifié" à ce stade, volontairement
 * simple).
 */
#[AsCommand(
    name: 'app:notifier-alertes-parc-informatique',
    description: 'Notifie IT Stock/Responsable des échéances de maintenance et des matériels jamais évalués en vulnérabilité.',
)]
class NotifierAlertesParcInformatiqueCommand extends Command
{
    private const FENETRE_JOURS = 7;
    private const ROLES_DESTINATAIRES = [User::ROLE_IT_STOCK, User::ROLE_IT_RESPONSABLE];

    public function __construct(
        private readonly EcheanceMaintenanceService $echeanceMaintenanceService,
        private readonly MaterielInformatiqueRepository $materielRepository,
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
        $sansEvaluation = $this->materielRepository->findSansNiveauVulnerabilite();

        if ([] === $enRetard && [] === $aVenir && [] === $sansEvaluation) {
            $io->success('Aucune échéance de maintenance ni matériel non évalué — rien à notifier.');

            return Command::SUCCESS;
        }

        $lignes = [];
        foreach ($enRetard as $entree) {
            $lignes[] = \sprintf('%s %s (%s) — maintenance en retard de %d j', $entree['marque'], $entree['modele'], $entree['numeroInventaire'], $entree['jours']);
        }
        foreach ($aVenir as $entree) {
            $lignes[] = \sprintf('%s %s (%s) — maintenance dans %d j', $entree['marque'], $entree['modele'], $entree['numeroInventaire'], $entree['jours']);
        }
        foreach ($sansEvaluation as $materiel) {
            $lignes[] = \sprintf('%s %s (%s) — vulnérabilité jamais évaluée', $materiel->getMarque(), $materiel->getModele(), $materiel->getNumeroInventaire());
        }

        $titre = \sprintf(
            'Alertes parc informatique : %d maintenance(s), %d matériel(s) non évalué(s)',
            \count($enRetard) + \count($aVenir),
            \count($sansEvaluation),
        );

        $this->notificationService->notifierRoles(
            self::ROLES_DESTINATAIRES,
            $titre,
            '/dashboard-informatique',
            implode("\n", $lignes),
        );

        $io->success(\sprintf(
            '%d matériel(s) signalé(s) (%d en retard, %d à venir, %d non évalué(s)).',
            \count($lignes),
            \count($enRetard),
            \count($aVenir),
            \count($sansEvaluation),
        ));

        return Command::SUCCESS;
    }
}
