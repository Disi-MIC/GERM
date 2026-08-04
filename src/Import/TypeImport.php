<?php

namespace App\Import;

enum TypeImport: string
{
    case DIRECTION = 'direction';
    case SERVICE = 'service';
    case PERSONNEL = 'personnel';
    case MATERIEL = 'materiel';
    case VEHICULE = 'vehicule';

    public function label(): string
    {
        return match ($this) {
            self::DIRECTION => 'Directions',
            self::SERVICE => 'Services',
            self::PERSONNEL => 'Personnel',
            self::MATERIEL => 'Parc informatique',
            self::VEHICULE => 'Parc automobile',
        };
    }

    public function indexRoute(): string
    {
        return match ($this) {
            self::DIRECTION => 'admin_direction_index',
            self::SERVICE => 'admin_service_index',
            self::PERSONNEL => 'admin_personnel_index',
            self::MATERIEL => 'admin_materiel_index',
            self::VEHICULE => 'admin_vehicule_index',
        };
    }
}
