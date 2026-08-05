<?php

namespace App\Entity\Enum;

/**
 * Statut partagé par les demandes de décision et de jouissance de congé.
 */
enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVEE = 'approuvee';
    case REFUSEE = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::APPROUVEE => 'Approuvée',
            self::REFUSEE => 'Refusée',
        };
    }
}
