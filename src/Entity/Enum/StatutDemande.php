<?php

namespace App\Entity\Enum;

/**
 * Statut partagé par les demandes de décision et de jouissance de congé.
 * TRANSMISE et TRANSMISE_AGENT ne sont utilisés que par DemandeDecision (circuit
 * à quatre étapes RH Congé → RH Admin → RH Congé, voir Api/DemandeDecisionController) ;
 * DemandeJouissance reste à trois états (en_attente/approuvee/refusee) et ne les
 * produit jamais — gardés dans l'enum partagé plutôt qu'un type dédié pour ne pas
 * dupliquer DemandeDecisionRepository/DemandeJouissanceRepository ni le contrôleur
 * Twig historique (Controller/Admin/DemandeDecisionController), qui référencent
 * tous StatutDemande directement.
 */
enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case TRANSMISE = 'transmise';
    case APPROUVEE = 'approuvee';
    case REFUSEE = 'refusee';
    case TRANSMISE_AGENT = 'transmise_agent';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::TRANSMISE => 'Transmise au RH Admin',
            self::APPROUVEE => 'Approuvée',
            self::REFUSEE => 'Refusée',
            self::TRANSMISE_AGENT => "Transmise à l'agent",
        };
    }
}
