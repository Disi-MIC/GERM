export type CategorieAgentConge = 'fonctionnaire' | 'non_fonctionnaire';

export interface ParametreEligibiliteConge {
  id?: number;
  categorie: CategorieAgentConge;
  joursParMois: number;
  plafondJours: number;
  delaiEligibiliteMois: number;
  updatedAt?: string | null;
  misAJourParNom?: string | null;
}
