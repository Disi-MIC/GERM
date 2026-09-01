<?php

namespace App\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

/**
 * Stocke tous les fichiers uploadés/générés par l'application (photos, pièces
 * justificatives, cartes professionnelles), sous un nom de fichier aléatoire
 * (jamais le nom d'origine, protection path-traversal et anti-écrasement).
 * Servi ensuite par une action de contrôleur authentifiée.
 *
 * En production, stockage sur un serveur SFTP distant (réseau interne du
 * ministère). FILE_STORAGE_LOCAL (voir when@dev dans services.yaml) bascule
 * sur le disque local du poste de dev : ce serveur SFTP n'est joignable que
 * depuis le réseau interne, injoignable en dev délocalisé — sans ce repli, le
 * moindre upload/lecture de fichier (photo, PDF de carte pro) reste bloqué 30
 * secondes (max_execution_time) avant d'échouer, sans lien avec le code
 * appelant. La prod n'est jamais concernée : FILE_STORAGE_LOCAL vaut false
 * par défaut (voir .env) et n'est mis à true que dans .env.local (non versionné).
 */
class FileStorage
{
    /**
     * Limite applicative (indépendante de upload_max_filesize/post_max_size de
     * PHP, qu'on ne peut pas changer à l'exécution) — voir erreurValidation().
     */
    public const TAILLE_MAX_OCTETS = 10 * 1024 * 1024;

    /**
     * Types réellement utilisés dans l'application, tous appels confondus :
     * photos (agent, matériel) et pièces jointes/documents (PDF, éventuellement
     * scannés en image). `getMimeType()` sniffe le contenu binaire réel du
     * fichier (fileinfo), pas l'en-tête Content-Type envoyé par le client
     * (falsifiable) ni l'extension du nom d'origine — voir erreurValidation().
     */
    private const TYPES_MIME_AUTORISES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    private readonly FilesystemOperator $filesystem;

    public function __construct(
        #[Autowire('%env(bool:FILE_STORAGE_LOCAL)%')] bool $local,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(SFTP_HOST)%')] string $host,
        #[Autowire('%env(int:SFTP_PORT)%')] int $port,
        #[Autowire('%env(SFTP_USERNAME)%')] string $username,
        #[Autowire('%env(SFTP_PASSWORD)%')] string $password,
        #[Autowire('%env(SFTP_PRIVATE_KEY)%')] string $privateKey,
        #[Autowire('%env(SFTP_PRIVATE_KEY_PASSPHRASE)%')] string $privateKeyPassphrase,
        #[Autowire('%env(SFTP_ROOT)%')] string $root,
    ) {
        if ($local) {
            $this->filesystem = new Filesystem(new LocalFilesystemAdapter($projectDir.'/var/uploads'));

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

        $this->filesystem = new Filesystem(new SftpAdapter($provider, $root));
    }

    /**
     * Message d'erreur à renvoyer au client si le fichier reçu n'est pas
     * utilisable, ou null s'il est valide — couvre les trois cas possibles :
     * absent, rejeté par PHP avant même d'atteindre Symfony (upload_max_filesize/
     * post_max_size, qui ne peuvent pas être changés à l'exécution — d'où une
     * limite applicative distincte, forcément inférieure), ou simplement trop
     * volumineux au sens de cette limite applicative. Centralisé ici pour que
     * chaque contrôleur d'upload applique la même règle avec le même message,
     * plutôt qu'un simple contrôle de présence dupliqué partout.
     */
    public function erreurValidation(?UploadedFile $file): ?string
    {
        if (!$file instanceof UploadedFile) {
            return 'Aucun fichier reçu.';
        }

        if (!$file->isValid()) {
            return match ($file->getError()) {
                \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => \sprintf(
                    'Le fichier dépasse la taille maximale autorisée par le serveur (%d Mo).',
                    (int) (self::TAILLE_MAX_OCTETS / 1024 / 1024),
                ),
                default => "Le fichier n'a pas pu être reçu, merci de réessayer.",
            };
        }

        if ($file->getSize() > self::TAILLE_MAX_OCTETS) {
            return \sprintf('Le fichier dépasse la taille maximale autorisée (%d Mo).', (int) (self::TAILLE_MAX_OCTETS / 1024 / 1024));
        }

        if (!\in_array($file->getMimeType(), self::TYPES_MIME_AUTORISES, true)) {
            return 'Type de fichier non autorisé (formats acceptés : PDF, JPEG, PNG, WebP).';
        }

        return null;
    }

    /**
     * @return array{path: string, originalName: string}
     */
    public function store(UploadedFile $file, string $sousDossier): array
    {
        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $path = $sousDossier.'/'.$filename;

        $stream = fopen($file->getPathname(), 'r');
        $this->filesystem->writeStream($path, $stream);
        if (\is_resource($stream)) {
            fclose($stream);
        }

        return [
            'path' => $path,
            'originalName' => $file->getClientOriginalName(),
        ];
    }

    /**
     * @return array{path: string, originalName: string}
     */
    public function storeContent(string $binary, string $originalName, string $extension, string $sousDossier): array
    {
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $path = $sousDossier.'/'.$filename;

        $this->filesystem->write($path, $binary);

        return [
            'path' => $path,
            'originalName' => $originalName,
        ];
    }

    /**
     * @return resource
     */
    public function readStream(string $relativePath)
    {
        return $this->filesystem->readStream($relativePath);
    }

    public function mimeType(string $relativePath): string
    {
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream';
    }

    public function delete(string $relativePath): void
    {
        $this->filesystem->delete($relativePath);
    }

    /**
     * Nom de fichier proposé au téléchargement (Content-Disposition) : les
     * fichiers liés à un agent (carte pro, pièces justificatives, documents
     * administratifs) sont stockés sous un nom aléatoire (voir store()), mais
     * doivent être proposés au téléchargement sous un nom lisible incluant le
     * nom de l'agent plutôt que le nom du fichier scanné d'origine — plus
     * facile à retrouver une fois enregistré sur le poste de l'utilisateur.
     * Translittère les accents plutôt que de compter sur l'encodage RFC 6266
     * du nom de fichier, pour éviter toute variation de rendu selon le
     * navigateur.
     */
    public function nomTelechargement(string $descriptif, string $extension): string
    {
        $translitere = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $descriptif) ?: $descriptif;
        $nettoye = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $translitere), '-');

        return \sprintf('%s.%s', '' !== $nettoye ? $nettoye : 'document', $extension);
    }
}
