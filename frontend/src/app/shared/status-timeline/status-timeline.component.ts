import { Component, Input } from '@angular/core';

export type EtatEtapeTimeline = 'termine' | 'actuel' | 'a-venir' | 'rejete';

export interface EtapeTimeline {
  label: string;
  /** Déjà formaté par l'appelant (ex. "16/08/2026 · 14:32") — null/undefined affiche "—", comme une étape pas encore atteinte. */
  sousTitre?: string | null;
  etat: EtatEtapeTimeline;
}

/**
 * Frise horizontale de statut (créée → étapes intermédiaires → décision
 * finale), utilisée sur les pages de traitement des demandes/tickets — même
 * composant en web et en mobile, ces pages étant partagées entre les deux
 * (voir app.routes.ts). Chaque domaine (carte pro, congés, tickets) calcule
 * sa propre liste d'étapes à partir de son statut ; ce composant se contente
 * de l'afficher. Défilement horizontal sur petit écran plutôt qu'un
 * réagencement vertical, pour rester fidèle au même visuel partout.
 */
@Component({
  selector: 'app-status-timeline',
  standalone: true,
  templateUrl: './status-timeline.component.html',
  styleUrl: './status-timeline.component.scss',
})
export class StatusTimelineComponent {
  @Input({ required: true }) etapes: EtapeTimeline[] = [];
}
