import { Component, Input } from '@angular/core';

/**
 * En-tête de page standard (titre + sous-titre + actions projetées à
 * droite, ex: bouton "Nouveau") — même gabarit que les tableaux de bord
 * (dashboard-informatique, mon-tableau-de-bord...). Base commune pour
 * toutes les pages de l'application : toute nouvelle page doit démarrer
 * par <app-page-header> plutôt qu'un <h5> brut.
 */
@Component({
  selector: 'app-page-header',
  standalone: true,
  templateUrl: './page-header.component.html',
})
export class PageHeaderComponent {
  @Input() title = '';
  @Input() subtitle?: string;
}
