import { Component, EventEmitter, OnInit, Output } from '@angular/core';

/**
 * Écran de lancement animé de l'app native (Capacitor) — pas un remplacement
 * du launch screen iOS natif (LaunchScreen.storyboard, système, instantané)
 * mais un habillage affiché juste après par AppComponent, le temps que
 * l'app finisse de démarrer (vérification de session en cours dessous, voir
 * authGuard). Se ferme seul après un court délai fixe : pas de dépendance à
 * la fin réelle du chargement, pour rester simple et toujours prévisible.
 */
// Doit rester égale à la durée de l'animation .germ-splash-progress-bar
// (splash.component.scss) : la barre de progression sert de repère visuel
// pour ce délai, les deux doivent finir ensemble.
const DUREE_MS = 2200;

@Component({
  selector: 'app-splash',
  standalone: true,
  templateUrl: './splash.component.html',
  styleUrl: './splash.component.scss',
})
export class SplashComponent implements OnInit {
  @Output() readonly termine = new EventEmitter<void>();

  readonly lettres = 'GERM'.split('');

  ngOnInit(): void {
    setTimeout(() => this.termine.emit(), DUREE_MS);
  }
}
