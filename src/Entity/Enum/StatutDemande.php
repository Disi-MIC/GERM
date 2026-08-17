<?php

namespace App\Entity\Enum;

/**
 * Statut partagé par les demandes de décision et de jouissance de congé.
 * TRANSMISE, APPROUVEE, RETOURNEE et TRANSMISE_AGENT ne sont utilisés que par
 * DemandeDecision (circuit à cinq étapes, voir Api/DemandeDecisionController) :
 * RH Congé transmet → RH Admin approuve (déclenche impression/courrier/
 * signature de l'autorité, hors application) → RH Admin vérifie le retour du
 * papier signé et le transmet au RH Congé (RETOURNEE) → RH Congé remet à
 * l'agent, physiquement et dans l'application (TRANSMISE_AGENT).
 * DemandeJouissance reste à trois états (en_attente/approuvee/refusee) et ne
 * produit jamais les quatre autres — gardés dans l'enum partagé plutôt qu'un
 * type dédié pour ne pas dupliquer DemandeDecisionRepository/
 * DemandeJouissanceRepository ni le contrôleur Twig historique
 * (Controller/Admin/DemandeDecisionController), qui référencent tous
 * StatutDemande directement.
 */
enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case TRANSMISE = 'transmise';
    case APPROUVEE = 'approuvee';
    case RETOURNEE = 'retournee';
    case REFUSEE = 'refusee';
    case TRANSMISE_AGENT = 'transmise_agent';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::TRANSMISE => 'Transmise au RH Admin',
            self::APPROUVEE => 'Approuvée, en circuit de signature',
            self::RETOURNEE => 'Signée, transmise au RH Congé',
            self::REFUSEE => 'Refusée',
            self::TRANSMISE_AGENT => "Transmise à l'agent",
        };
    }
}
