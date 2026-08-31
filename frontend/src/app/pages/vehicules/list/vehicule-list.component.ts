import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { Vehicule } from '../../../core/models/vehicule.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { VehiculeApiService } from '../vehicule-api.service';

const BADGES_ETAT: Record<string, string> = {
  en_service: 'success',
  en_mission: 'info',
  en_panne: 'danger',
  en_maintenance: 'warning',
  reforme: 'secondary',
};

@Component({
  selector: 'app-vehicule-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './vehicule-list.component.html',
})
export class VehiculeListComponent implements OnInit {
  vehicules: Vehicule[] = [];
  loading = true;
  error: string | null = null;
  deleteError: string | null = null;

  readonly columns: DataTableColumn<Vehicule>[] = [
    { key: 'immatriculation', label: 'Immatriculation', sortable: true, value: (v) => v.immatriculation },
    { key: 'type', label: 'Type', sortable: true, value: (v) => this.libelle(v.type) },
    { key: 'marqueModele', label: 'Marque / Modèle', sortable: true, value: (v) => `${v.marque} ${v.modele}` },
    { key: 'service', label: 'Service', sortable: true, value: (v) => this.serviceLabel(v) },
    { key: 'chauffeur', label: 'Chauffeur affecté', sortable: true, value: (v) => this.chauffeurLabel(v) },
    { key: 'etat', label: 'État', sortable: true, value: (v) => this.libelle(v.etat) },
    { key: 'echeances', label: 'Échéances' },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: VehiculeApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.api.getAll().subscribe({
      next: (vehicules) => {
        this.vehicules = vehicules;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le parc automobile.';
        this.loading = false;
      },
    });
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  private codeEtat(vehicule: Vehicule): string {
    return typeof vehicule.etat === 'string' ? '' : vehicule.etat.code;
  }

  badgeClasseEtat(vehicule: Vehicule): string {
    return BADGES_ETAT[this.codeEtat(vehicule)] ?? 'secondary';
  }

  serviceLabel(vehicule: Vehicule): string {
    const service = vehicule.service as ServiceRef | string | null;
    return service && typeof service !== 'string' ? service.nom : '—';
  }

  chauffeurLabel(vehicule: Vehicule): string {
    const chauffeur = vehicule.chauffeurAffecte as Personnel | string | null | undefined;
    if (!chauffeur) {
      return '—';
    }
    return typeof chauffeur === 'string' ? chauffeur : (chauffeur.nomComplet ?? `${chauffeur.prenom} ${chauffeur.nom}`);
  }

  supprimer(vehicule: Vehicule): void {
    if (!vehicule.id) {
      return;
    }
    if (!confirm('Supprimer ce véhicule du parc automobile ? Cette action est irréversible.')) {
      return;
    }
    this.deleteError = null;
    this.api.delete(vehicule.id).subscribe({
      next: () => {
        this.vehicules = this.vehicules.filter((v) => v.id !== vehicule.id);
      },
      error: (err) => {
        this.deleteError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
