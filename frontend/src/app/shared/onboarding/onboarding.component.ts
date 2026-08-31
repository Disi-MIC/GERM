import { Component, EventEmitter, Output } from '@angular/core';

interface Diapositive {
  icone: string;
  titre: string;
  texte: string;
}

/** Clé de présence dans localStorage — lue par AppComponent pour décider si l'onboarding doit s'afficher. */
export const ONBOARDING_VU_KEY = 'germ_onboarding_vu_v2';

const DIAPOSITIVES: Diapositive[] = [
  {
    icone: 'bi-phone',
    titre: 'Bienvenue sur GERM',
    texte: "Gérez vos ressources humaines et matérielles du Ministère, directement depuis votre téléphone.",
  },
  {
    icone: 'bi-person-badge',
    titre: 'Votre espace agent',
    texte: 'Suivez votre carrière, vos congés et votre carte professionnelle en un coup d\'œil.',
  },
  {
    icone: 'bi-bell',
    titre: 'Alertes en temps réel',
    texte: "Recevez une notification avant l'expiration d'un document, d'une carte ou d'une maintenance.",
  },
  {
    icone: 'bi-qr-code-scan',
    titre: 'Scanner le matériel',
    texte: 'Identifiez rapidement un poste ou un équipement du parc informatique en scannant son étiquette.',
  },
];

/**
 * Présentation guidée affichée une seule fois par installation, juste après
 * le splash screen (voir AppComponent) — persistée dans localStorage plutôt
 * que côté serveur : c'est une préférence d'affichage locale à l'appareil,
 * sans intérêt à synchroniser entre plusieurs appareils d'un même agent.
 */
@Component({
  selector: 'app-onboarding',
  standalone: true,
  templateUrl: './onboarding.component.html',
  styleUrl: './onboarding.component.scss',
})
export class OnboardingComponent {
  @Output() readonly termine = new EventEmitter<void>();

  readonly diapositives = DIAPOSITIVES;
  index = 0;

  private xDepart = 0;

  get derniere(): boolean {
    return this.index === this.diapositives.length - 1;
  }

  suivant(): void {
    if (this.derniere) {
      this.fermer();
      return;
    }
    this.index++;
  }

  precedent(): void {
    if (this.index > 0) {
      this.index--;
    }
  }

  aller(i: number): void {
    this.index = i;
  }

  fermer(): void {
    localStorage.setItem(ONBOARDING_VU_KEY, '1');
    this.termine.emit();
  }

  /** Balayage tactile gauche/droite entre les diapositives, en plus des boutons. */
  onTouchStart(event: TouchEvent): void {
    this.xDepart = event.touches[0].clientX;
  }

  onTouchEnd(event: TouchEvent): void {
    const delta = event.changedTouches[0].clientX - this.xDepart;
    if (Math.abs(delta) < 40) {
      return;
    }
    if (delta < 0) {
      this.suivant();
    } else {
      this.precedent();
    }
  }
}
