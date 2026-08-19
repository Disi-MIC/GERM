import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Direction } from '../../../core/models/direction.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { DirectionsApiService } from '../directions-api.service';

@Component({
  selector: 'app-direction-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './direction-list.component.html',
})
export class DirectionListComponent implements OnInit {
  directions: Direction[] = [];
  loading = true;
  error: string | null = null;
  deleteError: string | null = null;

  readonly columns: DataTableColumn<Direction>[] = [
    { key: 'code', label: 'Code', sortable: true, value: (d) => d.code },
    { key: 'nom', label: 'Nom', sortable: true, value: (d) => d.nom },
    { key: 'directeur', label: 'Directeur', sortable: true, value: (d) => d.directeurNom ?? '' },
    { key: 'actif', label: 'Actif', sortable: true, value: (d) => (d.actif ? 'Oui' : 'Non') },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: DirectionsApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (directions) => {
        this.directions = directions;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les directions.';
        this.loading = false;
      },
    });
  }

  supprimer(direction: Direction): void {
    if (!direction.id) {
      return;
    }
    if (!confirm('Supprimer cette direction ? Cette action est irréversible.')) {
      return;
    }
    this.deleteError = null;
    this.api.delete(direction.id).subscribe({
      next: () => (this.directions = this.directions.filter((d) => d.id !== direction.id)),
      error: (err) => {
        this.deleteError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
