import { ListeValeurRef, Personnel } from './personnel.model';

export interface DocumentAdministratif {
  id?: number;
  personnel: Personnel | string;
  type: ListeValeurRef | string;
  libelle: string;
  dateDocument?: string | null;
  dateExpiration?: string | null;
  nomOriginal?: string | null;
  observations?: string | null;
  createdAt?: string;
}
