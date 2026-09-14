<?php

namespace App\Entity\Enum;

/**
 * Types d'événements journalisés par AuditLog — socle minimal (§25-26 de
 * l'audit OWASP) : connexions, échecs d'accès, exports, validations
 * métier sensibles et gestion des comptes. Volontairement plus restreint
 * que la liste complète du cahier des charges (qui couvre aussi les
 * créations/suppressions sur chaque entité RH) : à étendre au fil des
 * besoins réels plutôt que journaliser par anticipation ce qui n'est
 * jamais consulté.
 */
enum ActionAudit: string
{
    case LOGIN_REUSSI = 'login_reussi';
    case LOGIN_ECHEC = 'login_echec';
    case LOGOUT = 'logout';
    case ACCES_REFUSE = 'acces_refuse';
    case EXPORT_CREE = 'export_cree';
    case CARTE_VALIDEE = 'carte_validee';
    case DECISION_VALIDEE = 'decision_validee';
    case DECISION_REJETEE = 'decision_rejetee';
    case UTILISATEUR_CREE = 'utilisateur_cree';
    case UTILISATEUR_MODIFIE = 'utilisateur_modifie';
    case ROLES_MODIFIES = 'roles_modifies';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN_REUSSI => 'Connexion réussie',
            self::LOGIN_ECHEC => 'Échec de connexion',
            self::LOGOUT => 'Déconnexion',
            self::ACCES_REFUSE => 'Accès refusé',
            self::EXPORT_CREE => 'Export généré',
            self::CARTE_VALIDEE => 'Carte professionnelle validée',
            self::DECISION_VALIDEE => 'Décision de congé validée',
            self::DECISION_REJETEE => 'Décision de congé rejetée',
            self::UTILISATEUR_CREE => 'Compte utilisateur créé',
            self::UTILISATEUR_MODIFIE => 'Compte utilisateur modifié',
            self::ROLES_MODIFIES => 'Rôles modifiés',
        };
    }
}
