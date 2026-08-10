import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

@Component({
  selector: 'app-demande-decision-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './demande-decision-list.component.html',
})
export class DemandeDecisionListComponent implements OnInit {
  demandes: DemandeDecision[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

  constructor(private readonly api: DemandeDecisionApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de décision.';
        this.loading = false;
      },
    });
  }

  agentLabel(demande: DemandeDecision): string {
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
