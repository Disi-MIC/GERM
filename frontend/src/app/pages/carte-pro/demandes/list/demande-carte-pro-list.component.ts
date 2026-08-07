import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-demande-carte-pro-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './demande-carte-pro-list.component.html',
})
export class DemandeCarteProListComponent implements OnInit {
  demandes: DemandeCartePro[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: DemandeCarteProApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de carte professionnelle.';
        this.loading = false;
      },
    });
  }

  agentLabel(demande: DemandeCartePro): string {
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
