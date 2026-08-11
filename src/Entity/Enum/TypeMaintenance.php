<?php

namespace App\Entity\Enum;

enum TypeMaintenance: string
{
    case PREVENTIVE = 'preventive';
    case CORRECTIVE = 'corrective';

    public function label(): string
    {
        return match ($this) {
            self::PREVENTIVE => 'Préventive',
            self::CORRECTIVE => 'Corrective',
        };
    }
}
