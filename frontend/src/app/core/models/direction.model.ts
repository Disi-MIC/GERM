import { PersonnelRef } from './personnel-ref.model';

export interface Direction {
  id?: number;
  code: string;
  nom: string;
  description: string | null;
  actif: boolean;
  directeur: PersonnelRef | string | null;
  directeurNom?: string | null;
  /** Justificatif de la nomination du directeur — obligatoire dès que `directeur` est renseigné. */
  noteServiceNumero: string | null;
  noteServiceDate: string | null;
  hasNoteServiceFichier?: boolean;
  noteServiceNomOriginal?: string | null;
}
