import { CarteProfessionnelle } from './carte-professionnelle.model';
import { HistoriqueAffectation } from './historique-affectation.model';

export type DomaineDemande = 'carte_pro' | 'decision' | 'jouissance';

export interface DemandeEnAttenteResume {
  domaine: DomaineDemande;
  id: number;
  libelle: string;
  statut: string;
  createdAt: string;
}

export interface RepartitionDemandes {
  en_attente: number;
  transmise: number;
  approuvee: number;
  refusee: number;
}

export interface DashboardMe {
  carriere: {
    nbMouvements: number;
    dernierMouvement: HistoriqueAffectation | null;
  };
  conges: {
    total: number;
    totalJours: number;
  };
  materiels: { total: number };
  vehicules: { total: number };
  carteActive: CarteProfessionnelle | null;
  demandesEnAttente: DemandeEnAttenteResume[];
  repartitionDemandes: RepartitionDemandes;
}
