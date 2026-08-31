import { Vehicule } from './vehicule.model';

export interface HistoriqueVidange {
  id?: number;
  vehicule: Vehicule | string;
  date: string;
  kilometrage: number;
  cout?: string | null;
  prestataire?: string | null;
  observations?: string | null;
  createdAt?: string;
}
