import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DecisionConge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../../shared/data-table/data-table.component';
import { DecisionCongeApiService } from '../../decision-conge-api.service';

@Component({
  selector: 'app-decision-conge-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './decision-conge-list.component.html',
})
export class DecisionCongeListComponent implements OnInit {
  decisions: DecisionConge[] = [];
  loading = true;
  error: string | null = null;

  readonly columns: DataTableColumn<DecisionConge>[] = [
    { key: 'numero', label: 'Numéro', sortable: true, value: (d) => d.numeroDecision },
    { key: 'agent', label: 'Agent', sortable: true, value: (d) => this.agentLabel(d) },
    { key: 'octroi', label: 'Octroi', sortable: true, value: (d) => d.dateDecision },
    { key: 'expiration', label: 'Expiration', sortable: true, value: (d) => d.dateExpiration },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => (d.isValide ? 'Valide' : 'Expirée') },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: DecisionCongeApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (decisions) => {
        this.decisions = decisions;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les décisions de congé.';
        this.loading = false;
      },
    });
  }

  agentLabel(decision: DecisionConge): string {
    if (typeof decision.personnel === 'string') {
      return decision.personnel;
    }
    const personnel: Personnel = decision.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  supprimer(decision: DecisionConge): void {
    if (!decision.id) {
      return;
    }
    if (!confirm(`Supprimer la décision ${decision.numeroDecision} ?`)) {
      return;
    }
    this.api.delete(decision.id).subscribe({
      next: () => {
        this.decisions = this.decisions.filter((d) => d.id !== decision.id);
      },
      error: (err) => {
        this.error =
          err?.error?.errors?.demandesJouissance ?? 'Erreur lors de la suppression.';
      },
    });
  }
}
