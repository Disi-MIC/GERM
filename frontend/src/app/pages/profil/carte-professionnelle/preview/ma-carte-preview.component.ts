import { SlicePipe } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { NativePdfService } from '../../../../core/native-pdf.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { ProfilApiService } from '../../profil-api.service';

const LABELS_STATUT: Record<string, string> = {
  valide: 'Valide',
  perdue: 'Perdue',
  volee: 'Volée',
  annulee: 'Annulée',
};

/**
 * Aperçu en ligne de sa propre carte professionnelle, sur le modèle de
 * CarteProPreviewComponent côté RH, mais en lecture seule (pas de
 * valider/régénérer, réservés au RH Admin) et sans endpoint dédié : la carte
 * est retrouvée dans la liste déjà exposée par /api/me/cartes-professionnelles.
 *
 * Sur natif (app mobile), pas d'<iframe> : WKWebView n'a pas d'onglet où
 * ouvrir un <a target="_blank">, qui ne fait donc rien de perceptible, et un
 * PDF de carte (mise en page portrait/paysage fixe, souvent petite) rendu
 * dans un <iframe> compressé sur 375px de large est illisible. À la place,
 * une carte-résumé (même langage visuel que les listes "Mon espace") avec
 * deux actions qui récupèrent le PDF via NativePdfService (voir ce service
 * pour le pourquoi : Browser.open()/SFSafariViewController ne partage pas
 * le cookie de session de la WebView, donc échoue en "Full authentication
 * is required" malgré un login réussi).
 */
@Component({
  selector: 'app-ma-carte-preview',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent],
  templateUrl: './ma-carte-preview.component.html',
  styleUrl: './ma-carte-preview.component.scss',
})
export class MaCartePreviewComponent implements OnInit, OnDestroy {
  carte: CarteProfessionnelle | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;
  telechargerUrl = '';
  readonly estNatif = Capacitor.isNativePlatform();
  readonly labelsStatut = LABELS_STATUT;
  ouvertureEnCours = false;
  erreurOuverture: string | null = null;

  /** URL blob: courante (voir ngOnInit()) — à révoquer explicitement, sinon fuite mémoire tant que l'onglet reste ouvert. */
  private objectUrl: string | null = null;

  constructor(
    private readonly api: ProfilApiService,
    private readonly route: ActivatedRoute,
    private readonly sanitizer: DomSanitizer,
    private readonly nativePdf: NativePdfService,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getMesCartesProfessionnelles().subscribe({
      next: (cartes) => {
        const carte = cartes.find((c) => c.id === id) ?? null;
        if (!carte) {
          this.error = 'Carte introuvable.';
          this.loading = false;
          return;
        }
        if (!carte.valideeParAdminRh) {
          this.error = "Cette carte n'est pas encore validée par le RH Admin.";
          this.loading = false;
          return;
        }
        this.carte = carte;
        this.telechargerUrl = this.api.cartePdfTelechargerUrl(id);
        this.loading = false;
        if (!this.estNatif) {
          this.chargerPdf(id);
        }
      },
      error: () => {
        this.error = 'Impossible de charger cette carte.';
        this.loading = false;
      },
    });
  }

  ngOnDestroy(): void {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }
  }

  /**
   * Récupère le PDF en blob plutôt que de pointer l'iframe directement sur
   * l'URL de l'API (web uniquement) — voir imprimer() : ouvrir ce blob dans
   * son propre onglet est ce qui permet d'imprimer, pas l'inverse, mais le
   * charger une seule fois en amont évite de le retélécharger à chaque clic.
   */
  private chargerPdf(id: number): void {
    this.api.getCartePdfBlob(id).subscribe({
      next: (blob) => {
        this.objectUrl = URL.createObjectURL(blob);
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.objectUrl);
      },
      error: () => {
        this.error = "Impossible de charger l'aperçu PDF.";
      },
    });
  }

  /**
   * iframe.contentWindow.print() échoue systématiquement en SecurityError
   * dans Chromium sur un PDF (le lecteur PDF natif s'exécute dans un
   * contexte isolé, blob: ou pas) — même famille de problème que
   * decision-conge-apercu.imprimer(), où masquer/repositionner un sous-arbre
   * de la page via CSS s'est montré peu fiable. Ici plus simple : le blob
   * est déjà un PDF, l'ouvrir dans son propre onglet suffit à donner accès
   * au bouton d'impression natif du lecteur PDF de Chrome (web uniquement —
   * sur natif, voir voir()/telecharger() via NativePdfService).
   */
  imprimer(): void {
    if (this.objectUrl) {
      window.open(this.objectUrl, '_blank');
    }
  }

  async voir(): Promise<void> {
    await this.ouvrir(this.api.cartePdfUrl(this.carte!.id!));
  }

  async telecharger(): Promise<void> {
    await this.ouvrir(this.telechargerUrl);
  }

  private async ouvrir(url: string): Promise<void> {
    if (this.ouvertureEnCours) {
      return;
    }
    this.ouvertureEnCours = true;
    this.erreurOuverture = null;
    try {
      await this.nativePdf.ouvrir(url, `Carte professionnelle ${this.carte!.numero}.pdf`);
    } catch {
      this.erreurOuverture = "Impossible d'ouvrir la carte pour le moment.";
    } finally {
      this.ouvertureEnCours = false;
    }
  }
}
