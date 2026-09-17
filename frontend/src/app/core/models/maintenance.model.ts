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
  /** Nom du fichier d'origine de la pièce justificative — présent seulement si un fichier a été joint (voir MaintenanceController::piece()). */
  nomOriginal?: string | null;
  createdAt?: string;
}

export interface MaintenancePayload {
  materiel: number;
  type: number;
  description: string;
  dateRealisation: string;
  realisePar?: number | null;
  prestataireExterne?: string | null;
  ticketOrigine?: number | null;
  observations?: string | null;
  fichier?: File | null;
}
