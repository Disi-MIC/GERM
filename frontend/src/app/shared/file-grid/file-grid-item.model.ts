export type FileGridColor = 'primary' | 'secondary' | 'red' | 'blue' | 'green' | 'yellow' | 'purple' | 'orange';

/** Indicateur compact (icône colorée + infobulle), ex. antivirus installé/statut d'expiration. */
export interface FileGridBadge {
  /** Classe bootstrap-icons, sans le préfixe "bi-". */
  icon: string;
  /** Suffixe de classe .icon-badge-* (success, danger, secondary, info, warning) — voir styles.scss. */
  colorClass: string;
  title: string;
}

export interface FileGridItem<T> {
  row: T;
  name: string;
  meta?: string;
  /** Classe bootstrap-icons (sans le préfixe "bi-") si iconIsText est faux, sinon libellé texte affiché tel quel (ex. "PDF"). */
  icon: string;
  iconIsText?: boolean;
  color: FileGridColor;
  statusLabel?: string;
  /** Suffixe de classe .status-dot-* (success, danger, secondary, info, warning). */
  statusColor?: string;
  /** Icône bootstrap-icons (sans "bi-") affichée à la place du point devant statusLabel. */
  statusIcon?: string;
  /** Indicateurs compacts additionnels (icône seule + infobulle), ex. antivirus. */
  badges?: FileGridBadge[];
}
