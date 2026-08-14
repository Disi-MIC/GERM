import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DocumentAdministratif } from '../../../core/models/document-administratif.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { DocumentsAdministratifsApiService } from '../documents-administratifs-api.service';

const JOURS_ALERTE_EXPIRATION = 30;

@Component({
  selector: 'app-documents-administratifs-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './documents-administratifs-list.component.html',
})
export class DocumentsAdministratifsListComponent implements OnInit {
  documents: DocumentAdministratif[] = [];
  loading = true;
  error: string | null = null;

  readonly columns: DataTableColumn<DocumentAdministratif>[] = [
    { key: 'agent', label: 'Agent', sortable: true, value: (d) => this.agentLabel(d) },
    { key: 'type', label: 'Type', sortable: true, value: (d) => this.typeLabel(d) },
    { key: 'libelle', label: 'Libellé', sortable: true, value: (d) => d.libelle },
    { key: 'dateDocument', label: 'Date document', sortable: true, value: (d) => d.dateDocument ?? '' },
    { key: 'expiration', label: 'Expiration', sortable: true, value: (d) => d.dateExpiration ?? '' },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: DocumentsAdministratifsApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.api.getAll().subscribe({
      next: (documents) => {
        this.documents = documents;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les documents administratifs.';
        this.loading = false;
      },
    });
  }

  agentLabel(document: DocumentAdministratif): string {
    if (typeof document.personnel === 'string') {
      return document.personnel;
    }
    const personnel: Personnel = document.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  typeLabel(document: DocumentAdministratif): string {
    if (typeof document.type === 'string') {
      return document.type;
    }
    const type: ListeValeurRef = document.type;
    return type.libelle;
  }

  fichierUrl(id: number): string {
    return this.api.fichierUrl(id);
  }

  /** Repère les documents à durée de validité (CNI, passeport...) proches de l'expiration ou déjà expirés. */
  badgeExpiration(document: DocumentAdministratif): { texte: string; classe: string } | null {
    if (!document.dateExpiration) {
      return null;
    }
    const expiration = new Date(document.dateExpiration);
    const aujourdhui = new Date();
    const dansJours = Math.floor((expiration.getTime() - aujourdhui.getTime()) / (1000 * 60 * 60 * 24));

    if (dansJours < 0) {
      return { texte: 'Expiré', classe: 'danger' };
    }
    if (dansJours <= JOURS_ALERTE_EXPIRATION) {
      return { texte: 'Expire bientôt', classe: 'warning' };
    }
    return null;
  }

  supprimer(document: DocumentAdministratif): void {
    if (!document.id) {
      return;
    }
    if (!confirm('Supprimer ce document administratif ? Cette action est irréversible.')) {
      return;
    }
    this.api.delete(document.id).subscribe({
      next: () => {
        this.documents = this.documents.filter((d) => d.id !== document.id);
      },
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
