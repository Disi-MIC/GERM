<?php

namespace App\Import;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileParserInterface
{
    public function supports(UploadedFile $file): bool;

    /**
     * Retourne les lignes de données (hors en-tête), indexées par leur numéro de ligne
     * 1-based dans le fichier source (l'en-tête est la ligne 1), et pour chaque ligne
     * un tableau associatif valeur-par-colonne (clé de colonne normalisée : trim + minuscule).
     *
     * @return iterable<int, array<string, ?string>>
     */
    public function parse(UploadedFile $file): iterable;
}
