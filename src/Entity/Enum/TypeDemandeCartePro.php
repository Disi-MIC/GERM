<?php

namespace App\Entity\Enum;

enum TypeDemandeCartePro: string
{
    case NOUVELLE = 'nouvelle';
    case RENOUVELLEMENT = 'renouvellement';
    case PERTE_VOL = 'perte_vol';

    public function label(): string
    {
        return match ($this) {
            self::NOUVELLE => 'Nouvelle carte',
            self::RENOUVELLEMENT => 'Renouvellement',
            self::PERTE_VOL => 'Perte ou vol',
        };
    }
}
