import { Component, OnInit } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { BarcodeScanner } from 'capacitor-barcode-scanner';
import { extraireTokenMateriel } from '../../../shared/materiel/qr-token.util';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

/**
 * Scanner de caméra intégré à l'app (AVFoundation natif, voir
 * capacitor-barcode-scanner) — pendant du QR affiché sur l'étiquette
 * (MaterielInformatiqueDetailComponent) : le jeton qu'elle encode est
 * chiffré côté serveur (QrTokenService) et ne veut rien dire pour un
 * lecteur QR quelconque (appareil photo classique) ; seul ce scanner (et
 * le lien germ:// intercepté par AppComponent) sait le résoudre en fiche
 * matériel, via MaterielInformatiqueApiService.resoudreQrcode().
 *
 * Web/desktop : bibliothèque native absente, scan impossible — message
 * explicite plutôt qu'une tentative silencieuse.
 */
@Component({
  selector: 'app-materiel-informatique-scanner',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent],
  templateUrl: './materiel-informatique-scanner.component.html',
})
export class MaterielInformatiqueScannerComponent implements OnInit {
  readonly estNatif = Capacitor.isNativePlatform();
  scanning = false;
  error: string | null = null;

  constructor(
    private readonly api: MaterielInformatiqueApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    if (this.estNatif) {
      this.scanner();
    }
  }

  scanner(): void {
    this.error = null;
    this.scanning = true;

    BarcodeScanner.scan()
      .then((resultat) => {
        this.scanning = false;
        if (!resultat.result || !resultat.code) {
          // Scan annulé par l'utilisateur — retour à la liste plutôt qu'un message d'erreur.
          this.router.navigateByUrl('/materiel-informatique');
          return;
        }
        this.resoudre(resultat.code);
      })
      .catch(() => {
        this.scanning = false;
        this.error = "Impossible d'accéder à l'appareil photo. Vérifiez l'autorisation caméra dans Réglages > GERM.";
      });
  }

  private resoudre(code: string): void {
    const token = extraireTokenMateriel(code);
    if (!token) {
      this.error = "Ce code QR n'est pas une étiquette GERM valide.";
      return;
    }

    this.api.resoudreQrcode(token).subscribe({
      next: ({ materielId }) => this.router.navigate(['/materiel-informatique', materielId]),
      error: () => {
        this.error = 'Étiquette invalide ou matériel introuvable.';
      },
    });
  }
}
