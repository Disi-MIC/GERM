<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem as RemoteFilesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Filet de sécurité minimal face à GERM-027 (aucun mécanisme de sauvegarde
 * ne préexistait pour `germ_db`, confirmé par le commanditaire le
 * 15/09/2026) : un dump `mysqldump` quotidien, compressé, chiffré, avec
 * rotation, stocké localement sous `var/backups/` **et** copié vers le
 * serveur SFTP de stockage de fichiers (10.112.26.31, déjà utilisé par
 * FileStorage) sous `db-backups/` — une machine distincte de celle qui
 * héberge `germ_db`, pour qu'une panne disque ou la destruction du serveur
 * applicatif n'emporte pas aussi les sauvegardes.
 *
 * Connexion SFTP dédiée plutôt que réutilisation de FileStorage : ce
 * dernier applique une validation (types MIME, 10 Mo) et un chiffrement
 * (FileEncryptionService, sa propre clé) pensés pour des fichiers
 * utilisateur, pas pour un dump déjà chiffré par ce service avec sa propre
 * clé — un second chiffrement par-dessus n'apporterait rien.
 *
 * Portée résiduelle assumée : cette copie distante suppose que
 * 10.112.26.31 ne partage pas la même infrastructure physique
 * (alimentation, baie) que germ-appdbserver — non vérifié depuis ces deux
 * machines, à confirmer auprès de qui gère l'infrastructure physique.
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
    private const DOSSIER_DISTANT = 'db-backups';

    private readonly ?FilesystemOperator $distant;

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(base64:DB_BACKUP_ENCRYPTION_KEY)%')] private readonly string $secretKey,
        #[Autowire('%kernel.project_dir%/var/backups')] private readonly string $backupDir,
        #[Autowire('%env(bool:FILE_STORAGE_LOCAL)%')] bool $local,
        #[Autowire('%env(SFTP_HOST)%')] string $host,
        #[Autowire('%env(int:SFTP_PORT)%')] int $port,
        #[Autowire('%env(SFTP_USERNAME)%')] string $username,
        #[Autowire('%env(SFTP_PASSWORD)%')] string $password,
        #[Autowire('%env(SFTP_PRIVATE_KEY)%')] string $privateKey,
        #[Autowire('%env(SFTP_PRIVATE_KEY_PASSPHRASE)%')] string $privateKeyPassphrase,
        #[Autowire('%env(SFTP_ROOT)%')] string $root,
    ) {
        // En dev délocalisé (FILE_STORAGE_LOCAL=true), le SFTP réel n'est
        // pas joignable — copie distante désactivée plutôt que de bloquer
        // 30s (max_execution_time) sur chaque sauvegarde, voir FileStorage.
        // Ne jamais pousser un dump de dev vers le SFTP de production.
        if ($local) {
            $this->distant = null;

            return;
        }

        $provider = new SftpConnectionProvider(
            host: $host,
            username: $username,
            password: '' !== $password ? $password : null,
            privateKey: '' !== $privateKey ? $privateKey : null,
            passphrase: '' !== $privateKeyPassphrase ? $privateKeyPassphrase : null,
            port: $port,
        );
        $this->distant = new RemoteFilesystem(new SftpAdapter($provider, $root));
    }

    /** @return string Chemin local du fichier de sauvegarde créé. */
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

        $nomFichier = \sprintf('germ_db_%s.sql.gz.enc', (new \DateTimeImmutable())->format('Ymd_His'));
        $chemin = \sprintf('%s/%s', $this->backupDir, $nomFichier);
        $filesystem->dumpFile($chemin, $chiffre);

        $this->purgerAnciennesSauvegardes();

        if (null !== $this->distant) {
            $this->distant->write(self::DOSSIER_DISTANT.'/'.$nomFichier, $chiffre);
            $this->purgerAnciennesSauvegardesDistantes();
        }

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

    private function purgerAnciennesSauvegardesDistantes(): void
    {
        // Tri par nom de fichier (horodatage inclus, format Ymd_His) plutôt
        // que par date de modification distante, non garantie fiable selon
        // le serveur SFTP.
        $fichiers = $this->distant->listContents(self::DOSSIER_DISTANT)
            ->filter(static fn (StorageAttributes $attributes) => $attributes->isFile())
            ->toArray();

        usort($fichiers, static fn (StorageAttributes $a, StorageAttributes $b) => $b->path() <=> $a->path());

        foreach (\array_slice($fichiers, self::NOMBRE_SAUVEGARDES_CONSERVEES) as $ancien) {
            $this->distant->delete($ancien->path());
        }
    }
}
