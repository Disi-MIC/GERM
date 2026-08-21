<?php

namespace App\Entity\Enum;

/**
 * Distingue les deux bases légales du congé administratif — Loi 61-33 du
 * 15/06/1961 pour les fonctionnaires, Décret 74-347 du 12/04/1974 pour les
 * non-fonctionnaires (voir EligibiliteDecisionCongeService) — dont les
 * paramètres (jours acquis par mois, plafond, délai d'éligibilité) peuvent
 * être réglés indépendamment (ParametreEligibiliteConge). Une seule ligne de
 * paramètres par cas — quel que soit le contenu exact de la liste de valeurs
 * "type de contrat" (fonctionnaire/contractuel/stagiaire/consultant...),
 * seul le code littéral "fonctionnaire" de Personnel::getTypeContrat() vaut
 * FONCTIONNAIRE, tout le reste (y compris aucun type de contrat renseigné)
 * vaut NON_FONCTIONNAIRE — voir EligibiliteDecisionCongeService::categorieDe().
 */
enum CategorieAgentConge: string
{
    case FONCTIONNAIRE = 'fonctionnaire';
    case NON_FONCTIONNAIRE = 'non_fonctionnaire';

    public function label(): string
    {
        return match ($this) {
            self::FONCTIONNAIRE => 'Fonctionnaires',
            self::NON_FONCTIONNAIRE => 'Non-fonctionnaires',
        };
    }
}
