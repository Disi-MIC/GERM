<?php

namespace App\Command;

use App\Service\DatabaseBackupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Destinée à un crontab (voir docs/security/deploiement/GERM-027-sauvegarde-mysql.md),
 * jamais à un déclenchement manuel en dehors d'un test — voir DatabaseBackupService
 * pour la portée assumée de cette sauvegarde (GERM-027).
 */
#[AsCommand(name: 'app:backup-database', description: 'Sauvegarde germ_db (dump chiffré, avec rotation) — GERM-027')]
final class BackupDatabaseCommand extends Command
{
    public function __construct(private readonly DatabaseBackupService $backupService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chemin = $this->backupService->sauvegarder();
        $output->writeln(\sprintf('Sauvegarde créée : %s', $chemin));

        return Command::SUCCESS;
    }
}
