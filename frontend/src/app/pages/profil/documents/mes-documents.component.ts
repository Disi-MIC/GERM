import { Component, OnInit } from '@angular/core';
import { DocumentAdministratif } from '../../../core/models/document-administratif.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { FileGridComponent } from '../../../shared/file-grid/file-grid.component';
import { FileGridColor, FileGridItem } from '../../../shared/file-grid/file-grid-item.model';
import { ProfilApiService } from '../profil-api.service';

const COULEURS_EXTENSION: Record<string, FileGridColor> = {
  pdf: 'red',
  doc: 'blue',
  docx: 'blue',
  xls: 'green',
  xlsx: 'green',
  ppt: 'orange',
  pptx: 'orange',
  jpg: 'yellow',
  jpeg: 'yellow',
  png: 'yellow',
  gif: 'yellow',
  zip: 'purple',
  rar: 'purple',
};
const COULEUR_DEFAUT: FileGridColor = 'secondary';

function extension(nomOriginal: string | null | undefined): string {
  const match = nomOriginal ? /\.([a-zA-Z0-9]+)$/.exec(nomOriginal) : null;
  return match ? match[1].toLowerCase() : '';
}

/**
 * Lecture seule : les documents administratifs sont archivés par le RH
 * Personnel (voir pages/documents-administratifs), l'agent ne fait que
 * consulter/télécharger les siens — même logique que "Ma carte
 * professionnelle" (géré par le RH, l'agent visualise/télécharge).
 */
@Component({
  selector: 'app-mes-documents',
  standalone: true,
  imports: [PageHeaderComponent, FileGridComponent],
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

  get items(): FileGridItem<DocumentAdministratif>[] {
    return this.documents.map((document) => {
      const ext = extension(document.nomOriginal);
      return {
        row: document,
        name: document.libelle,
        meta: this.typeLabel(document),
        icon: ext ? ext.toUpperCase() : 'FICHIER',
        iconIsText: true,
        color: COULEURS_EXTENSION[ext] ?? COULEUR_DEFAUT,
      };
    });
  }

  typeLabel(document: DocumentAdministratif): string {
    if (typeof document.type === 'string') {
      return document.type;
    }
    const type: ListeValeurRef = document.type;
    return type.libelle;
  }

  ouvrir(document: DocumentAdministratif): void {
    if (document.id) {
      window.open(this.api.documentFichierUrl(document.id), '_blank');
    }
  }
}
