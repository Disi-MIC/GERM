<?php

namespace App\Entity\Enum;

enum StatutCarteProfessionnelle: string
{
    case VALIDE = 'valide';
    case PERDUE = 'perdue';
    case VOLEE = 'volee';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::VALIDE => 'Valide',
            self::PERDUE => 'Perdue',
            self::VOLEE => 'Volée',
            self::ANNULEE => 'Annulée',
        };
    }
}
