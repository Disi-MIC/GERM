import { Component, OnInit } from '@angular/core';
import { ChartData } from 'chart.js';
import { ApercuMaDirection } from '../../../core/models/apercu-organisation.model';
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

/** Aperçu du directeur : effectif, répartition par service et liste des agents de toute sa direction. */
@Component({
  selector: 'app-apercu-direction',
  standalone: true,
  imports: [PageHeaderComponent, PanelComponent, StatTileComponent, ChartComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './apercu-direction.component.html',
})
export class ApercuDirectionComponent implements OnInit {
  data: ApercuMaDirection | null = null;
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

  /** Objet reconstruit une seule fois par chargement — voir dashboard.component.ts pour la raison (éviter de rejouer l'animation Chart.js à chaque cycle de détection). */
  chartParService: ChartData<'bar'> = { labels: [], datasets: [] };

  readonly chartOptions = {
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    plugins: { legend: { display: false } },
  };

  readonly columns: DataTableColumn<ApercuMaDirection['agents'][number]>[] = [
    { key: 'matricule', label: 'Matricule', sortable: true, value: (a) => a.matricule ?? '' },
    { key: 'nomComplet', label: 'Nom complet', sortable: true, value: (a) => a.nomComplet },
    { key: 'serviceNom', label: 'Service', sortable: true, value: (a) => a.serviceNom ?? '' },
    { key: 'grade', label: 'Grade', sortable: true, value: (a) => a.grade ?? '' },
    { key: 'fonction', label: 'Fonction', sortable: true, value: (a) => a.fonction ?? '' },
    { key: 'statut', label: 'Statut', sortable: true, value: (a) => this.labelsStatut[a.statut ?? ''] ?? a.statut ?? '' },
  ];

  constructor(private readonly api: ApercuOrganisationApiService) {}

  ngOnInit(): void {
    this.api.maDirection().subscribe({
      next: (data) => {
        this.data = data;
        this.chartParService = {
          labels: data.services.map((s) => s.nom),
          datasets: [{ label: 'Agents', data: data.services.map((s) => s.nbAgents), backgroundColor: CHART_COLORS.primary }],
        };
        this.loading = false;
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu de votre direction.";
        this.loading = false;
      },
    });
  }
}
