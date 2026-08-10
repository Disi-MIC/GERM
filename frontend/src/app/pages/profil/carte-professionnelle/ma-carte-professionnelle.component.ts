import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { forkJoin } from 'rxjs';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { DemandeCartePro } from '../../../core/models/demande-carte-pro.model';
import { ProfilApiService } from '../profil-api.service';

const LABELS_STATUT_CARTE: Record<string, string> = {
  valide: 'Valide',
  perdue: 'Perdue',
  volee: 'Volée',
  annulee: 'Annulée',
};

const LABELS_STATUT_DEMANDE: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE_DEMANDE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-ma-carte-professionnelle',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './ma-carte-professionnelle.component.html',
})
export class MaCarteProfessionnelleComponent implements OnInit {
  cartes: CarteProfessionnelle[] = [];
  demandes: DemandeCartePro[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatutCarte = LABELS_STATUT_CARTE;
  readonly labelsStatutDemande = LABELS_STATUT_DEMANDE;
  readonly labelsTypeDemande = LABELS_TYPE_DEMANDE;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    forkJoin({
      cartes: this.api.getMesCartesProfessionnelles(),
      demandes: this.api.getMesDemandesCartePro(),
    }).subscribe({
      next: ({ cartes, demandes }) => {
        this.cartes = cartes;
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos cartes professionnelles.';
        this.loading = false;
      },
    });
  }

  badgeClasseCarte(statut: string): string {
    return statut === 'valide' ? 'success' : 'danger';
  }

  badgeClasseDemande(statut: string | undefined): string {
    switch (statut) {
      case 'approuvee':
        return 'success';
      case 'refusee':
        return 'danger';
      case 'transmise':
        return 'info';
      default:
        return 'secondary';
    }
  }
}
