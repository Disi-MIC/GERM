import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ChartData } from 'chart.js';
import { PanelComponent } from '../../shared/panel/panel.component';
import { StatTileComponent } from '../../shared/stat-tile/stat-tile.component';
import { ChartComponent } from '../../shared/chart/chart.component';
import { AgentOuServiceFiltreComponent } from '../../shared/agent-ou-service-filtre/agent-ou-service-filtre.component';
import { CHART_COLORS, CHART_PALETTE } from '../../shared/chart/chart-colors';
import { moisAnneeFr } from '../../shared/date-fr';
import { DashboardInformatique } from '../../core/models/dashboard.model';
import { DashboardApiService } from '../dashboard/dashboard-api.service';

type PeriodeKey = 'aujourdhui' | 'semaine' | 'mois' | 'total';

const LABELS_PERIODE: Record<PeriodeKey, string> = {
  aujourdhui: "Aujourd'hui",
  semaine: 'Cette semaine',
  mois: 'Ce mois',
  total: 'Depuis toujours',
};

/** Regroupement d'affichage du volume mensuel de cartouches — pas un cumul depuis une date (voir PeriodeKey ci-dessus), juste comment sommer les 12 mois glissants renvoyés par le serveur. */
type PeriodeCartouches = 'mois' | 'trimestre' | 'semestre' | 'annee';

const LABELS_PERIODE_CARTOUCHES: Record<PeriodeCartouches, string> = {
  mois: 'Mois',
  trimestre: 'Trimestre',
  semestre: 'Semestre',
  annee: 'Année',
};

const LABELS_COULEUR_CARTOUCHE: Record<string, string> = { noir: 'Noir', cyan: 'Cyan', magenta: 'Magenta', jaune: 'Jaune' };
const COULEURS_CHART_CARTOUCHE: Record<string, string> = { noir: '#343a40', cyan: '#0dcaf0', magenta: '#d63384', jaune: '#ffc107' };

@Component({
  selector: 'app-dashboard-informatique',
  standalone: true,
  imports: [RouterLink, StatTileComponent, PanelComponent, ChartComponent, AgentOuServiceFiltreComponent],
  templateUrl: './dashboard-informatique.component.html',
})
export class DashboardInformatiqueComponent implements OnInit {
  data: DashboardInformatique | null = null;
  loading = true;
  error: string | null = null;
  periode: PeriodeKey = 'mois';
  readonly periodes: PeriodeKey[] = ['aujourdhui', 'semaine', 'mois', 'total'];
  readonly labelsPeriode = LABELS_PERIODE;

  periodeCartouches: PeriodeCartouches = 'mois';
  readonly periodesCartouches: PeriodeCartouches[] = ['mois', 'trimestre', 'semestre', 'annee'];
  readonly labelsPeriodeCartouches = LABELS_PERIODE_CARTOUCHES;
  readonly labelsCouleurCartouche = LABELS_COULEUR_CARTOUCHE;

  /** Périmètre du bloc Cartouches (voir AgentOuServiceFiltreComponent) — recharge le tableau de bord entier au changement, comme dashboard.component.ts (filtreDirection/filtreService) plutôt qu'un re-filtrage client des agrégats déjà réduits côté serveur. */
  filtreServiceId: number | null = null;

  /** Champs simples recalculés au chargement/changement de période plutôt que des getters — voir dashboard.component.ts. */
  chartTickets: ChartData<'doughnut'> = { labels: [], datasets: [] };
  chartMaterielParEtat: ChartData<'pie'> = { labels: [], datasets: [] };
  chartCartouchesParMois: ChartData<'bar'> = { labels: [], datasets: [] };
  chartCartouchesParCouleur: ChartData<'doughnut'> = { labels: [], datasets: [] };

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  onFiltreServiceChange(serviceId: number | null): void {
    this.filtreServiceId = serviceId;
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.getInformatique(this.filtreServiceId).subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
        this.recalculerChartTickets();
        this.recalculerChartCartouchesParMois();
        this.chartMaterielParEtat = {
          labels: Object.keys(data.materiel.parEtat),
          datasets: [{ data: Object.values(data.materiel.parEtat), backgroundColor: CHART_PALETTE }],
        };
        const couleurs = Object.keys(data.cartouches.parCouleur);
        this.chartCartouchesParCouleur = {
          labels: couleurs.map((c) => LABELS_COULEUR_CARTOUCHE[c] ?? c),
          datasets: [
            {
              data: couleurs.map((c) => data.cartouches.parCouleur[c].count),
              backgroundColor: couleurs.map((c) => COULEURS_CHART_CARTOUCHE[c] ?? CHART_COLORS.secondary),
            },
          ],
        };
      },
      error: () => {
        this.error = 'Impossible de charger le tableau de bord.';
        this.loading = false;
      },
    });
  }

  selectionnerPeriode(periode: PeriodeKey): void {
    this.periode = periode;
    this.recalculerChartTickets();
  }

  selectionnerPeriodeCartouches(periode: PeriodeCartouches): void {
    this.periodeCartouches = periode;
    this.recalculerChartCartouchesParMois();
  }

  ticketsTraitesPeriode() {
    return this.data?.tickets.traites[this.periode] ?? { resolus: 0, refuses: 0 };
  }

  maintenancePeriode(): number {
    return this.data?.maintenance[this.periode] ?? 0;
  }

  get couleursCartouche(): string[] {
    return Object.keys(this.data?.cartouches.parCouleur ?? {});
  }

  private recalculerChartTickets(): void {
    const { resolus, refuses } = this.ticketsTraitesPeriode();
    this.chartTickets = {
      labels: ['Résolus', 'Refusés'],
      datasets: [{ data: [resolus, refuses], backgroundColor: [CHART_COLORS.success, CHART_COLORS.danger] }],
    };
  }

  /**
   * Les 12 mois glissants renvoyés par le serveur (DashboardController::calculerCartouches())
   * sont sommés par groupes de 1/3/6/12 selon periodeCartouches — un seul
   * agrégat serveur, tout regroupement d'affichage se fait ici.
   */
  private recalculerChartCartouchesParMois(): void {
    if (!this.data) {
      return;
    }
    const taille = { mois: 1, trimestre: 3, semestre: 6, annee: 12 }[this.periodeCartouches];
    const cles = Object.keys(this.data.cartouches.parMois).sort();
    const labels: string[] = [];
    const valeurs: number[] = [];
    for (let i = 0; i < cles.length; i += taille) {
      const groupe = cles.slice(i, i + taille);
      const somme = groupe.reduce((total, cle) => total + this.data!.cartouches.parMois[cle], 0);
      labels.push(groupe.length > 1 ? `${moisAnneeFr(groupe[0])} – ${moisAnneeFr(groupe[groupe.length - 1])}` : moisAnneeFr(groupe[0]));
      valeurs.push(somme);
    }

    this.chartCartouchesParMois = {
      labels,
      datasets: [{ label: 'Changements de cartouche', data: valeurs, backgroundColor: CHART_COLORS.primary }],
    };
  }
}
