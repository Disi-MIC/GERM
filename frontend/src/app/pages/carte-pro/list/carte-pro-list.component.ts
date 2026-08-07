import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { CarteProApiService } from '../carte-pro-api.service';

const LABELS_COMPTEURS = ['Valide', 'Expire bientôt', 'Expirée', 'Perdue', 'Volée', 'Annulée'] as const;

@Component({
  selector: 'app-carte-pro-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './carte-pro-list.component.html',
})
export class CarteProListComponent implements OnInit {
  cartes: CarteProfessionnelle[] = [];
  cartesAffichees: CarteProfessionnelle[] = [];
  loading = true;
  error: string | null = null;
  filtreStatutAffiche: string | null = null;
  compteurs: Record<string, number> = {};
  enAttenteValidation = 0;
  readonly labelsCompteurs = LABELS_COMPTEURS;

  constructor(
    private readonly api: CarteProApiService,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (cartes) => {
        this.cartes = cartes;
        this.calculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger la liste des cartes professionnelles.';
        this.loading = false;
      },
    });
  }

  private calculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const label of LABELS_COMPTEURS) {
      compteurs[label] = 0;
    }

    let enAttente = 0;
    for (const carte of this.cartes) {
      const label = carte.statutAffiche?.label ?? '';
      compteurs[label] = (compteurs[label] ?? 0) + 1;
      if (!carte.valideeParAdminRh) {
        enAttente++;
      }
    }

    this.compteurs = compteurs;
    this.enAttenteValidation = enAttente;
  }

  filtrer(label: string | null): void {
    this.filtreStatutAffiche = this.filtreStatutAffiche === label ? null : label;
    this.appliquerFiltre();
  }

  private appliquerFiltre(): void {
    this.cartesAffichees = this.filtreStatutAffiche
      ? this.cartes.filter((c) => c.statutAffiche?.label === this.filtreStatutAffiche)
      : this.cartes;
  }

  agentLabel(carte: CarteProfessionnelle): string {
    return typeof carte.personnel === 'string' ? carte.personnel : this.nomComplet(carte.personnel);
  }

  private nomComplet(personnel: Personnel): string {
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  supprimer(carte: CarteProfessionnelle): void {
    if (!carte.id) {
      return;
    }
    if (!confirm(`Supprimer la carte ${carte.numero} ? Cette action est irréversible.`)) {
      return;
    }

    this.api.delete(carte.id).subscribe({
      next: () => {
        this.cartes = this.cartes.filter((c) => c.id !== carte.id);
        this.calculerCompteurs();
        this.appliquerFiltre();
      },
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
