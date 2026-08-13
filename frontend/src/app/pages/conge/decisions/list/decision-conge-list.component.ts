import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DecisionConge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DecisionCongeApiService } from '../../decision-conge-api.service';

@Component({
  selector: 'app-decision-conge-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './decision-conge-list.component.html',
})
export class DecisionCongeListComponent implements OnInit {
  decisions: DecisionConge[] = [];
  loading = true;
  error: string | null = null;

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
