import { UserRef } from './user.model';

export type RoleDelegable = 'ROLE_ADMIN_RH' | 'ROLE_RH_PERSONNEL' | 'ROLE_RH_CONGE' | 'ROLE_RH_CARTE_PRO';
export type StatutDelegation = 'active' | 'revoquee';

export interface StatutAffiche {
  label: string;
  badgeClass: string;
}

export interface Delegation {
  id?: number;
  delegant?: UserRef | string;
  delegataire: UserRef | string;
  roleDelegue: RoleDelegable;
  dateDebut: string | null;
  dateFin: string | null;
  motif?: string | null;
  statut?: StatutDelegation;
  createdAt?: string;
  isActive?: boolean;
  statutAffiche?: StatutAffiche;
}
