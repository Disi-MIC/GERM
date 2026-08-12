import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { LicenceLogiciel } from '../../../core/models/licence-logiciel.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { MaterielInformatiqueApiService } from '../../materiel-informatique/materiel-informatique-api.service';
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
  private materiels: MaterielInformatique[] = [];

  constructor(
    private readonly api: LicencesLogiciellesApiService,
    private readonly materielApi: MaterielInformatiqueApiService,
  ) {}

  ngOnInit(): void {
    forkJoin({ licences: this.api.getAll(), materiels: this.materielApi.getAll() }).subscribe({
      next: ({ licences, materiels }) => {
        this.licences = licences;
        this.materiels = materiels;
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

  /**
   * Postes couverts = matériels dont l'OS/la suite bureautique/l'antivirus
   * référence ce logiciel, compté à la volée plutôt que saisi à la main —
   * toujours à jour au fur et à mesure que du matériel est enregistré.
   */
  postesCouverts(licence: LicenceLogiciel): number {
    const logiciel = licence.logiciel as ListeValeurRef | string;
    const logicielId = typeof logiciel === 'string' ? null : logiciel.id;
    if (logicielId === null) {
      return 0;
    }
    return this.materiels.filter((materiel) => this.referenceLogiciel(materiel, logicielId)).length;
  }

  private referenceLogiciel(materiel: MaterielInformatique, logicielId: number): boolean {
    for (const champ of [materiel.systemeExploitation, materiel.suiteBureautique, materiel.antivirus]) {
      if (champ && typeof champ !== 'string' && champ.id === logicielId) {
        return true;
      }
    }
    return false;
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
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
