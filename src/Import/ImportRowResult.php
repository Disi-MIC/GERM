<?php

namespace App\Import;

final readonly class ImportRowResult
{
    public function __construct(
        public int $lineNumber,
        public ImportRowStatus $status,
        public ?string $message = null,
    ) {
    }
}
