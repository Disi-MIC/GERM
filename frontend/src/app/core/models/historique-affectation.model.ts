import { Personnel } from './personnel.model';
import { ServiceRef } from './personnel.model';

export type TypeMouvementCarriere = 'nomination' | 'mutation' | 'promotion' | 'autre';

export interface HistoriqueAffectation {
  id?: number;
  personnel: Personnel | string;
  service: ServiceRef | string;
  fonction: string;
  grade?: string | null;
  typeMouvement: TypeMouvementCarriere;
  dateEffet: string | null;
  numeroDecision?: string | null;
  dateDecision?: string | null;
  observations?: string | null;
  createdAt?: string;
}
