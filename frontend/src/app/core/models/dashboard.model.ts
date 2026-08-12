export interface RepartitionRef {
  id: number;
  nom: string;
}

export interface DashboardPersonnel {
  nbPersonnel: number;
  nbServices: number;
  parDirection: Record<string, { M: number; F: number }>;
  parService: Record<string, { M: number; F: number }>;
  directions: RepartitionRef[];
  services: RepartitionRef[];
  filtreDirection: number | null;
  filtreService: number | null;
}

// Compteurs par période cumulative : aujourd'hui ⊆ semaine ⊆ mois ⊆ total.
export interface PeriodeTraitement<T> {
  aujourdhui: T;
  semaine: T;
  mois: T;
  total: T;
}

export interface CompteurApprouveesRefusees {
  approuvees: number;
  refusees: number;
}

export interface DashboardConges {
  enAttente: { decisions: number; jouissances: number };
  traites: PeriodeTraitement<CompteurApprouveesRefusees>;
  decisionsValides: number;
}

export interface DashboardCartesProfessionnelles {
  enAttente: number;
  transmises: number;
  traites: PeriodeTraitement<CompteurApprouveesRefusees>;
  cartesValides: number;
  cartesExpirantBientot: number;
}

export interface CompteurResolusRefuses {
  resolus: number;
  refuses: number;
}

export interface DashboardInformatique {
  tickets: {
    ouverts: number;
    enCours: number;
    traites: PeriodeTraitement<CompteurResolusRefuses>;
  };
  maintenance: PeriodeTraitement<number>;
  materiel: { total: number; parEtat: Record<string, number> };
  echeancesMaintenance: { enRetard: EcheanceMaintenance[]; aVenir: EcheanceMaintenance[] };
  licencesExpirantBientot: { enRetard: EcheanceLicence[]; aVenir: EcheanceLicence[] };
  slaTickets: { enRetard: SlaTicket[]; aRisque: SlaTicket[] };
}

export interface SlaTicket {
  ticketId: number;
  titre: string;
  priorite: string;
  niveau: string;
  echeance: string;
  heures: number;
}

export interface EcheanceMaintenance {
  materielId: number;
  numeroInventaire: string;
  marque: string;
  modele: string;
  echeance: string;
  jours: number;
}

export interface EcheanceLicence {
  licenceId: number;
  logicielId: number;
  logiciel: string;
  nombrePostes: number | null;
  echeance: string;
  jours: number;
}
