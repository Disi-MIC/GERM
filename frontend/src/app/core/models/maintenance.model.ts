import { MaterielInformatique } from './materiel-informatique.model';
import { Personnel } from './personnel.model';
import { TicketIncident } from './ticket-incident.model';

export type TypeMaintenance = 'preventive' | 'corrective';

export interface Maintenance {
  id?: number;
  materiel: MaterielInformatique | string;
  type: TypeMaintenance;
  description: string;
  dateRealisation: string;
  realisePar?: Personnel | string | null;
  prestataireExterne?: string | null;
  ticketOrigine?: TicketIncident | string | null;
  cout?: string | null;
  observations?: string | null;
  createdAt?: string;
}
