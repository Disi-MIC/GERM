import { Routes } from '@angular/router';

export const LICENCES_LOGICIELLES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/licences-logicielles-list.component').then((m) => m.LicencesLogiciellesListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./form/licence-logicielle-form.component').then((m) => m.LicenceLogicielleFormComponent),
  },
];
