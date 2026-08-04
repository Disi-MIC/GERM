<?php

namespace App\Import;

final readonly class ImportReport
{
    /**
     * @param ImportRowResult[] $rowResults Uniquement les lignes ignorées (doublon) ou en erreur
     */
    public function __construct(
        public int $created,
        public int $skippedExisting,
        public int $errors,
        public array $rowResults,
        public ?string $globalError = null,
    ) {
    }
}
