<?php

namespace App\Entity\Enum;

/**
 * Types d'événements journalisés par AuditLog. Socle initial (§25-26 de
 * l'audit OWASP) : connexions, échecs d'accès, exports, validations
 * métier sensibles et gestion des comptes. Étendu le 17/09/2026 (GERM-018)
 * aux téléchargements de document et aux créations/suppressions sur les
 * entités RH (voir RhEntityAuditListener) — la couverture complète encore
 * ouverte (§25-27) concerne le volet SIEM, hors périmètre depuis la
 * décision du 15/09/2026 (voir OWASP-GERM-AUDIT.md).
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
    case UTILISATEUR_SUPPRIME = 'utilisateur_supprime';
    case ROLES_MODIFIES = 'roles_modifies';
    case DOCUMENT_TELECHARGE = 'document_telecharge';
    case ENTITE_CREEE = 'entite_creee';
    case ENTITE_SUPPRIMEE = 'entite_supprimee';

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
            self::UTILISATEUR_SUPPRIME => 'Compte utilisateur supprimé',
            self::ROLES_MODIFIES => 'Rôles modifiés',
            self::DOCUMENT_TELECHARGE => 'Document téléchargé ou consulté',
            self::ENTITE_CREEE => 'Enregistrement créé',
            self::ENTITE_SUPPRIMEE => 'Enregistrement supprimé',
        };
    }
}
