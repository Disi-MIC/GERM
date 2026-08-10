export interface CurrentUser {
  id: number;
  email: string;
  nom: string;
  prenom: string;
  roles: string[];
}

/** Exposition API minimale d'un compte, utilisée pour le sélecteur "délégataire". */
export interface UserRef {
  id: number;
  email: string;
  actif: boolean;
  nomComplet: string;
}
