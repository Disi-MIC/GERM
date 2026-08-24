export interface ServiceRef {
  id: number;
  code: string;
  nom: string;
  responsableNom?: string | null;
}

export interface ListeValeurRef {
  id: number;
  categorie: string;
  code: string;
  libelle: string;
}

export interface Personnel {
  id?: number;
  matricule: string | null;
  nom: string;
  prenom: string;
  sexe: 'M' | 'F';
  dateNaissance: string | null;
  fonction: string;
  grade: string | null;
  typeContrat: ListeValeurRef | string;
  dateEmbauche: string | null;
  statut: string;
  telephone: string | null;
  email: string | null;
  adresse: string | null;
  service: ServiceRef | string;
  observations: string | null;
  createdAt?: string;
  updatedAt?: string;
  nomComplet?: string;
  hasPhoto?: boolean;
}
