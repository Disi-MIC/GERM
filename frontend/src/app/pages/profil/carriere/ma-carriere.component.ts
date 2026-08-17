import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { HistoriqueAffectation } from '../../../core/models/historique-affectation.model';
import { ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableMobileItemDirective } from '../../../shared/data-table/data-table-mobile-item.directive';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ProfilApiService } from '../profil-api.service';

const ICONES_TYPE_MOUVEMENT: Record<string, string> = {
  nomination: 'bi-award',
  mutation: 'bi-arrow-left-right',
  promotion: 'bi-graph-up-arrow',
  autre: 'bi-three-dots',
};

const LABELS_TYPE_MOUVEMENT: Record<string, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Autre',
};

@Component({
  selector: 'app-ma-carriere',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective, DataTableMobileItemDirective],
  templateUrl: './ma-carriere.component.html',
})
export class MaCarriereComponent implements OnInit {
  mouvements: HistoriqueAffectation[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsTypeMouvement = LABELS_TYPE_MOUVEMENT;

  readonly columns: DataTableColumn<HistoriqueAffectation>[] = [
    { key: 'dateEffet', label: "Date d'effet", sortable: true, value: (m) => m.dateEffet },
    { key: 'mouvement', label: 'Mouvement', sortable: true, value: (m) => this.labelsTypeMouvement[m.typeMouvement] ?? m.typeMouvement },
    { key: 'service', label: 'Service', sortable: true, value: (m) => this.serviceLabel(m) },
    { key: 'fonction', label: 'Fonction', sortable: true, value: (m) => m.fonction },
    { key: 'grade', label: 'Grade', sortable: true, value: (m) => m.grade ?? '' },
    { key: 'decision', label: 'Décision', sortable: true, value: (m) => m.numeroDecision ?? '' },
  ];

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMaCarriere().subscribe({
      next: (mouvements) => {
        this.mouvements = mouvements;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre historique de carrière.';
        this.loading = false;
      },
    });
  }

  serviceLabel(mouvement: HistoriqueAffectation): string {
    const service = mouvement.service;
    return service && typeof service !== 'string' ? (service as ServiceRef).nom : '';
  }

  iconeMouvement(mouvement: HistoriqueAffectation): string {
    return ICONES_TYPE_MOUVEMENT[mouvement.typeMouvement] ?? 'bi-three-dots';
  }
}
