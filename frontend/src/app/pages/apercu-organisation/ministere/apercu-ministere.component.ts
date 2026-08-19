import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ChartData } from 'chart.js';
import { ApercuMinistere, RepartitionSexe } from '../../../core/models/apercu-organisation.model';
import { CHART_COLORS } from '../../../shared/chart/chart-colors';
import { ChartComponent } from '../../../shared/chart/chart.component';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { StatTileComponent } from '../../../shared/stat-tile/stat-tile.component';
import { ApercuOrganisationApiService } from '../apercu-organisation-api.service';

/** Aperçu global du Ministère (SG / DC / Ministre) : effectifs, services, directions et parc informatique. */
@Component({
  selector: 'app-apercu-ministere',
  standalone: true,
  imports: [FormsModule, PageHeaderComponent, PanelComponent, StatTileComponent, ChartComponent],
  templateUrl: './apercu-ministere.component.html',
})
export class ApercuMinistereComponent implements OnInit {
  data: ApercuMinistere | null = null;
  loading = true;
  error: string | null = null;
  filtreDirection: number | null = null;
  filtreService: number | null = null;
  filtreGrade: string | null = null;

  /** Objets reconstruits une seule fois par chargement — voir dashboard.component.ts pour la raison (éviter de rejouer l'animation Chart.js à chaque cycle de détection). */
  chartParDirection: ChartData<'bar'> = { labels: [], datasets: [] };
  chartParService: ChartData<'bar'> = { labels: [], datasets: [] };
  chartParGrade: ChartData<'bar'> = { labels: [], datasets: [] };
  chartMaterielParEtat: ChartData<'doughnut'> = { labels: [], datasets: [] };

  readonly chartOptionsSexe = {
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
  };

  constructor(private readonly api: ApercuOrganisationApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  onFiltreChange(): void {
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.ministere(this.filtreDirection, this.filtreService, this.filtreGrade).subscribe({
      next: (data) => {
        this.data = data;
        this.filtreDirection = data.filtreDirection;
        this.filtreService = data.filtreService;
        this.filtreGrade = data.filtreGrade;
        this.chartParDirection = this.repartitionChart(data.parDirection);
        this.chartParService = this.repartitionChart(data.parService);
        this.chartParGrade = this.repartitionChart(data.parGrade);
        this.chartMaterielParEtat = {
          labels: Object.keys(data.materiel.parEtat),
          datasets: [{ data: Object.values(data.materiel.parEtat), backgroundColor: Object.values(CHART_COLORS) }],
        };
        this.loading = false;
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu du Ministère.";
        this.loading = false;
      },
    });
  }

  private repartitionChart(parGroupe: Record<string, RepartitionSexe>): ChartData<'bar'> {
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
