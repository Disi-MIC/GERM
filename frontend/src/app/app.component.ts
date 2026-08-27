import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { AdminAccessModalComponent } from './shared/admin-access-modal/admin-access-modal.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, AdminAccessModalComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
  title = 'frontend';

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
    }
  }
}
