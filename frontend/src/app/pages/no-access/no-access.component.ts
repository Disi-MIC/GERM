import { Component } from '@angular/core';

@Component({
  selector: 'app-no-access',
  standalone: true,
  template: `
    <div class="card p-4">
      <p class="mb-0">
        Votre compte ne dispose d'aucun rôle configuré pour cette interface. Merci de
        contacter l'administrateur.
      </p>
    </div>
  `,
})
export class NoAccessComponent {}
