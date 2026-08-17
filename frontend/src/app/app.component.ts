import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
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
    // launchAutoHide est désactivé (capacitor.config.ts) pour éviter la coupure
    // brute par défaut : on masque nous-même une fois Angular monté, avec un
    // fondu et un léger délai pour que l'écran de lancement ne soit pas juste
    // un flash.
    if (Capacitor.isNativePlatform()) {
      setTimeout(() => {
        SplashScreen.hide({ fadeOutDuration: 400 });
      }, 400);
    }
  }
}
