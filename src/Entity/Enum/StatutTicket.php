<?php

namespace App\Entity\Enum;

/**
 * Workflow à deux niveaux, même logique que StatutDemandeCartePro : le
 * technicien traite (prend en charge, résout ou refuse), seul le responsable
 * informatique valide et clôt un ticket résolu — ou le rouvre s'il n'est pas
 * satisfait de la résolution proposée.
 */
enum StatutTicket: string
{
    case OUVERT = 'ouvert';
    case EN_COURS = 'en_cours';
    case RESOLU = 'resolu';
    case CLOTURE = 'cloture';
    case REFUSE = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::OUVERT => 'Ouvert',
            self::EN_COURS => 'En cours',
            self::RESOLU => 'Résolu, en attente de validation',
            self::CLOTURE => 'Clôturé',
            self::REFUSE => 'Refusé',
        };
    }
}
