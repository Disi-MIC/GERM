<?php

namespace App\Entity\Enum;

enum CategorieListeValeur: string
{
    case TYPE_MATERIEL = 'type-materiel';
    case TYPE_VEHICULE = 'type-vehicule';
    case ETAT_MATERIEL = 'etat-materiel';
    case ETAT_VEHICULE = 'etat-vehicule';
    case TYPE_CONTRAT = 'type-contrat';
    case TYPE_DOCUMENT = 'type-document';
    case PRIORITE_TICKET = 'priorite-ticket';
    case TYPE_MAINTENANCE = 'type-maintenance';
    case LOGICIEL_OS = 'logiciel-os';
    case LOGICIEL_ANTIVIRUS = 'logiciel-antivirus';
    case LOGICIEL_BUREAUTIQUE = 'logiciel-bureautique';
    case MOTIF_REJET_DECISION_CONGE = 'motif-rejet-decision-conge';

    public function label(): string
    {
        return match ($this) {
            self::TYPE_MATERIEL => 'Types de matériel informatique',
            self::TYPE_VEHICULE => 'Types de véhicule',
            self::ETAT_MATERIEL => 'États du matériel informatique',
            self::ETAT_VEHICULE => 'États des véhicules',
            self::TYPE_CONTRAT => 'Types de contrat',
            self::TYPE_DOCUMENT => 'Types de document administratif',
            self::PRIORITE_TICKET => 'Priorités de ticket informatique',
            self::TYPE_MAINTENANCE => 'Types de maintenance informatique',
            self::LOGICIEL_OS => 'Systèmes d\'exploitation',
            self::LOGICIEL_ANTIVIRUS => 'Antivirus',
            self::LOGICIEL_BUREAUTIQUE => 'Suites bureautiques',
            self::MOTIF_REJET_DECISION_CONGE => 'Motifs de rejet — demande de décision de congé',
        };
    }
}
