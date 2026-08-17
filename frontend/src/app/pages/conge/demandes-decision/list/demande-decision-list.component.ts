import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../../shared/data-table/data-table.component';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  transmise_agent: "Transmise à l'agent",
  refusee: 'Refusée',
};

@Component({
  selector: 'app-demande-decision-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './demande-decision-list.component.html',
})
export class DemandeDecisionListComponent implements OnInit {
  demandes: DemandeDecision[] = [];
  demandesAffichees: DemandeDecision[] = [];
  loading = true;
  error: string | null = null;
  filtreStatut: string | null = null;
  compteurs: Record<string, number> = {};
  readonly labelsStatut = LABELS_STATUT;
  readonly statuts = Object.keys(LABELS_STATUT);

  readonly columns: DataTableColumn<DemandeDecision>[] = [
    { key: 'agent', label: 'Agent', sortable: true, value: (d) => this.agentLabel(d) },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => this.labelsStatut[d.statut ?? ''] ?? '' },
    { key: 'creeeLe', label: 'Créée le', sortable: true, value: (d) => d.createdAt },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: DemandeDecisionApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de décision.';
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

  agentLabel(demande: DemandeDecision): string {
    if (typeof demande.personnel === 'string') {
      return demande.personnel;
    }
    const personnel: Personnel = demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  badgeClass(statut: string | undefined): string {
    switch (statut) {
      case 'transmise':
        return 'info';
      case 'approuvee':
        return 'primary';
      case 'transmise_agent':
        return 'success';
      case 'refusee':
        return 'danger';
      default:
        return 'secondary';
    }
  }
}
