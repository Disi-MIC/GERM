import { SlicePipe } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { CarteProApiService } from '../carte-pro-api.service';

@Component({
  selector: 'app-carte-pro-preview',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './carte-pro-preview.component.html',
  styleUrl: './carte-pro-preview.component.scss',
})
export class CarteProPreviewComponent implements OnInit, OnDestroy {
  carte: CarteProfessionnelle | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;
  telechargerUrl = '';

  /** URL blob: courante (voir chargerPdf()) — à révoquer explicitement, sinon fuite mémoire tant que l'onglet reste ouvert. */
  private objectUrl: string | null = null;

  constructor(
    private readonly api: CarteProApiService,
    private readonly route: ActivatedRoute,
    private readonly sanitizer: DomSanitizer,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getOne(id).subscribe({
      next: (carte) => {
        this.carte = carte;
        this.telechargerUrl = this.api.telechargerUrl(id);
        this.loading = false;
        this.chargerPdf(id);
      },
      error: () => {
        this.error = 'Impossible de charger cette carte.';
        this.loading = false;
      },
    });
  }

  ngOnDestroy(): void {
    this.revoquerObjectUrl();
  }

  /**
   * Récupère le PDF en blob plutôt que de pointer l'iframe directement sur
   * l'URL de l'API : un <iframe> cross-origin (l'API tourne sur une autre
   * origine que le frontend, surtout en dev) empêche contentWindow.print()
   * avec un SecurityError. Un blob: est toujours same-origin avec la page
   * qui l'a créé, quelle que soit l'origine d'où il vient — voir imprimer().
   */
  private chargerPdf(id: number): void {
    this.api.getPdfBlob(id).subscribe({
      next: (blob) => {
        this.revoquerObjectUrl();
        this.objectUrl = URL.createObjectURL(blob);
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.objectUrl);
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu PDF.";
      },
    });
  }

  private revoquerObjectUrl(): void {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }
  }

  agentLabel(): string {
    if (!this.carte) {
      return '';
    }
    if (typeof this.carte.personnel === 'string') {
      return this.carte.personnel;
    }
    const personnel: Personnel = this.carte.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  /**
   * iframe.contentWindow.print() échoue systématiquement en SecurityError
   * dans Chromium sur un PDF (le lecteur PDF natif s'exécute dans un
   * contexte isolé, blob: ou pas — pas un vrai cross-origin, mais traité
   * pareil) — même famille de problème que decision-conge-apercu.imprimer(),
   * où masquer/repositionner un sous-arbre de la page via CSS s'est montré
   * peu fiable. Ici plus simple : le blob est déjà un PDF, l'ouvrir dans son
   * propre onglet suffit à donner accès au bouton d'impression natif du
   * lecteur PDF de Chrome, hors de toute page hôte avec laquelle interférer.
   */
  imprimer(): void {
    if (this.objectUrl) {
      window.open(this.objectUrl, '_blank');
    }
  }

  valider(): void {
    if (!this.carte?.id) {
      return;
    }
    this.api.valider(this.carte.id).subscribe({
      next: (carte) => (this.carte = carte),
      error: () => {
        this.error = 'Erreur lors de la validation.';
      },
    });
  }

  regenerer(): void {
    if (!this.carte?.id) {
      return;
    }
    this.api.generer(this.carte.id).subscribe({
      next: (carte) => {
        this.carte = carte;
        this.chargerPdf(carte.id!);
      },
      error: () => {
        this.error = 'Erreur lors de la régénération du PDF.';
      },
    });
  }
}
