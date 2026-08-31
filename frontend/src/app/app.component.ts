import { Component, OnInit } from '@angular/core';
import { Router, RouterOutlet } from '@angular/router';
import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';
import { AdminAccessModalComponent } from './shared/admin-access-modal/admin-access-modal.component';
import { extraireTokenMateriel } from './shared/materiel/qr-token.util';
import { MaterielInformatiqueApiService } from './pages/materiel-informatique/materiel-informatique-api.service';
import { SplashComponent } from './shared/splash/splash.component';
import { OnboardingComponent, ONBOARDING_VU_KEY } from './shared/onboarding/onboarding.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, AdminAccessModalComponent, SplashComponent, OnboardingComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
  title = 'frontend';

  // Splash puis, au premier lancement seulement, onboarding — voir
  // SplashComponent/OnboardingComponent. Affichés en superposition
  // au-dessus du router-outlet plutôt qu'en routes dédiées : la navigation
  // (authGuard, deep link germ://...) se déroule normalement dessous
  // pendant ce temps, sans logique de redirection à dupliquer ici.
  showSplash = Capacitor.isNativePlatform();
  showOnboarding = false;

  constructor(
    private readonly router: Router,
    private readonly materielApi: MaterielInformatiqueApiService,
  ) {}

  ngOnInit(): void {
    // Le viewport partagé avec le web (index.html) laisse le pinch-zoom actif
    // pour l'accessibilité — mais dans la WebView native, un pincement ou un
    // double-tap accidentel zoome la page et y reste bloqué (pas de geste
    // pour dézoomer sur une UI déjà pensée pour l'écran du téléphone). Sans
    // impact sur le web : la fonctionnalité Zoom d'iOS (Réglages >
    // Accessibilité) reste disponible indépendamment de ce meta.
    if (Capacitor.isNativePlatform()) {
      document.querySelector('meta[name="viewport"]')
        ?.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover');

      this.ecouterLiensGerm();
    }
  }

  onSplashTermine(): void {
    this.showSplash = false;
    this.showOnboarding = !localStorage.getItem(ONBOARDING_VU_KEY);
  }

  onOnboardingTermine(): void {
    this.showOnboarding = false;
  }

  /**
   * Un lien `germ://materiel/{token}` peut être ouvert autrement qu'en
   * scannant depuis MaterielInformatiqueScannerComponent (ex. l'app Appareil
   * photo propose "Ouvrir dans GERM" pour un schéma personnalisé qu'elle
   * reconnaît) — écouté ici pour un comportement cohérent quelle que soit
   * l'origine de l'ouverture, sans dupliquer la résolution du jeton.
   */
  private ecouterLiensGerm(): void {
    App.addListener('appUrlOpen', (event) => {
      const token = extraireTokenMateriel(event.url);
      if (!token) {
        return;
      }
      this.materielApi.resoudreQrcode(token).subscribe({
        next: ({ materielId }) => this.router.navigate(['/materiel-informatique', materielId]),
      });
    });
  }
}
