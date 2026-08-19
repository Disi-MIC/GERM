import { Component, OnInit } from '@angular/core';
import { ApercuMonService } from '../../../core/models/apercu-organisation.model';
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
  imports: [PageHeaderComponent, PanelComponent, StatTileComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './apercu-service.component.html',
})
export class ApercuServiceComponent implements OnInit {
  data: ApercuMonService | null = null;
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

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
        this.loading = false;
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu de votre service.";
        this.loading = false;
      },
    });
  }
}
