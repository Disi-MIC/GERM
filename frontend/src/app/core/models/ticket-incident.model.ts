import { MaterielInformatique } from './materiel-informatique.model';
import { ListeValeurRef, Personnel } from './personnel.model';

export type StatutTicket = 'ouvert' | 'en_cours' | 'resolu' | 'cloture' | 'refuse';

export interface TicketIncident {
  id?: number;
  personnel: Personnel | string;
  materiel: MaterielInformatique | string;
  titre: string;
  description: string;
  priorite: ListeValeurRef | string;
  statut?: StatutTicket;
  assigneA?: Personnel | string | null;
  commentaireResolution?: string | null;
  commentaireValidation?: string | null;
  datePriseEnCharge?: string | null;
  dateResolution?: string | null;
  dateCloture?: string | null;
  createdAt?: string;
  ouvert?: boolean;
  enCours?: boolean;
  resolu?: boolean;
}
