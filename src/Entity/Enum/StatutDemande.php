<?php

namespace App\Entity\Enum;

/**
 * Statut partagé par les demandes de décision et de jouissance de congé.
 * VALIDEE, DEPOSEE_COURRIER, RETOURNEE et TRANSMISE_AGENT ne sont utilisés
 * que par DemandeDecision (circuit à cinq étapes, voir
 * Api/DemandeDecisionController et Api/MeDemandesController) :
 *
 *  1. RH Congé vérifie les pièces et valide (VALIDEE) ou rejette ;
 *  2. l'agent, une fois notifié, dépose physiquement au service courrier et
 *     le confirme dans l'application (DEPOSEE_COURRIER) ;
 *  3. circuit ministériel (courrier → SG → Ministre → DAGE), entièrement
 *     hors application ;
 *  4. le RH Admin réceptionne le retour, scanne/dépose les documents et les
 *     rend visibles au RH Congé (RETOURNEE) ;
 *  5. le RH Congé calcule le nombre de jours, génère la DecisionConge et
 *     transmet à l'agent, physiquement et dans l'application
 *     (TRANSMISE_AGENT).
 *
 * APPROUVEE reste utilisé par DemandeJouissance seule (trois états :
 * en_attente/approuvee/refusee), qui ne produit jamais les quatre autres —
 * gardés dans l'enum partagé plutôt qu'un type dédié pour ne pas dupliquer
 * DemandeDecisionRepository/DemandeJouissanceRepository ni le contrôleur
 * Twig historique (Controller/Admin/DemandeDecisionController), qui
 * référencent tous StatutDemande directement.
 */
enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDEE = 'validee';
    case DEPOSEE_COURRIER = 'deposee_courrier';
    case RETOURNEE = 'retournee';
    case APPROUVEE = 'approuvee';
    case REFUSEE = 'refusee';
    case TRANSMISE_AGENT = 'transmise_agent';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validée, à déposer au courrier',
            self::DEPOSEE_COURRIER => 'Déposée au service courrier',
            self::RETOURNEE => 'Revenue du circuit, transmise au RH Congé',
            self::APPROUVEE => 'Approuvée',
            self::REFUSEE => 'Refusée',
            self::TRANSMISE_AGENT => "Transmise à l'agent",
        };
    }
}
