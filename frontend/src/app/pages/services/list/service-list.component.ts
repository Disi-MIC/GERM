import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Service } from '../../../core/models/service.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ServicesApiService } from '../services-api.service';

@Component({
  selector: 'app-service-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './service-list.component.html',
})
export class ServiceListComponent implements OnInit {
  services: Service[] = [];
  loading = true;
  error: string | null = null;
  deleteError: string | null = null;

  readonly columns: DataTableColumn<Service>[] = [
    { key: 'code', label: 'Code', sortable: true, value: (s) => s.code },
    { key: 'nom', label: 'Nom', sortable: true, value: (s) => s.nom },
    { key: 'direction', label: 'Direction', sortable: true, value: (s) => this.directionLabel(s) },
    { key: 'responsable', label: 'Responsable', sortable: true, value: (s) => s.responsableNom ?? '' },
    { key: 'actif', label: 'Actif', sortable: true, value: (s) => (s.actif ? 'Oui' : 'Non') },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: ServicesApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (services) => {
        this.services = services;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les services.';
        this.loading = false;
      },
    });
  }

  directionLabel(service: Service): string {
    return service.direction && typeof service.direction !== 'string' ? service.direction.nom : '—';
  }

  supprimer(service: Service): void {
    if (!service.id) {
      return;
    }
    if (!confirm('Supprimer ce service ? Cette action est irréversible.')) {
      return;
    }
    this.deleteError = null;
    this.api.delete(service.id).subscribe({
      next: () => (this.services = this.services.filter((s) => s.id !== service.id)),
      error: (err) => {
        this.deleteError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
