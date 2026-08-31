import { Component, OnDestroy, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Vehicule } from '../../../core/models/vehicule.model';
import { VehiculeApiService } from '../vehicule-api.service';

/**
 * Aperçu/impression de la carte du véhicule — même mécanique que
 * CarteProPreviewComponent (PDF récupéré en blob, ouvert dans son propre
 * onglet pour imprimer), mais pas de stockage/validation côté serveur : le
 * PDF est régénéré à chaque ouverture (voir VehiculePdfGenerator), donc pas
 * de bouton "régénérer" ni de statut de validation à afficher ici.
 */
@Component({
  selector: 'app-vehicule-carte',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './vehicule-carte.component.html',
  styleUrl: './vehicule-carte.component.scss',
})
export class VehiculeCarteComponent implements OnInit, OnDestroy {
  vehicule: Vehicule | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;

  private objectUrl: string | null = null;

  constructor(
    private readonly api: VehiculeApiService,
    private readonly route: ActivatedRoute,
    private readonly sanitizer: DomSanitizer,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getOne(id).subscribe({
      next: (vehicule) => {
        this.vehicule = vehicule;
        this.loading = false;
        this.chargerPdf(id);
      },
      error: () => {
        this.error = 'Impossible de charger ce véhicule.';
        this.loading = false;
      },
    });
  }

  ngOnDestroy(): void {
    this.revoquerObjectUrl();
  }

  private chargerPdf(id: number): void {
    this.api.getCartePdfBlob(id).subscribe({
      next: (blob) => {
        this.revoquerObjectUrl();
        this.objectUrl = URL.createObjectURL(blob);
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.objectUrl);
      },
      error: () => {
        this.error = "Impossible de générer la carte du véhicule.";
      },
    });
  }

  private revoquerObjectUrl(): void {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }
  }

  /** iframe.contentWindow.print() échoue en SecurityError sur un PDF — même raison que CarteProPreviewComponent.imprimer(). */
  imprimer(): void {
    if (this.objectUrl) {
      window.open(this.objectUrl, '_blank');
    }
  }

  telecharger(): void {
    if (!this.objectUrl || !this.vehicule) {
      return;
    }
    const lien = document.createElement('a');
    lien.href = this.objectUrl;
    lien.download = `Carte vehicule ${this.vehicule.immatriculation}.pdf`;
    lien.click();
  }
}
