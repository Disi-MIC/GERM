import { Directive, TemplateRef } from '@angular/core';

/**
 * Rendu de ligne alternatif pour petit écran, projeté dans un
 * <app-data-table> englobant. Quand fourni, remplace en dessous de 640px le
 * repli générique "intitulé au-dessus de la valeur" (voir
 * data-table.component.scss) par une carte entièrement custom à la page
 * consommatrice — au-dessus de 640px le tableau classique reste affiché tel
 * quel, ce template n'est alors pas utilisé.
 *
 * Usage : <ng-template appDataTableMobileItem let-row>...carte...</ng-template>
 */
@Directive({
  selector: 'ng-template[appDataTableMobileItem]',
  standalone: true,
})
export class DataTableMobileItemDirective {
  constructor(public readonly templateRef: TemplateRef<{ $implicit: unknown }>) {}
}
