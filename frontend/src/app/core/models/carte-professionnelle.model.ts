import { Personnel } from './personnel.model';

export type StatutCarteProfessionnelle = 'valide' | 'perdue' | 'volee' | 'annulee';

export interface StatutAffiche {
  label: string;
  badgeClass: string;
}

export interface CarteProfessionnelle {
  id?: number;
  personnel: Personnel | string;
  numero: string;
  dateDelivrance: string | null;
  dateExpiration?: string | null;
  statut: StatutCarteProfessionnelle;
  observations?: string | null;
  nomOriginal?: string | null;
  valideeParAdminRh?: boolean;
  valideeParNom?: string | null;
  valideeLe?: string | null;
  createdAt?: string;
  hasFichier?: boolean;
  statutAffiche?: StatutAffiche;
}
