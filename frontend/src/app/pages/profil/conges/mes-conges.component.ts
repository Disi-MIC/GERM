import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { Conge, DecisionConge, DemandeDecision, DemandeJouissance } from '../../../core/models/conge.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableMobileItemDirective } from '../../../shared/data-table/data-table-mobile-item.directive';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
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
  imports: [SlicePipe, RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective, DataTableMobileItemDirective],
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

  readonly colonnesConges: DataTableColumn<Conge>[] = [
    { key: 'type', label: 'Type', sortable: true, value: (c) => this.labelsType[c.type] ?? c.type },
    { key: 'debut', label: 'Date début', sortable: true, value: (c) => c.dateDebut },
    { key: 'fin', label: 'Date fin', sortable: true, value: (c) => c.dateFin },
    { key: 'duree', label: 'Durée', sortable: true, value: (c) => c.duree },
    { key: 'motif', label: 'Motif', sortable: true, value: (c) => c.motif ?? '' },
  ];

  readonly colonnesDecisions: DataTableColumn<DecisionConge>[] = [
    { key: 'numero', label: 'Numéro', sortable: true, value: (d) => d.numeroDecision },
    { key: 'dateDecision', label: 'Date de décision', sortable: true, value: (d) => d.dateDecision },
    { key: 'expiration', label: 'Expire le', sortable: true, value: (d) => d.dateExpiration },
  ];

  readonly colonnesDemandesDecision: DataTableColumn<DemandeDecision>[] = [
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => this.labelsStatut[d.statut ?? ''] ?? '' },
    { key: 'creeeLe', label: 'Créée le', sortable: true, value: (d) => d.createdAt },
    { key: 'traiteeLe', label: 'Traitée le', sortable: true, value: (d) => d.dateTraitement ?? '' },
    { key: 'commentaire', label: 'Commentaire', sortable: false, value: (d) => d.commentaireTraitement ?? '' },
  ];

  readonly colonnesDemandesJouissance: DataTableColumn<DemandeJouissance>[] = [
    { key: 'type', label: 'Type', sortable: true, value: (d) => this.labelsType[d.type] ?? d.type },
    { key: 'dates', label: 'Dates', sortable: true, value: (d) => d.dateDebut },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => this.labelsStatut[d.statut ?? ''] ?? '' },
    { key: 'traiteeLe', label: 'Traitée le', sortable: true, value: (d) => d.dateTraitement ?? '' },
    { key: 'commentaire', label: 'Commentaire', sortable: false, value: (d) => d.commentaireTraitement ?? '' },
  ];

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
