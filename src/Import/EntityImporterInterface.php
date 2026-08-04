<?php

namespace App\Import;

interface EntityImporterInterface
{
    public function getSupportedType(): TypeImport;

    /**
     * @return ImportColumnDefinition[]
     */
    public function getColumns(): array;

    /**
     * Traite une ligne du fichier importé et persiste l'entité correspondante en cas de succès.
     * Ne flush jamais : la gestion du flush (par lot) revient à l'appelant (ImportRunner).
     *
     * @param array<string, ?string> $row Valeurs indexées par nom de colonne normalisé
     */
    public function importRow(array $row, int $lineNumber): ImportRowResult;
}
