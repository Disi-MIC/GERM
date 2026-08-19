export interface AgentApercu {
  id: number;
  nomComplet: string;
  matricule: string | null;
  fonction: string | null;
  grade: string | null;
  statut: string | null;
}

export interface AgentApercuAvecService extends AgentApercu {
  serviceNom: string | null;
}

export interface ApercuMonService {
  service: {
    id: number;
    nom: string;
    code: string;
    direction: { id: number; nom: string } | null;
  };
  nbAgents: number;
  nbMateriels: number;
  agents: AgentApercu[];
}

export interface ApercuMaDirection {
  direction: { id: number; nom: string; code: string };
  nbServices: number;
  nbAgents: number;
  services: { id: number; nom: string; nbAgents: number }[];
  agents: AgentApercuAvecService[];
}

export interface RepartitionSexe {
  M: number;
  F: number;
}

/** Âge/ancienneté seulement — jamais d'autre donnée personnelle sur un directeur, voir ApercuOrganisationController. */
export interface DirecteurCarriere {
  nom: string;
  age: number | null;
  anciennete: number | null;
}

export interface ApercuMinistere {
  nbAgents: number;
  nbServices: number;
  nbDirections: number;
  parDirection: Record<string, RepartitionSexe>;
  parService: Record<string, RepartitionSexe>;
  parGrade: Record<string, RepartitionSexe>;
  directions: { id: number; nom: string; nbServices: number; directeur: DirecteurCarriere | null }[];
  services: { id: number; nom: string }[];
  grades: string[];
  materiel: { total: number; parEtat: Record<string, number>; parVulnerabilite: Record<string, number> };
  filtreDirection: number | null;
  filtreService: number | null;
  filtreGrade: string | null;
}
