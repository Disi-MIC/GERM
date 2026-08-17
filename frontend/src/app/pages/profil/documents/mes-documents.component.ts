import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DocumentAdministratif } from '../../../core/models/document-administratif.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { FileGridComponent } from '../../../shared/file-grid/file-grid.component';
import { FileGridColor, FileGridItem } from '../../../shared/file-grid/file-grid-item.model';
import { FilePieceInputComponent } from '../../../shared/file-piece-input/file-piece-input.component';
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
 * Les documents à valeur d'acte RH (décision de nomination, attestation,
 * contrat...) restent archivés exclusivement par le RH Personnel (voir
 * pages/documents-administratifs) — même logique que "Ma carte
 * professionnelle". L'agent peut néanmoins déposer lui-même certaines pièces
 * justificatives qu'il détient déjà (CNI, diplôme, CV...) : la liste des
 * types autorisés vient du serveur (Api/MeDemandesController::TYPES_DOCUMENT_AGENT),
 * jamais codée en dur ici, pour ne jamais désynchroniser les deux côtés.
 */
@Component({
  selector: 'app-mes-documents',
  standalone: true,
  imports: [FormsModule, PageHeaderComponent, PanelComponent, FileGridComponent, FilePieceInputComponent],
  templateUrl: './mes-documents.component.html',
})
export class MesDocumentsComponent implements OnInit {
  documents: DocumentAdministratif[] = [];
  types: ListeValeurRef[] = [];
  loading = true;
  error: string | null = null;

  afficherFormulaire = false;
  typeChoisi = '';
  libelleChoisi = '';
  fichierChoisi: File | null = null;
  depotEnCours = false;
  erreurDepot: string | null = null;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.charger();
    this.api.getTypesDocumentsSoumissibles().subscribe({
      next: (types) => (this.types = types),
    });
  }

  private charger(): void {
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

  basculerFormulaire(): void {
    this.afficherFormulaire = !this.afficherFormulaire;
    this.erreurDepot = null;
    if (!this.afficherFormulaire) {
      this.typeChoisi = '';
      this.libelleChoisi = '';
      this.fichierChoisi = null;
    }
  }

  deposer(): void {
    if (!this.typeChoisi || !this.fichierChoisi) {
      this.erreurDepot = 'Choisissez un type de document et un fichier.';
      return;
    }

    const libelle = this.libelleChoisi.trim() || (this.types.find((t) => t.code === this.typeChoisi)?.libelle ?? '');

    this.depotEnCours = true;
    this.erreurDepot = null;
    this.api.uploaderDocument(this.typeChoisi, libelle, this.fichierChoisi).subscribe({
      next: () => {
        this.depotEnCours = false;
        this.basculerFormulaire();
        this.charger();
      },
      error: (err) => {
        this.depotEnCours = false;
        this.erreurDepot = err?.error?.errors?.fichier ?? err?.error?.errors?.type ?? 'Le dépôt du document a échoué.';
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
