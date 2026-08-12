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
  fournisseur?: string | null;
  garantieJusquau?: string | null;
  /** Fréquence de maintenance préventive en mois (3/6/12...), null si aucun plan requis. */
  periodiciteMois?: number | null;
  systemeExploitation?: ListeValeurRef | string | null;
  suiteBureautique?: ListeValeurRef | string | null;
  antivirus?: ListeValeurRef | string | null;
  etat: ListeValeurRef | string;
  service: ServiceRef | string;
  affecteA?: Personnel | string | null;
  observations?: string | null;
  createdAt?: string;
}
