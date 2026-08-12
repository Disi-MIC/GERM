import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { LicenceLogiciel } from '../../../core/models/licence-logiciel.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { LicencesLogiciellesApiService } from '../licences-logicielles-api.service';

@Component({
  selector: 'app-licences-logicielles-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './licences-logicielles-list.component.html',
})
export class LicencesLogiciellesListComponent implements OnInit {
  licences: LicenceLogiciel[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: LicencesLogiciellesApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (licences) => {
        this.licences = licences;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le registre des licences.';
        this.loading = false;
      },
    });
  }

  logicielLabel(licence: LicenceLogiciel): string {
    const logiciel = licence.logiciel as ListeValeurRef | string;
    return typeof logiciel === 'string' ? logiciel : logiciel.libelle;
  }

  estExpiree(licence: LicenceLogiciel): boolean {
    return !!licence.dateExpiration && licence.dateExpiration < new Date().toISOString().substring(0, 10);
  }

  supprimer(licence: LicenceLogiciel): void {
    if (!licence.id) {
      return;
    }
    if (!confirm('Supprimer cette licence du registre ?')) {
      return;
    }
    this.api.delete(licence.id).subscribe({
      next: () => {
        this.licences = this.licences.filter((l) => l.id !== licence.id);
      },
      error: (err) => {
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
