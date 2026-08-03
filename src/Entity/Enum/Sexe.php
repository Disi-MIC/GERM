<?php

namespace App\Entity\Enum;

enum Sexe: string
{
    case HOMME = 'M';
    case FEMME = 'F';

    public function label(): string
    {
        return match ($this) {
            self::HOMME => 'Homme',
            self::FEMME => 'Femme',
        };
    }
}
