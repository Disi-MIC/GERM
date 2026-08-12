<?php

namespace App\Entity\Enum;

/**
 * Niveau d'intervention du ticket (support à paliers, comme GLPI/ITIL) :
 * N1 déclenché à la création, N2/N3 atteints par escalade fonctionnelle
 * (TicketIncidentController::escalader(), voir TicketEscalade pour
 * l'historique) quand le niveau courant manque de compétence/autorité pour
 * traiter l'incident — distinct d'un refus, qui ferme le ticket. N3 est le
 * palier maximal, porté par le responsable informatique (seul rôle "expert"
 * de ce domaine). Tous les niveaux passent par le responsable pour la
 * répartition (modèle Service Desk, voir TicketIncidentController::assigner()) :
 * le niveau ne détermine pas qui reçoit le ticket, seulement sa complexité.
 */
enum NiveauTicket: string
{
    case N1 = 'n1';
    case N2 = 'n2';
    case N3 = 'n3';

    public function label(): string
    {
        return match ($this) {
            self::N1 => 'Niveau 1 (support)',
            self::N2 => 'Niveau 2 (technique)',
            self::N3 => 'Niveau 3 (expertise)',
        };
    }

    public function suivant(): ?self
    {
        return match ($this) {
            self::N1 => self::N2,
            self::N2 => self::N3,
            self::N3 => null,
        };
    }
}
