import { MaterielInformatique } from './materiel-informatique.model';
import { ListeValeurRef, Personnel } from './personnel.model';
import { TicketIncident } from './ticket-incident.model';

export interface Maintenance {
  id?: number;
  materiel: MaterielInformatique | string;
  type: ListeValeurRef | string;
  description: string;
  dateRealisation: string;
  realisePar?: Personnel | string | null;
  prestataireExterne?: string | null;
  ticketOrigine?: TicketIncident | string | null;
  observations?: string | null;
  createdAt?: string;
}
