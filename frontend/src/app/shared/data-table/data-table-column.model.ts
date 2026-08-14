/**
 * Définition d'une colonne pour <app-data-table>. Le rendu de la cellule
 * reste toujours à la charge de la page consommatrice (via
 * <ng-template appDataTableCell="key">) — cette interface ne porte que ce
 * dont le tableau générique a besoin pour trier, filtrer et proposer la
 * colonne dans le menu "Colonnes".
 */
export interface DataTableColumn<T> {
  /** Doit correspondre au `appDataTableCell` du template de cellule associé. */
  key: string;
  label: string;
  /**
   * Valeur brute (indépendante du rendu) utilisée pour le tri et, sauf
   * `searchable: false`, pour la recherche texte. Absente pour une colonne
   * qui n'est ni triable ni recherchable (ex. "Actions").
   */
  value?: (row: T) => string | number | Date | null | undefined;
  sortable?: boolean;
  /** Par défaut : recherchable dès que `value` est fourni. */
  searchable?: boolean;
  align?: 'end';
  /** Colonne décochée par défaut dans le menu "Colonnes" (reste triable/recherchable). */
  hiddenByDefault?: boolean;
  /** Toujours affichée, absente du menu "Colonnes" (ex. Actions). */
  alwaysVisible?: boolean;
}
