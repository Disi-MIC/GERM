import { Personnel } from './personnel.model';

export interface HistoriqueChangementPersonnel {
  id?: number;
  personnel: Personnel | string;
  champ: string;
  valeurAvant?: string | null;
  valeurApres?: string | null;
  enregistreParNom?: string | null;
  createdAt?: string;
}
