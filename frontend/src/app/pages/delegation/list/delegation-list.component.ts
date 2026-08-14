import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Delegation } from '../../../core/models/delegation.model';
import { UserRef } from '../../../core/models/user.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { DelegationApiService } from '../delegation-api.service';

const LABELS_ROLE: Record<string, string> = {
  ROLE_ADMIN_RH: 'Administrateur RH (accès global)',
  ROLE_RH_PERSONNEL: 'Gestion du personnel',
  ROLE_RH_CONGE: 'Gestion des congés',
  ROLE_RH_CARTE_PRO: 'Gestion des cartes professionnelles',
};

@Component({
  selector: 'app-delegation-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './delegation-list.component.html',
})
export class DelegationListComponent implements OnInit {
  delegations: Delegation[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsRole = LABELS_ROLE;

  readonly columns: DataTableColumn<Delegation>[] = [
    { key: 'delegant', label: 'Délégant', sortable: true, value: (d) => this.userLabel(d.delegant) },
    { key: 'delegataire', label: 'Délégataire', sortable: true, value: (d) => this.userLabel(d.delegataire) },
    { key: 'role', label: 'Rôle délégué', sortable: true, value: (d) => this.labelsRole[d.roleDelegue] ?? d.roleDelegue },
    { key: 'periode', label: 'Période', sortable: true, value: (d) => d.dateDebut },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => d.statutAffiche?.label ?? '' },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: DelegationApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (delegations) => {
        this.delegations = delegations;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les délégations.';
        this.loading = false;
      },
    });
  }

  userLabel(user: UserRef | string | undefined): string {
    if (!user) {
      return '';
    }
    return typeof user === 'string' ? user : user.nomComplet;
  }

  revoquer(delegation: Delegation): void {
    if (!delegation.id) {
      return;
    }
    if (!confirm('Révoquer cette délégation ?')) {
      return;
    }
    this.api.revoke(delegation.id).subscribe({
      next: (updated) => {
        this.delegations = this.delegations.map((d) => (d.id === updated.id ? updated : d));
      },
      error: () => {
        this.error = 'Erreur lors de la révocation.';
      },
    });
  }
}
