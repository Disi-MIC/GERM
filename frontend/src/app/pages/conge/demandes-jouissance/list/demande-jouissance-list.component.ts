import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DemandeJouissance } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { DemandeJouissanceApiService } from '../../demande-jouissance-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

@Component({
  selector: 'app-demande-jouissance-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './demande-jouissance-list.component.html',
})
export class DemandeJouissanceListComponent implements OnInit {
  demandes: DemandeJouissance[] = [];
  demandesAffichees: DemandeJouissance[] = [];
  loading = true;
  error: string | null = null;
  filtreStatut: string | null = null;
  compteurs: Record<string, number> = {};
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;
  readonly statuts = Object.keys(LABELS_STATUT);

  constructor(private readonly api: DemandeJouissanceApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de jouissance.';
        this.loading = false;
      },
    });
  }

  private recalculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const statut of this.statuts) {
      compteurs[statut] = 0;
    }
    for (const demande of this.demandes) {
      const statut = demande.statut ?? '';
      compteurs[statut] = (compteurs[statut] ?? 0) + 1;
    }
    this.compteurs = compteurs;
  }

  private appliquerFiltre(): void {
    this.demandesAffichees = this.filtreStatut
      ? this.demandes.filter((d) => d.statut === this.filtreStatut)
      : this.demandes;
  }

  filtrer(statut: string): void {
    this.filtreStatut = this.filtreStatut === statut ? null : statut;
    this.appliquerFiltre();
  }

  agentLabel(demande: DemandeJouissance): string {
    if (typeof demande.personnel === 'string') {
      return demande.personnel;
    }
    const personnel: Personnel = demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  badgeClass(statut: string | undefined): string {
    switch (statut) {
      case 'approuvee':
        return 'success';
      case 'refusee':
        return 'danger';
      default:
        return 'secondary';
    }
  }
}
