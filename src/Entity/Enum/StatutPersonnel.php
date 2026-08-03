<?php

namespace App\Entity\Enum;

enum StatutPersonnel: string
{
    case ACTIF = 'actif';
    case EN_CONGE = 'en_conge';
    case SUSPENDU = 'suspendu';
    case RETRAITE = 'retraite';
    case DEMISSIONNAIRE = 'demissionnaire';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::EN_CONGE => 'En congé',
            self::SUSPENDU => 'Suspendu',
            self::RETRAITE => 'Retraité',
            self::DEMISSIONNAIRE => 'Démissionnaire',
        };
    }
}
