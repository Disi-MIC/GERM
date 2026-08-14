import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { PersonnelApiService } from '../personnel-api.service';

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

const COULEURS_STATUT: Record<string, string> = {
  actif: 'success',
  en_conge: 'info',
  suspendu: 'warning',
  retraite: 'secondary',
  demissionnaire: 'danger',
};

@Component({
  selector: 'app-personnel-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './personnel-list.component.html',
})
export class PersonnelListComponent implements OnInit {
  personnels: Personnel[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

  readonly columns: DataTableColumn<Personnel>[] = [
    { key: 'matricule', label: 'Matricule', sortable: true, value: (p) => p.matricule ?? '' },
    { key: 'nomComplet', label: 'Nom complet', sortable: true, value: (p) => p.nomComplet },
    { key: 'fonction', label: 'Fonction', sortable: true, value: (p) => p.fonction },
    { key: 'service', label: 'Service', sortable: true, value: (p) => this.serviceLabel(p) },
    { key: 'statut', label: 'Statut', sortable: true, value: (p) => this.labelsStatut[p.statut] ?? p.statut },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: PersonnelApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (personnels) => {
        this.personnels = personnels;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger la liste du personnel.';
        this.loading = false;
      },
    });
  }

  serviceLabel(personnel: Personnel): string {
    return typeof personnel.service === 'string' ? personnel.service : personnel.service.nom;
  }

  badgeClasseStatut(statut: string): string {
    return COULEURS_STATUT[statut] ?? 'secondary';
  }
}
