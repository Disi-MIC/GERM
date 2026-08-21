import { CategorieAgentConge } from './parametre-eligibilite-conge.model';

export interface ParametresDecisionConge {
  id?: number;
  categorie: CategorieAgentConge;
  visasDecrets: string | null;
  article2: string | null;
  article3: string | null;
  ampliations: string | null;
  updatedAt?: string | null;
  misAJourParNom?: string | null;
}
