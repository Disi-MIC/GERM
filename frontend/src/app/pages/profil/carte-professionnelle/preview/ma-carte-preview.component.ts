import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { NativePdfService } from '../../../../core/native-pdf.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
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
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './ma-carte-preview.component.html',
})
export class MaCartePreviewComponent implements OnInit {
  carte: CarteProfessionnelle | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;
  telechargerUrl = '';
  readonly estNatif = Capacitor.isNativePlatform();
  readonly labelsStatut = LABELS_STATUT;
  ouvertureEnCours = false;
  erreurOuverture: string | null = null;

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
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.api.cartePdfUrl(id));
        this.telechargerUrl = this.api.cartePdfTelechargerUrl(id);
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger cette carte.';
        this.loading = false;
      },
    });
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
