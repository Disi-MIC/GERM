import { ListeValeurRef, Personnel, ServiceRef } from './personnel.model';

export interface MaterielInformatique {
  id?: number;
  numeroInventaire: string;
  type: ListeValeurRef | string;
  marque: string;
  modele: string;
  numeroSerie?: string | null;
  specifications?: string | null;
  dateAcquisition?: string | null;
  /** RH uniquement (groupe api:read:rh côté serveur) — jamais renvoyé par la vue self-service "Mon parc informatique". */
  valeurAcquisition?: string | null;
  /** RH uniquement, voir valeurAcquisition. */
  fournisseur?: string | null;
  garantieJusquau?: string | null;
  etat: ListeValeurRef | string;
  service: ServiceRef | string;
  affecteA?: Personnel | string | null;
  observations?: string | null;
  createdAt?: string;
}
