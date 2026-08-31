import { Personnel } from './personnel.model';
import { Vehicule } from './vehicule.model';

export interface BonEssence {
  id?: number;
  vehicule: Vehicule | string;
  numero?: string | null;
  date: string;
  quantiteLitres?: string | null;
  montant?: string | null;
  kilometrageReleve?: number | null;
  chauffeur?: Personnel | string | null;
  createdAt?: string;
}
