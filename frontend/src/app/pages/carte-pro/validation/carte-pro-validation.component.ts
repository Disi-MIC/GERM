import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { CarteProApiService } from '../carte-pro-api.service';

@Component({
  selector: 'app-carte-pro-validation',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './carte-pro-validation.component.html',
})
export class CarteProValidationComponent implements OnInit {
  cartes: CarteProfessionnelle[] = [];
  loading = true;
  error: string | null = null;

  readonly columns: DataTableColumn<CarteProfessionnelle>[] = [
    { key: 'numero', label: 'Numéro', sortable: true, value: (c) => c.numero },
    { key: 'agent', label: 'Agent', sortable: true, value: (c) => this.agentLabel(c) },
    { key: 'creeeLe', label: 'Créée le', sortable: true, value: (c) => c.createdAt },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: CarteProApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.getEnAttenteValidation().subscribe({
      next: (cartes) => {
        this.cartes = cartes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les cartes en attente de validation.';
        this.loading = false;
      },
    });
  }

  agentLabel(carte: CarteProfessionnelle): string {
    if (typeof carte.personnel === 'string') {
      return carte.personnel;
    }
    const personnel: Personnel = carte.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  valider(carte: CarteProfessionnelle): void {
    if (!carte.id) {
      return;
    }
    this.api.valider(carte.id).subscribe({
      next: () => {
        this.cartes = this.cartes.filter((c) => c.id !== carte.id);
      },
      error: () => {
        this.error = 'Erreur lors de la validation.';
      },
    });
  }
}
