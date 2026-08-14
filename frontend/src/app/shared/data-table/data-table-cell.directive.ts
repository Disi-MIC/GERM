import { Directive, Input, TemplateRef } from '@angular/core';

/**
 * Marque un <ng-template> comme rendu de cellule pour la colonne `key` d'un
 * <app-data-table> englobant. Permet à la page consommatrice de garder un
 * contrôle total sur le contenu (badges, liens, boutons d'action...) alors
 * que le tableau générique ne gère que tri/recherche/pagination/colonnes.
 *
 * Usage : <ng-template appDataTableCell="matricule" let-row>{{ row.matricule }}</ng-template>
 */
@Directive({
  selector: 'ng-template[appDataTableCell]',
  standalone: true,
})
export class DataTableCellDirective {
  @Input('appDataTableCell') columnKey = '';

  constructor(public readonly templateRef: TemplateRef<{ $implicit: unknown }>) {}
}
