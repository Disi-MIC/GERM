import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { HistoriqueAffectation } from '../../../core/models/historique-affectation.model';
import { ServiceRef } from '../../../core/models/personnel.model';
import { ProfilApiService } from '../profil-api.service';

const LABELS_TYPE_MOUVEMENT: Record<string, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Autre',
};

@Component({
  selector: 'app-ma-carriere',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './ma-carriere.component.html',
})
export class MaCarriereComponent implements OnInit {
  mouvements: HistoriqueAffectation[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsTypeMouvement = LABELS_TYPE_MOUVEMENT;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMaCarriere().subscribe({
      next: (mouvements) => {
        this.mouvements = mouvements;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre historique de carrière.';
        this.loading = false;
      },
    });
  }

  serviceLabel(mouvement: HistoriqueAffectation): string {
    const service = mouvement.service;
    return service && typeof service !== 'string' ? (service as ServiceRef).nom : '';
  }
}
