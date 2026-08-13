import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Conge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { CongeApiService } from '../../conge-api.service';

const LABELS_TYPE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

@Component({
  selector: 'app-conge-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './conge-list.component.html',
})
export class CongeListComponent implements OnInit {
  conges: Conge[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: CongeApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (conges) => {
        this.conges = conges;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger la liste des congés.';
        this.loading = false;
      },
    });
  }

  agentLabel(conge: Conge): string {
    if (typeof conge.personnel === 'string') {
      return conge.personnel;
    }
    const personnel: Personnel = conge.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  supprimer(conge: Conge): void {
    if (!conge.id) {
      return;
    }
    if (!confirm('Supprimer ce congé ?')) {
      return;
    }
    this.api.delete(conge.id).subscribe({
      next: () => {
        this.conges = this.conges.filter((c) => c.id !== conge.id);
      },
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
