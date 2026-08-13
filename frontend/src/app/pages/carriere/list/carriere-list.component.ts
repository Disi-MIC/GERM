import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { HistoriqueAffectation } from '../../../core/models/historique-affectation.model';
import { Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { CarriereApiService } from '../carriere-api.service';

const LABELS_TYPE: Record<string, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Autre',
};

@Component({
  selector: 'app-carriere-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './carriere-list.component.html',
})
export class CarriereListComponent implements OnInit {
  mouvements: HistoriqueAffectation[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: CarriereApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (mouvements) => {
        this.mouvements = mouvements;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le journal de carrière.';
        this.loading = false;
      },
    });
  }

  agentLabel(mouvement: HistoriqueAffectation): string {
    if (typeof mouvement.personnel === 'string') {
      return mouvement.personnel;
    }
    const personnel: Personnel = mouvement.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  serviceLabel(mouvement: HistoriqueAffectation): string {
    if (typeof mouvement.service === 'string') {
      return mouvement.service;
    }
    const service: ServiceRef = mouvement.service;
    return service.nom;
  }

  supprimer(mouvement: HistoriqueAffectation): void {
    if (!mouvement.id) {
      return;
    }
    if (!confirm('Supprimer ce mouvement de carrière ?')) {
      return;
    }
    this.api.delete(mouvement.id).subscribe({
      next: () => {
        this.mouvements = this.mouvements.filter((m) => m.id !== mouvement.id);
      },
      error: (err) => {
        this.error = err?.error?.errors?.mouvement ?? 'Erreur lors de la suppression.';
      },
    });
  }
}
