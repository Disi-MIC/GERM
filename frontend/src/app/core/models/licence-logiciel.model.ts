import { ListeValeurRef } from './personnel.model';

export interface LicenceLogiciel {
  id?: number;
  logiciel: ListeValeurRef | string;
  numeroLicence?: string | null;
  dateDebut?: string | null;
  /** Durée en mois, saisie à la place d'une date d'expiration — voir dateExpiration. */
  dureeMois?: number | null;
  /** Calculée côté serveur (dateDebut + dureeMois), jamais envoyée en écriture. */
  dateExpiration?: string | null;
  fournisseur?: string | null;
  observations?: string | null;
  createdAt?: string;
}
