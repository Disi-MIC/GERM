<?php

namespace App\Entity\Enum;

/**
 * Rôles pouvant faire l'objet d'une délégation temporaire. Volontairement
 * limité aux rôles RH : ROLE_SUPERADMIN/ROLE_ADMIN ne sont jamais délégables
 * (garde-fou anti-élévation de privilège assuré par le typage lui-même).
 * Les valeurs sont exactement les constantes de rôle de App\Entity\User.
 */
enum RoleDelegable: string
{
    case ADMIN_RH = 'ROLE_ADMIN_RH';
    case RH_PERSONNEL = 'ROLE_RH_PERSONNEL';
    case RH_CONGE = 'ROLE_RH_CONGE';
    case RH_CARTE_PRO = 'ROLE_RH_CARTE_PRO';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_RH => 'Administrateur RH (accès global)',
            self::RH_PERSONNEL => 'Gestion du personnel',
            self::RH_CONGE => 'Gestion des congés',
            self::RH_CARTE_PRO => 'Gestion des cartes professionnelles',
        };
    }
}
