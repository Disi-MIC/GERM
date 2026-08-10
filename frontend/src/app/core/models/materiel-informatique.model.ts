import { ListeValeurRef, ServiceRef } from './personnel.model';

export interface MaterielInformatique {
  id?: number;
  numeroInventaire: string;
  type: ListeValeurRef | string;
  marque: string;
  modele: string;
  numeroSerie?: string | null;
  specifications?: string | null;
  dateAcquisition?: string | null;
  garantieJusquau?: string | null;
  etat: ListeValeurRef | string;
  service: ServiceRef | string;
  observations?: string | null;
  createdAt?: string;
}
