import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DocumentAdministratif } from '../../../core/models/document-administratif.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ProfilApiService } from '../profil-api.service';

/**
 * Lecture seule : les documents administratifs sont archivés par le RH
 * Personnel (voir pages/documents-administratifs), l'agent ne fait que
 * consulter/télécharger les siens — même logique que "Ma carte
 * professionnelle" (géré par le RH, l'agent visualise/télécharge).
 */
@Component({
  selector: 'app-mes-documents',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './mes-documents.component.html',
})
export class MesDocumentsComponent implements OnInit {
  documents: DocumentAdministratif[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMesDocuments().subscribe({
      next: (documents) => {
        this.documents = documents;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos documents administratifs.';
        this.loading = false;
      },
    });
  }

  typeLabel(document: DocumentAdministratif): string {
    if (typeof document.type === 'string') {
      return document.type;
    }
    const type: ListeValeurRef = document.type;
    return type.libelle;
  }

  fichierUrl(id: number): string {
    return this.api.documentFichierUrl(id);
  }
}
