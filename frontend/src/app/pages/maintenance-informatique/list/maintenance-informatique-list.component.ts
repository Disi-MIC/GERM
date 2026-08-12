import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Maintenance } from '../../../core/models/maintenance.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { MaintenanceInformatiqueApiService } from '../maintenance-informatique-api.service';

/** Couleur pour le code 'corrective' livré par défaut — les autres retombent sur 'info' (voir badgeClasseType). */
const CODES_CORRECTIFS = ['corrective'];

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

  typeLabel(maintenance: Maintenance): string {
    const type = maintenance.type as ListeValeurRef | string;
    return typeof type === 'string' ? type : type.libelle;
  }

  badgeClasseType(maintenance: Maintenance): string {
    const type = maintenance.type as ListeValeurRef | string;
    const code = typeof type === 'string' ? type : type.code;
    return CODES_CORRECTIFS.includes(code) ? 'warning' : 'info';
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
