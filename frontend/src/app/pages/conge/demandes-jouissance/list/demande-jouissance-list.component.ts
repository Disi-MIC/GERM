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
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: DemandeJouissanceApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de jouissance.';
        this.loading = false;
      },
    });
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
