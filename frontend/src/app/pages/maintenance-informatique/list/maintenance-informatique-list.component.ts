import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Maintenance, TypeMaintenance } from '../../../core/models/maintenance.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { Personnel } from '../../../core/models/personnel.model';
import { MaintenanceInformatiqueApiService } from '../maintenance-informatique-api.service';

const LABELS_TYPE: Record<TypeMaintenance, string> = {
  preventive: 'Préventive',
  corrective: 'Corrective',
};

@Component({
  selector: 'app-maintenance-informatique-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './maintenance-informatique-list.component.html',
})
export class MaintenanceInformatiqueListComponent implements OnInit {
  maintenances: Maintenance[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: MaintenanceInformatiqueApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (maintenances) => {
        this.maintenances = maintenances;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le journal de maintenance.';
        this.loading = false;
      },
    });
  }

  materielLabel(maintenance: Maintenance): string {
    const materiel = maintenance.materiel as MaterielInformatique | string;
    return typeof materiel === 'string' ? materiel : `${materiel.marque} ${materiel.modele} (${materiel.numeroInventaire})`;
  }

  realiseParLabel(maintenance: Maintenance): string {
    if (maintenance.prestataireExterne) {
      return maintenance.prestataireExterne;
    }
    const personnel = maintenance.realisePar as Personnel | string | null | undefined;
    if (!personnel) {
      return '—';
    }
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  supprimer(maintenance: Maintenance): void {
    if (!maintenance.id) {
      return;
    }
    if (!confirm('Supprimer cette entrée de maintenance ?')) {
      return;
    }
    this.api.delete(maintenance.id).subscribe({
      next: () => {
        this.maintenances = this.maintenances.filter((m) => m.id !== maintenance.id);
      },
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
