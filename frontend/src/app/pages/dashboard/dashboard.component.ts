import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ChartData } from 'chart.js';
import { PanelComponent } from '../../shared/panel/panel.component';
import { StatTileComponent } from '../../shared/stat-tile/stat-tile.component';
import { ChartComponent } from '../../shared/chart/chart.component';
import { CHART_COLORS } from '../../shared/chart/chart-colors';
import { DashboardPersonnel } from '../../core/models/dashboard.model';
import { DashboardApiService } from './dashboard-api.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [FormsModule, RouterLink, StatTileComponent, PanelComponent, ChartComponent],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  data: DashboardPersonnel | null = null;
  loading = true;
  error: string | null = null;
  filtreDirection: number | null = null;
  filtreService: number | null = null;

  /**
   * Champs simples recalculés une seule fois par chargement plutôt que des
   * getters : un getter reconstruirait un nouvel objet ChartData à chaque
   * cycle de détection de changement Angular, et <app-chart> le recevrait
   * comme "changé" en continu (ngOnChanges), rejouant l'animation Chart.js
   * en boucle pour rien.
   */
  chartParDirection: ChartData<'bar'> = { labels: [], datasets: [] };
  chartParService: ChartData<'bar'> = { labels: [], datasets: [] };

  readonly chartOptions = {
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
  };

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  onFiltreChange(): void {
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.getPersonnel(this.filtreDirection, this.filtreService).subscribe({
      next: (data) => {
        this.data = data;
        this.filtreDirection = data.filtreDirection;
        this.filtreService = data.filtreService;
        this.chartParDirection = this.repartitionChart(data.parDirection);
        this.chartParService = this.repartitionChart(data.parService);
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le tableau de bord.';
        this.loading = false;
      },
    });
  }

  private repartitionChart(parGroupe: Record<string, { M: number; F: number }>): ChartData<'bar'> {
    const entrees = Object.entries(parGroupe);
    return {
      labels: entrees.map(([nom]) => nom),
      datasets: [
        { label: 'Hommes', data: entrees.map(([, v]) => v.M), backgroundColor: CHART_COLORS.primary },
        { label: 'Femmes', data: entrees.map(([, v]) => v.F), backgroundColor: CHART_COLORS.info },
      ],
    };
  }
}
