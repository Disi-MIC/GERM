<?php

namespace App\Entity\Enum;

enum CategorieListeValeur: string
{
    case TYPE_MATERIEL = 'type-materiel';
    case TYPE_VEHICULE = 'type-vehicule';
    case ETAT_MATERIEL = 'etat-materiel';
    case ETAT_VEHICULE = 'etat-vehicule';
    case TYPE_CONTRAT = 'type-contrat';

    public function label(): string
    {
        return match ($this) {
            self::TYPE_MATERIEL => 'Types de matériel informatique',
            self::TYPE_VEHICULE => 'Types de véhicule',
            self::ETAT_MATERIEL => 'États du matériel informatique',
            self::ETAT_VEHICULE => 'États des véhicules',
            self::TYPE_CONTRAT => 'Types de contrat',
        };
    }
}
