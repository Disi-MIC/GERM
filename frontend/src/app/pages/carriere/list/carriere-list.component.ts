import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { HistoriqueAffectation } from '../../../core/models/historique-affectation.model';
import { Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { CarriereApiService } from '../carriere-api.service';

const LABELS_TYPE: Record<string, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Autre',
};

@Component({
  selector: 'app-carriere-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './carriere-list.component.html',
})
export class CarriereListComponent implements OnInit {
  mouvements: HistoriqueAffectation[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  readonly columns: DataTableColumn<HistoriqueAffectation>[] = [
    { key: 'agent', label: 'Agent', sortable: true, value: (m) => this.agentLabel(m) },
    { key: 'type', label: 'Type', sortable: true, value: (m) => this.labelsType[m.typeMouvement] ?? m.typeMouvement },
    { key: 'service', label: 'Service', sortable: true, value: (m) => this.serviceLabel(m) },
    { key: 'fonction', label: 'Fonction', sortable: true, value: (m) => m.fonction },
    { key: 'dateEffet', label: "Date d'effet", sortable: true, value: (m) => m.dateEffet },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: CarriereApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (mouvements) => {
        this.mouvements = mouvements;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le journal de carrière.';
        this.loading = false;
      },
    });
  }

  agentLabel(mouvement: HistoriqueAffectation): string {
    if (typeof mouvement.personnel === 'string') {
      return mouvement.personnel;
    }
    const personnel: Personnel = mouvement.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  serviceLabel(mouvement: HistoriqueAffectation): string {
    if (typeof mouvement.service === 'string') {
      return mouvement.service;
    }
    const service: ServiceRef = mouvement.service;
    return service.nom;
  }

  supprimer(mouvement: HistoriqueAffectation): void {
    if (!mouvement.id) {
      return;
    }
    if (!confirm('Supprimer ce mouvement de carrière ?')) {
      return;
    }
    this.api.delete(mouvement.id).subscribe({
      next: () => {
        this.mouvements = this.mouvements.filter((m) => m.id !== mouvement.id);
      },
      error: (err) => {
        this.error = err?.error?.errors?.mouvement ?? 'Erreur lors de la suppression.';
      },
    });
  }
}
