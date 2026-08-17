import { Component, Input, OnChanges } from '@angular/core';
import { PageTitleService } from '../../core/page-title.service';

/**
 * En-tête de page standard (titre + sous-titre + actions projetées à
 * droite, ex: bouton "Nouveau") — même gabarit que les tableaux de bord
 * (dashboard-informatique, mon-tableau-de-bord...). Base commune pour
 * toutes les pages de l'application : toute nouvelle page doit démarrer
 * par <app-page-header> plutôt qu'un <h5> brut. Pousse aussi son titre vers
 * PageTitleService, lu par MobileShellComponent pour l'en-tête mobile —
 * gratuit pour chaque page tant qu'elle utilise déjà ce composant.
 */
@Component({
  selector: 'app-page-header',
  standalone: true,
  templateUrl: './page-header.component.html',
})
export class PageHeaderComponent implements OnChanges {
  @Input() title = '';
  @Input() subtitle?: string;

  constructor(private readonly pageTitle: PageTitleService) {}

  ngOnChanges(): void {
    this.pageTitle.definir(this.title);
  }
}
