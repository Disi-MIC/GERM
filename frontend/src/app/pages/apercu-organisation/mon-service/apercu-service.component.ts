import { Component, OnInit } from '@angular/core';
import { ChartData } from 'chart.js';
import { ApercuMonService } from '../../../core/models/apercu-organisation.model';
import { CHART_COLORS } from '../../../shared/chart/chart-colors';
import { ChartComponent } from '../../../shared/chart/chart.component';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { StatTileComponent } from '../../../shared/stat-tile/stat-tile.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ApercuOrganisationApiService } from '../apercu-organisation-api.service';

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

/** Aperçu du chef de service / coordonnateur : effectif et liste des agents de son seul service. */
@Component({
  selector: 'app-apercu-service',
  standalone: true,
  imports: [PageHeaderComponent, PanelComponent, StatTileComponent, ChartComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './apercu-service.component.html',
})
export class ApercuServiceComponent implements OnInit {
  data: ApercuMonService | null = null;
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

  /** Objets reconstruits une seule fois par chargement — voir dashboard.component.ts pour la raison (éviter de rejouer l'animation Chart.js à chaque cycle de détection). */
  chartParGrade: ChartData<'bar'> = { labels: [], datasets: [] };
  chartMaterielParEtat: ChartData<'doughnut'> = { labels: [], datasets: [] };
  chartMaterielParVulnerabilite: ChartData<'bar'> = { labels: [], datasets: [] };

  readonly chartOptionsGrade = {
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    plugins: { legend: { display: false } },
  };

  readonly chartOptionsVulnerabilite = {
    indexAxis: 'y' as const,
    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
    plugins: { legend: { display: false } },
  };

  readonly columns: DataTableColumn<ApercuMonService['agents'][number]>[] = [
    { key: 'matricule', label: 'Matricule', sortable: true, value: (a) => a.matricule ?? '' },
    { key: 'nomComplet', label: 'Nom complet', sortable: true, value: (a) => a.nomComplet },
    { key: 'grade', label: 'Grade', sortable: true, value: (a) => a.grade ?? '' },
    { key: 'fonction', label: 'Fonction', sortable: true, value: (a) => a.fonction ?? '' },
    { key: 'statut', label: 'Statut', sortable: true, value: (a) => this.labelsStatut[a.statut ?? ''] ?? a.statut ?? '' },
  ];

  constructor(private readonly api: ApercuOrganisationApiService) {}

  ngOnInit(): void {
    this.api.monService().subscribe({
      next: (data) => {
        this.data = data;
        this.chartParGrade = {
          labels: Object.keys(data.parGrade),
          datasets: [{ label: 'Agents', data: Object.values(data.parGrade), backgroundColor: CHART_COLORS.primary }],
        };
        this.chartMaterielParEtat = {
          labels: Object.keys(data.materiel.parEtat),
          datasets: [{ data: Object.values(data.materiel.parEtat), backgroundColor: Object.values(CHART_COLORS) }],
        };
        this.chartMaterielParVulnerabilite = {
          labels: Object.keys(data.materiel.parVulnerabilite),
          datasets: [
            { label: 'Matériels', data: Object.values(data.materiel.parVulnerabilite), backgroundColor: CHART_COLORS.danger },
          ],
        };
        this.loading = false;
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu de votre service.";
        this.loading = false;
      },
    });
  }
}
