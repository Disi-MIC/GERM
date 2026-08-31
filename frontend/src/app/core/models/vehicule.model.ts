import { ListeValeurRef, Personnel, ServiceRef } from './personnel.model';

export interface StatutEcheance {
  label: string;
  badgeClass: string;
}

export interface Vehicule {
  id?: number;
  immatriculation: string;
  type: ListeValeurRef | string;
  marque: string;
  modele: string;
  numeroChassis?: string | null;
  carburant?: string | null;
  dateAcquisition?: string | null;
  /** Réservé au superadmin (groupe api:read:admin côté serveur) — jamais renvoyé par la vue self-service "Mon parc automobile". */
  valeurAcquisition?: string | null;
  kilometrage?: number | null;
  assuranceJusquau?: string | null;
  visiteTechniqueJusquau?: string | null;
  etat: ListeValeurRef | string;
  service: ServiceRef | string;
  chauffeurAffecte?: Personnel | string | null;
  observations?: string | null;
  /** Intervalle entre deux vidanges, en km — null si non suivi. */
  periodiciteVidangeKm?: number | null;
  /** Dénormalisé depuis la dernière entrée HistoriqueVidange — jamais saisi directement. */
  derniereVidangeKm?: number | null;
  derniereVidangeDate?: string | null;
  createdAt?: string;
  statutAssurance?: StatutEcheance | null;
  statutVisiteTechnique?: StatutEcheance | null;
  statutVidange?: StatutEcheance | null;
}
