import { Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';

export type StatTileColor = 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'secondary';

/**
 * Tuile statistique façon Gentelella ("tile_stats_count") : icône dans un
 * badge de couleur, grande valeur, libellé. Réutilisée par tous les
 * tableaux de bord (voir dashboard-informatique/dashboard/dashboard-conges/
 * dashboard-cartes-professionnelles/mon-tableau-de-bord) pour remplacer les
 * cartes Bootstrap génériques dupliquées dans chacun. `routerLink` optionnel
 * rend toute la tuile cliquable (mon-tableau-de-bord).
 */
@Component({
  selector: 'app-stat-tile',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './stat-tile.component.html',
  styleUrl: './stat-tile.component.scss',
})
export class StatTileComponent {
  @Input() icon = 'bi-bar-chart';
  @Input() value: string | number = 0;
  @Input() label = '';
  @Input() color: StatTileColor = 'primary';
  @Input() routerLink?: string | unknown[];
}
