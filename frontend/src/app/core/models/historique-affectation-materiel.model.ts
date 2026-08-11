import { MaterielInformatique } from './materiel-informatique.model';
import { Personnel } from './personnel.model';

export interface HistoriqueAffectationMateriel {
  id?: number;
  materiel: MaterielInformatique | string;
  personnel?: Personnel | string | null;
  dateAffectation: string;
  dateFinAffectation?: string | null;
  observations?: string | null;
  createdAt?: string;
}
