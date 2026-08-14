import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Conge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../../shared/data-table/data-table.component';
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
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './conge-list.component.html',
})
export class CongeListComponent implements OnInit {
  conges: Conge[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  readonly columns: DataTableColumn<Conge>[] = [
    { key: 'agent', label: 'Agent', sortable: true, value: (c) => this.agentLabel(c) },
    { key: 'type', label: 'Type', sortable: true, value: (c) => this.labelsType[c.type] ?? c.type },
    { key: 'debut', label: 'Début', sortable: true, value: (c) => c.dateDebut },
    { key: 'fin', label: 'Fin', sortable: true, value: (c) => c.dateFin },
    { key: 'duree', label: 'Durée', sortable: true, value: (c) => c.duree },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

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
