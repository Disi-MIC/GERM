import { Component, OnInit } from '@angular/core';
import { ChartData } from 'chart.js';
import { PanelComponent } from '../../shared/panel/panel.component';
import { StatTileComponent } from '../../shared/stat-tile/stat-tile.component';
import { ChartComponent } from '../../shared/chart/chart.component';
import { CHART_COLORS } from '../../shared/chart/chart-colors';
import { DashboardConges } from '../../core/models/dashboard.model';
import { DashboardApiService } from '../dashboard/dashboard-api.service';

type PeriodeKey = 'aujourdhui' | 'semaine' | 'mois' | 'total';

const LABELS_PERIODE: Record<PeriodeKey, string> = {
  aujourdhui: "Aujourd'hui",
  semaine: 'Cette semaine',
  mois: 'Ce mois',
  total: 'Depuis toujours',
};

@Component({
  selector: 'app-dashboard-conges',
  standalone: true,
  imports: [StatTileComponent, PanelComponent, ChartComponent],
  templateUrl: './dashboard-conges.component.html',
})
export class DashboardCongesComponent implements OnInit {
  data: DashboardConges | null = null;
  loading = true;
  error: string | null = null;
  periode: PeriodeKey = 'mois';
  readonly periodes: PeriodeKey[] = ['aujourdhui', 'semaine', 'mois', 'total'];
  readonly labelsPeriode = LABELS_PERIODE;

  /** Champ simple recalculé à chaque chargement/changement de période plutôt qu'un getter — voir dashboard.component.ts. */
  chartTraites: ChartData<'doughnut'> = { labels: [], datasets: [] };

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.loading = true;
    this.api.getConges().subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
        this.recalculerChart();
      },
      error: () => {
        this.error = 'Impossible de charger le tableau de bord.';
        this.loading = false;
      },
    });
  }

  selectionnerPeriode(periode: PeriodeKey): void {
    this.periode = periode;
    this.recalculerChart();
  }

  traitesPeriode() {
    return this.data?.traites[this.periode] ?? { approuvees: 0, refusees: 0 };
  }

  private recalculerChart(): void {
    const { approuvees, refusees } = this.traitesPeriode();
    this.chartTraites = {
      labels: ['Approuvées', 'Refusées'],
      datasets: [{ data: [approuvees, refusees], backgroundColor: [CHART_COLORS.success, CHART_COLORS.danger] }],
    };
  }
}
