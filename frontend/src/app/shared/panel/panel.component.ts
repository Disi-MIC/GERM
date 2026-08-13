import { Component, Input } from '@angular/core';

/**
 * Carte "x_panel" façon Gentelella : bandeau d'en-tête (icône + titre, zone
 * d'actions projetée à droite) séparé par une bordure, contenu projeté
 * en-dessous. Remplace les `<div class="card p-3"><h6>Titre</h6>...</div>`
 * dupliqués dans chaque tableau de bord — un seul gabarit d'en-tête partagé.
 */
@Component({
  selector: 'app-panel',
  standalone: true,
  templateUrl: './panel.component.html',
  styleUrl: './panel.component.scss',
})
export class PanelComponent {
  @Input() title = '';
  @Input() icon?: string;
  /** Couleur du titre — utilisée pour les panneaux d'alerte (ex: "en retard" en rouge). */
  @Input() titleColor?: 'danger' | 'warning' | 'success';
  /** Retire le padding du corps — pour un tableau qui doit toucher les bords. */
  @Input() noPadding = false;
}
