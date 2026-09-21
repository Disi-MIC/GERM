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

/**
 * Les champs marqués optionnels ci-dessous (hors id/matricule/nom/prenom/
 * nomComplet) portent tous le groupe de sérialisation api:read:personnel-sensible
 * côté serveur (voir Personnel::class et App\Serializer\PersonnelNormalizer,
 * GERM-009) : absents de la réponse pour un appelant ROLE_IT_STOCK ou
 * ROLE_RH_CARTE_PRO consultant la fiche d'un autre agent — présents pour
 * ROLE_RH_PERSONNEL, et pour n'importe quel agent consultant SA PROPRE fiche
 * (self-service, /api/me/personnel).
 */
export interface Personnel {
  id?: number;
  matricule: string | null;
  nom: string;
  prenom: string;
  sexe?: 'M' | 'F';
  cni?: string | null;
  dateNaissance?: string | null;
  fonction?: string;
  grade?: string | null;
  corps?: ListeValeurRef | string | null;
  hierarchie?: ListeValeurRef | string | null;
  typeContrat?: ListeValeurRef | string;
  programme?: ListeValeurRef | string | null;
  chapitre?: ListeValeurRef | string | null;
  dateEmbauche?: string | null;
  statut?: string;
  telephone?: string | null;
  email?: string | null;
  adresse?: string | null;
  service?: ServiceRef | string;
  observations?: string | null;
  createdAt?: string;
  updatedAt?: string;
  nomComplet?: string;
  hasPhoto?: boolean;
}
