import { MaterielInformatique } from './materiel-informatique.model';

export type CouleurCartouche = 'noir' | 'cyan' | 'magenta' | 'jaune';

export interface ChangementCartouche {
  id?: number;
  materiel: MaterielInformatique | string;
  couleur: CouleurCartouche;
  reference?: string | null;
  dateChangement: string;
  enregistreParNom?: string | null;
  observations?: string | null;
  createdAt?: string;
}
