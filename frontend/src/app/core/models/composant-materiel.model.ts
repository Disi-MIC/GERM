import { ListeValeurRef } from './personnel.model';

export interface ComposantMateriel {
  id?: number;
  /** IRI du matériel parent — requis seulement à la création (voir ComposantMaterielApiService.creer()). */
  materiel?: string;
  type: ListeValeurRef | string;
  specification: string;
  createdAt?: string;
}
