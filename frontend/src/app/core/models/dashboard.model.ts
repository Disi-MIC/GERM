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
  documentsExpirants: { enRetard: EcheanceDocument[]; aVenir: EcheanceDocument[] };
  structureIncomplete: {
    servicesSansResponsable: { serviceId: number; nom: string; direction: string }[];
    directionsSansDirecteur: { directionId: number; nom: string }[];
  };
  // null si l'utilisateur n'a pas ROLE_RH_RESPONSABLE (accès réservé, comme Delegation).
  delegationsExpirantes: { enRetard: EcheanceDelegation[]; aVenir: EcheanceDelegation[] } | null;
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
  decisionsExpirantes: { enRetard: EcheanceDecision[]; aVenir: EcheanceDecision[] };
}

export interface DashboardCartesProfessionnelles {
  enAttente: number;
  transmises: number;
  traites: PeriodeTraitement<CompteurApprouveesRefusees>;
  cartesValides: number;
  cartesExpirantBientot: { enRetard: EcheanceCarte[]; aVenir: EcheanceCarte[] };
}

export interface EcheanceDocument {
  documentId: number;
  personnelId: number | null;
  personnel: string;
  libelle: string;
  type: string;
  echeance: string;
  jours: number;
}

export interface EcheanceCarte {
  carteId: number;
  personnelId: number | null;
  personnel: string;
  numero: string;
  echeance: string;
  jours: number;
}

export interface EcheanceDelegation {
  delegationId: number;
  delegant: string;
  delegataire: string;
  role: string;
  echeance: string;
  jours: number;
}

export interface EcheanceDecision {
  decisionId: number;
  personnelId: number | null;
  personnel: string;
  numeroDecision: string;
  echeance: string;
  jours: number;
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
  materielsSansNiveauVulnerabilite: MaterielSansVulnerabilite[];
  licencesExpirantBientot: { enRetard: EcheanceLicence[]; aVenir: EcheanceLicence[] };
  slaTickets: { enRetard: SlaTicket[]; aRisque: SlaTicket[] };
  cartouches: DashboardCartouches;
}

/** Vue "approvisionnement" du journal des cartouches (voir ChangementCartouche côté serveur) — pas l'historique complet, juste de quoi décider du rythme de réapprovisionnement. */
export interface DashboardCartouches {
  total: number;
  /** 12 entrées chronologiques clé "YYYY-MM" — le mensuel/trimestriel/semestriel/annuel s'en dérivent tous par simple somme côté Angular. */
  parMois: Record<string, number>;
  parCouleur: Record<string, { count: number; dureeMoyenneJours: number | null }>;
  topReferences: { reference: string; count: number }[];
  topImprimantes: { materielId: number; label: string; count: number }[];
  topServices: { serviceId: number; nom: string; count: number }[];
  topAgents: { personnelId: number; nom: string; count: number }[];
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

export interface MaterielSansVulnerabilite {
  materielId: number;
  numeroInventaire: string;
  marque: string;
  modele: string;
}

export interface EcheanceLicence {
  licenceId: number;
  logicielId: number;
  logiciel: string;
  nombrePostes: number | null;
  echeance: string;
  jours: number;
}
