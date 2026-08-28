import { MaterielInformatique } from './materiel-informatique.model';

export interface HistoriqueChangementMateriel {
  id?: number;
  materiel: MaterielInformatique | string;
  champ: string;
  valeurAvant?: string | null;
  valeurApres?: string | null;
  enregistreParNom?: string | null;
  createdAt?: string;
}
