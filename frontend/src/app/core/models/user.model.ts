export interface CurrentUser {
  id: number;
  email: string;
  nom: string;
  prenom: string;
  roles: string[];
  /** Id du service dont cet agent est le responsable désigné, ou null — voir ApercuOrganisationController. */
  serviceResponsableId: number | null;
  /** Id de la direction dont cet agent est le directeur désigné, ou null. */
  directionDirigeeId: number | null;
}

/** Exposition API minimale d'un compte, utilisée pour le sélecteur "délégataire". */
export interface UserRef {
  id: number;
  email: string;
  actif: boolean;
  nomComplet: string;
}
