<?php

namespace App\Entity\Enum;

enum Carburant: string
{
    case ESSENCE = 'essence';
    case DIESEL = 'diesel';
    case ELECTRIQUE = 'electrique';
    case HYBRIDE = 'hybride';

    public function label(): string
    {
        return match ($this) {
            self::ESSENCE => 'Essence',
            self::DIESEL => 'Diesel',
            self::ELECTRIQUE => 'Électrique',
            self::HYBRIDE => 'Hybride',
        };
    }
}
