<?php

namespace App\Entity\Enum;

/**
 * Statut propre à DemandeCartePro (distinct du StatutDemande partagé par les
 * demandes de congé) : le workflow comporte une étape intermédiaire de
 * transmission — le RH Carte Pro ne peut que transmettre ou rejeter, seul le
 * RH Admin peut approuver (ce qui crée ET valide la carte en une fois).
 */
enum StatutDemandeCartePro: string
{
    case EN_ATTENTE = 'en_attente';
    case TRANSMISE = 'transmise';
    case APPROUVEE = 'approuvee';
    case REFUSEE = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::TRANSMISE => 'Transmise au RH Admin',
            self::APPROUVEE => 'Approuvée',
            self::REFUSEE => 'Refusée',
        };
    }
}
