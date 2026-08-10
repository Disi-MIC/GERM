import { CarteProfessionnelle } from './carte-professionnelle.model';
import { Personnel } from './personnel.model';

export type TypeDemandeCartePro = 'nouvelle' | 'renouvellement' | 'perte_vol';
export type StatutDemandeCartePro = 'en_attente' | 'transmise' | 'approuvee' | 'refusee';

export interface DemandeCartePro {
  id?: number;
  personnel: Personnel | string;
  typeDemande: TypeDemandeCartePro;
  carteReference?: CarteProfessionnelle | string | null;
  motif?: string | null;
  statut?: StatutDemandeCartePro;
  dateTraitement?: string | null;
  commentaireTraitement?: string | null;
  carteCreee?: CarteProfessionnelle | string | null;
  nomOriginal?: string | null;
  createdAt?: string;
  enAttente?: boolean;
  transmise?: boolean;
}
