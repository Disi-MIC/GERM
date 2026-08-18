import { ListeValeurRef, Personnel } from './personnel.model';

export type TypeConge = 'annuel' | 'maladie' | 'maternite_paternite' | 'sans_solde' | 'autre';
/** validee/deposee_courrier/retournee/transmise_agent : DemandeDecision uniquement (circuit à 5 étapes). approuvee : DemandeJouissance uniquement (3 états). refusee/en_attente : les deux. */
export type StatutDemande = 'en_attente' | 'validee' | 'deposee_courrier' | 'retournee' | 'approuvee' | 'refusee' | 'transmise_agent';

export interface Conge {
  id?: number;
  personnel: Personnel | string;
  type: TypeConge;
  dateDebut: string | null;
  dateFin: string | null;
  motif?: string | null;
  createdAt?: string;
  duree?: number | null;
}

export interface DecisionConge {
  id?: number;
  personnel: Personnel | string;
  numeroDecision: string;
  dateDecision: string | null;
  dateExpiration: string | null;
  observations?: string | null;
  nombreJours?: number | null;
  genereeParNom?: string | null;
  createdAt?: string;
  isValide?: boolean;
}

export interface PieceJustificative {
  id: number;
  nomOriginal: string;
  createdAt: string;
}

export interface DemandeDecision {
  id?: number;
  personnel: Personnel | string;
  nouvellementAffecte: boolean;
  dateDerniereDecision?: string | null;
  numeroDerniereDecision?: string | null;
  motif?: string | null;
  statut?: StatutDemande;
  dateTraitement?: string | null;
  commentaireTraitement?: string | null;
  decisionCreee?: DecisionConge | string | null;
  motifRejet?: ListeValeurRef | string | null;
  pieces?: PieceJustificative[];
  nomOriginalDocumentRetour?: string | null;
  hasDocumentRetour?: boolean;
  createdAt?: string;
  enAttente?: boolean;
  validee?: boolean;
  deposeeCourrier?: boolean;
  retournee?: boolean;
}

export interface DemandeJouissance {
  id?: number;
  personnel: Personnel | string;
  type: TypeConge;
  decision?: DecisionConge | string | null;
  dateDebut: string | null;
  dateFin: string | null;
  motif?: string | null;
  statut?: StatutDemande;
  dateTraitement?: string | null;
  commentaireTraitement?: string | null;
  conge?: Conge | string | null;
  pieces?: PieceJustificative[];
  createdAt?: string;
  isEnAttente?: boolean;
}
