import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ChartData } from 'chart.js';
import { PanelComponent } from '../../shared/panel/panel.component';
import { StatTileComponent } from '../../shared/stat-tile/stat-tile.component';
import { ChartComponent } from '../../shared/chart/chart.component';
import { CHART_COLORS, CHART_PALETTE } from '../../shared/chart/chart-colors';
import { DashboardInformatique } from '../../core/models/dashboard.model';
import { DashboardApiService } from '../dashboard/dashboard-api.service';

type PeriodeKey = 'aujourdhui' | 'semaine' | 'mois' | 'total';

const LABELS_PERIODE: Record<PeriodeKey, string> = {
  aujourdhui: "Aujourd'hui",
  semaine: 'Cette semaine',
  mois: 'Ce mois',
  total: 'Depuis toujours',
};

@Component({
  selector: 'app-dashboard-informatique',
  standalone: true,
  imports: [RouterLink, StatTileComponent, PanelComponent, ChartComponent],
  templateUrl: './dashboard-informatique.component.html',
})
export class DashboardInformatiqueComponent implements OnInit {
  data: DashboardInformatique | null = null;
  loading = true;
  error: string | null = null;
  periode: PeriodeKey = 'mois';
  readonly periodes: PeriodeKey[] = ['aujourdhui', 'semaine', 'mois', 'total'];
  readonly labelsPeriode = LABELS_PERIODE;

  /** Champs simples recalculés au chargement/changement de période plutôt que des getters — voir dashboard.component.ts. */
  chartTickets: ChartData<'doughnut'> = { labels: [], datasets: [] };
  chartMaterielParEtat: ChartData<'pie'> = { labels: [], datasets: [] };

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.loading = true;
    this.api.getInformatique().subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
        this.recalculerChartTickets();
        this.chartMaterielParEtat = {
          labels: Object.keys(data.materiel.parEtat),
          datasets: [{ data: Object.values(data.materiel.parEtat), backgroundColor: CHART_PALETTE }],
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

  ticketsTraitesPeriode() {
    return this.data?.tickets.traites[this.periode] ?? { resolus: 0, refuses: 0 };
  }

  maintenancePeriode(): number {
    return this.data?.maintenance[this.periode] ?? 0;
  }

  private recalculerChartTickets(): void {
    const { resolus, refuses } = this.ticketsTraitesPeriode();
    this.chartTickets = {
      labels: ['Résolus', 'Refusés'],
      datasets: [{ data: [resolus, refuses], backgroundColor: [CHART_COLORS.success, CHART_COLORS.danger] }],
    };
  }
}
