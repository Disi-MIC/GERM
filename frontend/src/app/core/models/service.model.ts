import { PersonnelRef } from './personnel-ref.model';

export interface DirectionRef {
  id: number;
  nom: string;
}

export interface Service {
  id?: number;
  code: string;
  nom: string;
  description: string | null;
  actif: boolean;
  direction: DirectionRef | string | null;
  responsable: PersonnelRef | string | null;
  responsableNom?: string | null;
  /** Justificatif de la nomination du responsable — obligatoire dès que `responsable` est renseigné. */
  noteServiceNumero: string | null;
  noteServiceDate: string | null;
  hasNoteServiceFichier?: boolean;
  noteServiceNomOriginal?: string | null;
}
