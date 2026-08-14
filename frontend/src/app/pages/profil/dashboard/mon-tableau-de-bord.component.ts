import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ChartData } from 'chart.js';
import { DashboardMe, DemandeEnAttenteResume, DomaineDemande } from '../../../core/models/dashboard-me.model';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { StatTileComponent } from '../../../shared/stat-tile/stat-tile.component';
import { ChartComponent } from '../../../shared/chart/chart.component';
import { CHART_COLORS } from '../../../shared/chart/chart-colors';
import { ProfilApiService } from '../profil-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const ROUTES_DOMAINE: Record<DomaineDemande, string> = {
  carte_pro: '/mon-espace/carte-professionnelle',
  decision: '/mon-espace/conges',
  jouissance: '/mon-espace/conges',
};

const COULEURS_REPARTITION: Record<string, string> = {
  en_attente: 'secondary',
  transmise: 'info',
  approuvee: 'success',
  refusee: 'danger',
};

const CHART_COULEURS_REPARTITION: Record<string, string> = {
  en_attente: CHART_COLORS.secondary,
  transmise: CHART_COLORS.info,
  approuvee: CHART_COLORS.success,
  refusee: CHART_COLORS.danger,
};

@Component({
  selector: 'app-mon-tableau-de-bord',
  standalone: true,
  imports: [SlicePipe, RouterLink, FormsModule, StatTileComponent, PanelComponent, ChartComponent],
  templateUrl: './mon-tableau-de-bord.component.html',
})
export class MonTableauDeBordComponent implements OnInit {
  data: DashboardMe | null = null;
  loading = true;
  error: string | null = null;
  filtreDomaine: DomaineDemande | 'toutes' = 'toutes';
  readonly labelsStatut = LABELS_STATUT;
  readonly couleursRepartition = COULEURS_REPARTITION;

  /** Champ simple recalculé une seule fois au chargement plutôt qu'un getter — voir dashboard.component.ts. */
  chartRepartition: ChartData<'doughnut'> = { labels: [], datasets: [] };

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMonTableauDeBord().subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
        const r = data.repartitionDemandes;
        const entrees = Object.entries(r).filter(([, valeur]) => valeur > 0);
        this.chartRepartition = {
          labels: entrees.map(([cle]) => this.labelsStatut[cle] ?? cle),
          datasets: [{ data: entrees.map(([, valeur]) => valeur), backgroundColor: entrees.map(([cle]) => CHART_COULEURS_REPARTITION[cle]) }],
        };
      },
      error: () => {
        this.error = 'Impossible de charger votre tableau de bord.';
        this.loading = false;
      },
    });
  }

  get demandesFiltrees(): DemandeEnAttenteResume[] {
    if (!this.data) {
      return [];
    }
    if (this.filtreDomaine === 'toutes') {
      return this.data.demandesEnAttente;
    }
    return this.data.demandesEnAttente.filter((d) => d.domaine === this.filtreDomaine);
  }

  routeDemande(demande: DemandeEnAttenteResume): string {
    return ROUTES_DOMAINE[demande.domaine];
  }

  totalDemandes(): number {
    if (!this.data) {
      return 0;
    }
    const r = this.data.repartitionDemandes;
    return r.en_attente + r.transmise + r.approuvee + r.refusee;
  }
}
