<?php

namespace App\Entity\Enum;

enum TypeConge: string
{
    case ANNUEL = 'annuel';
    case MALADIE = 'maladie';
    case MATERNITE_PATERNITE = 'maternite_paternite';
    case SANS_SOLDE = 'sans_solde';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ANNUEL => 'Congé annuel',
            self::MALADIE => 'Congé maladie',
            self::MATERNITE_PATERNITE => 'Congé maternité / paternité',
            self::SANS_SOLDE => 'Congé sans solde',
            self::AUTRE => 'Autre',
        };
    }
}
