import { MaterielInformatique } from './materiel-informatique.model';
import { ListeValeurRef, Personnel } from './personnel.model';

export type StatutTicket = 'ouvert' | 'en_cours' | 'resolu' | 'cloture' | 'refuse';
export type NiveauTicket = 'n1' | 'n2' | 'n3';

export interface TicketIncident {
  id?: number;
  personnel: Personnel | string;
  materiel: MaterielInformatique | string;
  titre: string;
  description: string;
  priorite: ListeValeurRef | string;
  statut?: StatutTicket;
  /** Palier de support courant (N1/N2/N3) — voir escalade, distinct de `statut`. */
  niveau?: NiveauTicket;
  assigneA?: Personnel | string | null;
  commentaireResolution?: string | null;
  commentaireValidation?: string | null;
  datePriseEnCharge?: string | null;
  dateResolution?: string | null;
  dateCloture?: string | null;
  createdAt?: string;
  /** Échéance cible de résolution (ITIL), calculée par priorité — voir TicketIncident::getEcheanceSla(). */
  echeanceSla?: string | null;
  ouvert?: boolean;
  enCours?: boolean;
  resolu?: boolean;
}

export interface TicketEscalade {
  id?: number;
  ticket: TicketIncident | string;
  deNiveau: NiveauTicket;
  versNiveau: NiveauTicket;
  par?: Personnel | string | null;
  commentaire: string;
  createdAt?: string;
}
