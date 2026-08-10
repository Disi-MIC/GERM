import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { ProfilApiService } from '../../profil-api.service';

/**
 * Aperçu en ligne de sa propre carte professionnelle (PDF intégré), sur le
 * modèle de CarteProPreviewComponent côté RH, mais en lecture seule (pas de
 * valider/régénérer, réservés au RH Admin) et sans endpoint dédié : la carte
 * est retrouvée dans la liste déjà exposée par /api/me/cartes-professionnelles.
 */
@Component({
  selector: 'app-ma-carte-preview',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './ma-carte-preview.component.html',
})
export class MaCartePreviewComponent implements OnInit {
  carte: CarteProfessionnelle | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;
  telechargerUrl = '';

  constructor(
    private readonly api: ProfilApiService,
    private readonly route: ActivatedRoute,
    private readonly sanitizer: DomSanitizer,
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
}
