<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Filet de sécurité minimal face à GERM-027 (aucun mécanisme de sauvegarde
 * ne préexistait pour `germ_db`, confirmé par le commanditaire le
 * 15/09/2026) : un dump `mysqldump` quotidien, compressé, chiffré, avec
 * rotation, stocké localement sous `var/backups/`.
 *
 * Portée assumée, à ne pas surestimer : cette sauvegarde vit sur le même
 * disque que la base qu'elle protège. Elle couvre une erreur applicative,
 * une commande destructrice ou une mauvaise migration — pas une panne
 * disque ni la destruction physique du serveur, qui emporterait les deux à
 * la fois. Une résilience réelle suppose une copie sur un support distinct,
 * hors du périmètre de ce correctif (décision d'infrastructure à part).
 *
 * Chiffrement par libsodium `secretbox`, même primitive que QrTokenService
 * et FileEncryptionService, avec une clé dédiée (`DB_BACKUP_ENCRYPTION_KEY`)
 * — jamais partagée avec les deux autres, même principe de cloisonnement.
 *
 * Le dump entier est chargé en mémoire pour être chiffré (`secretbox` ne
 * s'applique pas par blocs à un flux) : acceptable pour le volume actuel de
 * GERM, à revoir si la base grossit significativement.
 */
class DatabaseBackupService
{
    private const MAGIC = 'GRMB1';
    private const NOMBRE_SAUVEGARDES_CONSERVEES = 14;

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(base64:DB_BACKUP_ENCRYPTION_KEY)%')] private readonly string $secretKey,
        #[Autowire('%kernel.project_dir%/var/backups')] private readonly string $backupDir,
    ) {
    }

    /** @return string Chemin du fichier de sauvegarde créé. */
    public function sauvegarder(): string
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->backupDir);

        $dump = $this->dumper();
        $compresse = gzencode($dump, 9);
        if (false === $compresse) {
            throw new \RuntimeException('Échec de la compression du dump SQL.');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $chiffre = self::MAGIC.$nonce.sodium_crypto_secretbox($compresse, $nonce, $this->secretKey);

        $chemin = \sprintf('%s/germ_db_%s.sql.gz.enc', $this->backupDir, (new \DateTimeImmutable())->format('Ymd_His'));
        $filesystem->dumpFile($chemin, $chiffre);

        $this->purgerAnciennesSauvegardes();

        return $chemin;
    }

    /**
     * @return string Le dump SQL en clair, tel que renvoyé par mysqldump.
     */
    private function dumper(): string
    {
        $params = $this->connection->getParams();

        $process = new Process([
            'mysqldump',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--host='.$params['host'],
            '--port='.$params['port'],
            '--user='.$params['user'],
            $params['dbname'],
        ]);
        // Mot de passe transmis par variable d'environnement du sous-processus,
        // jamais en argument de ligne de commande (visible dans `ps aux`).
        $process->setEnv(['MYSQL_PWD' => $params['password']]);
        $process->setTimeout(300);
        $process->mustRun();

        return $process->getOutput();
    }

    private function purgerAnciennesSauvegardes(): void
    {
        $fichiers = glob($this->backupDir.'/germ_db_*.sql.gz.enc') ?: [];
        usort($fichiers, static fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach (\array_slice($fichiers, self::NOMBRE_SAUVEGARDES_CONSERVEES) as $ancien) {
            @unlink($ancien);
        }
    }
}
