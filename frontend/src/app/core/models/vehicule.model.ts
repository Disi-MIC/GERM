import { ListeValeurRef, ServiceRef } from './personnel.model';

export type Carburant = 'essence' | 'diesel' | 'electrique' | 'hybride';

export interface Vehicule {
  id?: number;
  immatriculation: string;
  type: ListeValeurRef | string;
  marque: string;
  modele: string;
  numeroChassis?: string | null;
  carburant?: Carburant | null;
  dateAcquisition?: string | null;
  kilometrage?: number | null;
  assuranceJusquau?: string | null;
  visiteTechniqueJusquau?: string | null;
  etat: ListeValeurRef | string;
  service: ServiceRef | string;
  observations?: string | null;
  createdAt?: string;
}
