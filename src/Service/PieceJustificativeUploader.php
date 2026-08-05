<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stocke les pièces justificatives (décisions/jouissances de congé) hors de public/,
 * sous un nom de fichier aléatoire (jamais le nom d'origine, protection path-traversal
 * et anti-écrasement). Servi ensuite par une action de contrôleur authentifiée.
 */
class PieceJustificativeUploader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/pieces-justificatives')]
        private readonly string $baseDir,
    ) {
    }

    /**
     * @return array{path: string, originalName: string}
     */
    public function store(UploadedFile $file, string $sousDossier): array
    {
        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $file->move($this->baseDir.'/'.$sousDossier, $filename);

        return [
            'path' => $sousDossier.'/'.$filename,
            'originalName' => $file->getClientOriginalName(),
        ];
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->baseDir.'/'.$relativePath;
    }

    public function delete(string $relativePath): void
    {
        @unlink($this->absolutePath($relativePath));
    }
}
