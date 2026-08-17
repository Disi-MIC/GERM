import { Injectable, signal } from '@angular/core';

/**
 * Titre de la page courante, alimenté par PageHeaderComponent (déjà présent
 * en tête de chaque page — voir son commentaire) et lu par MobileShellComponent
 * pour son en-tête : évite une table de correspondance route → titre séparée
 * à maintenir en double de ce que chaque page affiche déjà elle-même.
 */
@Injectable({ providedIn: 'root' })
export class PageTitleService {
  readonly titre = signal('GERM');

  definir(titre: string): void {
    this.titre.set(titre || 'GERM');
  }
}
