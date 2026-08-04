<?php

namespace App\Import;

final readonly class ImportColumnDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $required,
        public ?string $example = null,
    ) {
    }
}
