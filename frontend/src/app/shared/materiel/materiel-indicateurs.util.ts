import { LicenceLogiciel } from '../../core/models/licence-logiciel.model';
import { MaterielInformatique } from '../../core/models/materiel-informatique.model';

/** Icône colorée + infobulle — voir .icon-badge / .status-dot-icon dans styles.scss. */
export interface IconIndicateur {
  icon: string;
  colorClass: string;
  title: string;
}

export const COULEURS_ETAT_MATERIEL: Record<string, string> = {
  en_service: 'success',
  en_stock: 'info',
  en_panne: 'danger',
  en_maintenance: 'warning',
  reforme: 'secondary',
};

export const ICONES_ETAT_MATERIEL: Record<string, string> = {
  en_service: 'check-circle-fill',
  en_stock: 'box-seam',
  en_panne: 'exclamation-triangle-fill',
  en_maintenance: 'tools',
  reforme: 'slash-circle',
};

/** Seuils d'alerte avant expiration de la licence antivirus, en jours. */
const SEUIL_EXPIRATION_ALERTE_JOURS = 90;
const SEUIL_EXPIRATION_CRITIQUE_JOURS = 30;

function joursAvant(dateIso: string): number {
  const cible = new Date(dateIso).getTime();
  const maintenant = new Date().setHours(0, 0, 0, 0);
  return Math.ceil((cible - maintenant) / (1000 * 60 * 60 * 24));
}

function formatDate(dateIso: string): string {
  return new Date(dateIso).toLocaleDateString('fr-FR');
}

function libelleLogiciel(licence: LicenceLogiciel): string {
  return licence.logiciel && typeof licence.logiciel !== 'string' ? licence.logiciel.libelle : '';
}

export function antivirusDe(materiel: MaterielInformatique): LicenceLogiciel | null {
  return materiel.antivirus && typeof materiel.antivirus !== 'string' ? materiel.antivirus : null;
}

/** Icônes "antivirus installé" + (si applicable) "statut d'expiration". */
export function badgesAntivirus(materiel: MaterielInformatique): IconIndicateur[] {
  const antivirus = antivirusDe(materiel);
  const badges: IconIndicateur[] = [
    antivirus
      ? { icon: 'shield-check', colorClass: 'success', title: `Antivirus installé : ${libelleLogiciel(antivirus)}` }
      : { icon: 'shield-slash', colorClass: 'secondary', title: 'Aucun antivirus installé' },
  ];

  if (antivirus?.dateExpiration) {
    badges.push(badgeExpirationAntivirus(antivirus.dateExpiration));
  }

  return badges;
}

function badgeExpirationAntivirus(dateExpiration: string): IconIndicateur {
  const jours = joursAvant(dateExpiration);
  const dateFormatee = formatDate(dateExpiration);

  if (jours < 0) {
    return { icon: 'shield-fill-x', colorClass: 'danger', title: `Antivirus expiré depuis le ${dateFormatee}` };
  }
  if (jours <= SEUIL_EXPIRATION_CRITIQUE_JOURS) {
    return { icon: 'shield-fill-exclamation', colorClass: 'danger', title: `Antivirus expire le ${dateFormatee} (dans ${jours} j)` };
  }
  if (jours <= SEUIL_EXPIRATION_ALERTE_JOURS) {
    return { icon: 'shield-fill-exclamation', colorClass: 'warning', title: `Antivirus expire le ${dateFormatee} (dans ${jours} j)` };
  }
  return { icon: 'shield-fill-check', colorClass: 'success', title: `Antivirus valide jusqu'au ${dateFormatee}` };
}
