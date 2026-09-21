<?php

namespace App\Import;

enum TypeImport: string
{
    case DIRECTION = 'direction';
    case SERVICE = 'service';
    case PERSONNEL = 'personnel';
    case MATERIEL = 'materiel';
    case VEHICULE = 'vehicule';
    case CORPS = 'corps';
    case HIERARCHIE = 'hierarchie';
    case PROGRAMME = 'programme';
    case CHAPITRE = 'chapitre';

    public function label(): string
    {
        return match ($this) {
            self::DIRECTION => 'Directions',
            self::SERVICE => 'Services',
            self::PERSONNEL => 'Personnel',
            self::MATERIEL => 'Parc informatique',
            self::VEHICULE => 'Parc automobile',
            self::CORPS => 'Corps',
            self::HIERARCHIE => 'Hiérarchie',
            self::PROGRAMME => 'Programmes budgétaires',
            self::CHAPITRE => 'Chapitres budgétaires',
        };
    }

    public function indexRoute(): string
    {
        return match ($this) {
            self::DIRECTION => 'admin_direction_index',
            self::SERVICE => 'admin_service_index',
            self::PERSONNEL => 'admin_personnel_index',
            self::MATERIEL => 'admin_materiel_index',
            self::VEHICULE => 'admin_vehicule_index',
            self::CORPS, self::HIERARCHIE, self::PROGRAMME, self::CHAPITRE => 'admin_liste_valeur_index',
        };
    }

    /**
     * Paramètres additionnels à fournir à indexRoute() — vide pour les cinq
     * types historiques (route sans paramètre), {categorie: ...} pour les
     * quatre listes paramétrables qui partagent toutes admin_liste_valeur_index.
     *
     * @return array<string, string>
     */
    public function indexRouteParams(): array
    {
        return match ($this) {
            self::CORPS, self::HIERARCHIE, self::PROGRAMME, self::CHAPITRE => ['categorie' => $this->value],
            default => [],
        };
    }
}
