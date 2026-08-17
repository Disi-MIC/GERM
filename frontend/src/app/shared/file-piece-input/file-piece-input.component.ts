import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DocumentScannerService } from '../../core/document-scanner.service';

/**
 * Dépôt d'une pièce justificative : bouton "Scanner" (numérisation native,
 * voir DocumentScannerService) à côté du sélecteur de fichier classique sur
 * mobile — les deux mènent au même `fichierChange`, l'agent choisit celle
 * qui lui convient. Sur web, seul le sélecteur de fichier s'affiche
 * (DocumentScannerService.disponible() renvoie false hors app native).
 * Facteur commun aux formulaires "Mes documents", carte professionnelle et
 * congés (décision/jouissance) — mêmes boutons partout plutôt que dupliqués
 * dans chaque composant.
 */
@Component({
  selector: 'app-file-piece-input',
  standalone: true,
  templateUrl: './file-piece-input.component.html',
})
export class FilePieceInputComponent {
  @Input() label = '';
  @Input() requis = false;
  @Input() accept = '.pdf,.jpg,.jpeg,.png';
  @Input() fichier: File | null = null;
  @Output() fichierChange = new EventEmitter<File | null>();

  scanEnCours = false;
  erreurScan: string | null = null;

  constructor(private readonly scanner: DocumentScannerService) {}

  get scanDisponible(): boolean {
    return this.scanner.disponible();
  }

  onFichierChange(input: HTMLInputElement): void {
    this.erreurScan = null;
    this.fichierChange.emit(input.files?.[0] ?? null);
    input.value = '';
  }

  async scannerDocument(): Promise<void> {
    this.erreurScan = null;
    this.scanEnCours = true;
    try {
      const fichier = await this.scanner.scanner();
      if (fichier) {
        this.fichierChange.emit(fichier);
      }
    } catch {
      this.erreurScan = 'La numérisation a échoué. Réessayez ou choisissez un fichier existant.';
    } finally {
      this.scanEnCours = false;
    }
  }

  retirer(): void {
    this.fichierChange.emit(null);
  }
}
