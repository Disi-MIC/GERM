import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { Conge, DecisionConge, DemandeDecision, DemandeJouissance } from '../../../core/models/conge.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ProfilApiService } from '../profil-api.service';

const LABELS_TYPE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

@Component({
  selector: 'app-mes-conges',
  standalone: true,
  imports: [SlicePipe, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './mes-conges.component.html',
})
export class MesCongesComponent implements OnInit {
  conges: Conge[] = [];
  decisions: DecisionConge[] = [];
  demandesDecision: DemandeDecision[] = [];
  demandesJouissance: DemandeJouissance[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;
  readonly labelsStatut = LABELS_STATUT;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    forkJoin({
      conges: this.api.getMesConges(),
      decisions: this.api.getMesDecisionsConge(),
      demandesDecision: this.api.getMesDemandesDecision(),
      demandesJouissance: this.api.getMesDemandesJouissance(),
    }).subscribe({
      next: ({ conges, decisions, demandesDecision, demandesJouissance }) => {
        this.conges = conges;
        this.decisions = decisions;
        this.demandesDecision = demandesDecision;
        this.demandesJouissance = demandesJouissance;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos congés.';
        this.loading = false;
      },
    });
  }

  badgeClasse(statut: string | undefined): string {
    return statut === 'approuvee' ? 'success' : statut === 'refusee' ? 'danger' : 'secondary';
  }
}
