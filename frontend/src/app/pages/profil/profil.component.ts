import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../core/auth.service';
import { Personnel } from '../../core/models/personnel.model';
import { PageHeaderComponent } from '../../shared/page-header/page-header.component';
import { PanelComponent } from '../../shared/panel/panel.component';
import { ProfilApiService } from './profil-api.service';

const LABELS_SEXE: Record<string, string> = {
  M: 'Homme',
  F: 'Femme',
};

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

const BADGES_STATUT: Record<string, string> = {
  actif: 'success',
  en_conge: 'info',
  suspendu: 'warning',
  retraite: 'secondary',
  demissionnaire: 'danger',
};

@Component({
  selector: 'app-profil',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './profil.component.html',
})
export class ProfilComponent implements OnInit {
  personnel: Personnel | null = null;
  loading = true;
  error: string | null = null;
  readonly labelsSexe = LABELS_SEXE;
  readonly labelsStatut = LABELS_STATUT;

  constructor(
    private readonly api: ProfilApiService,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.api.getMonPersonnel().subscribe({
      next: (personnel) => {
        this.personnel = personnel;
        this.loading = false;
      },
      error: (err) => {
        this.error = err?.error?.errors?.personnel ?? 'Impossible de charger votre fiche personnel.';
        this.loading = false;
      },
    });
  }

  photoUrl(): string {
    return this.api.photoUrl();
  }

  serviceLabel(): string {
    const service = this.personnel?.service;
    return service && typeof service !== 'string' ? service.nom : '';
  }

  typeContratLabel(): string {
    const type = this.personnel?.typeContrat;
    return type && typeof type !== 'string' ? type.libelle : '';
  }

  badgeClasseStatut(statut: string): string {
    return BADGES_STATUT[statut] ?? 'secondary';
  }
}
