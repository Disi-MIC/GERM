import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

const BADGES_ETAT: Record<string, string> = {
  en_service: 'success',
  en_stock: 'info',
  en_panne: 'danger',
  en_maintenance: 'warning',
  reforme: 'secondary',
};

const LABELS_ETAT: Record<string, string> = {
  en_service: 'En service',
  en_stock: 'En stock',
  en_panne: 'En panne',
  en_maintenance: 'En maintenance',
  reforme: 'Réformé',
};

@Component({
  selector: 'app-materiel-informatique-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './materiel-informatique-list.component.html',
})
export class MaterielInformatiqueListComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  materielsAffiches: MaterielInformatique[] = [];
  loading = true;
  error: string | null = null;
  filtreEtat: string | null = null;
  compteurs: Record<string, number> = {};
  readonly etats = Object.keys(BADGES_ETAT);
  readonly labelsEtat = LABELS_ETAT;

  constructor(private readonly api: MaterielInformatiqueApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (materiels) => {
        this.materiels = materiels;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le parc informatique.';
        this.loading = false;
      },
    });
  }

  private codeEtat(materiel: MaterielInformatique): string {
    return typeof materiel.etat === 'string' ? '' : materiel.etat.code;
  }

  private recalculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const etat of this.etats) {
      compteurs[etat] = 0;
    }
    for (const materiel of this.materiels) {
      const code = this.codeEtat(materiel);
      compteurs[code] = (compteurs[code] ?? 0) + 1;
    }
    this.compteurs = compteurs;
  }

  private appliquerFiltre(): void {
    this.materielsAffiches = this.filtreEtat
      ? this.materiels.filter((m) => this.codeEtat(m) === this.filtreEtat)
      : this.materiels;
  }

  filtrer(etat: string): void {
    this.filtreEtat = this.filtreEtat === etat ? null : etat;
    this.appliquerFiltre();
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  badgeClasseEtat(materiel: MaterielInformatique): string {
    return BADGES_ETAT[this.codeEtat(materiel)] ?? 'secondary';
  }

  serviceLabel(materiel: MaterielInformatique): string {
    const service = materiel.service as ServiceRef | string;
    return typeof service === 'string' ? service : service.nom;
  }

  affecteLabel(materiel: MaterielInformatique): string {
    const personnel = materiel.affecteA as Personnel | string | null | undefined;
    if (!personnel) {
      return '—';
    }
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  supprimer(materiel: MaterielInformatique): void {
    if (!materiel.id) {
      return;
    }
    if (!confirm('Supprimer ce matériel du parc informatique ? Cette action est irréversible.')) {
      return;
    }
    this.api.delete(materiel.id).subscribe({
      next: () => {
        this.materiels = this.materiels.filter((m) => m.id !== materiel.id);
        this.recalculerCompteurs();
        this.appliquerFiltre();
      },
      error: (err) => {
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
