<?php

namespace App\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

/**
 * Stocke sur un serveur SFTP distant tous les fichiers uploadés/générés par
 * l'application (photos, pièces justificatives, cartes professionnelles), sous
 * un nom de fichier aléatoire (jamais le nom d'origine, protection path-traversal
 * et anti-écrasement). Servi ensuite par une action de contrôleur authentifiée.
 */
class FileStorage
{
    private readonly FilesystemOperator $filesystem;

    public function __construct(
        #[Autowire('%env(SFTP_HOST)%')] string $host,
        #[Autowire('%env(int:SFTP_PORT)%')] int $port,
        #[Autowire('%env(SFTP_USERNAME)%')] string $username,
        #[Autowire('%env(SFTP_PASSWORD)%')] string $password,
        #[Autowire('%env(SFTP_PRIVATE_KEY)%')] string $privateKey,
        #[Autowire('%env(SFTP_PRIVATE_KEY_PASSPHRASE)%')] string $privateKeyPassphrase,
        #[Autowire('%env(SFTP_ROOT)%')] string $root,
    ) {
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
}
