import { Routes } from '@angular/router';

export const CARTOUCHES_INFORMATIQUE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/cartouches-informatique-list.component').then((m) => m.CartouchesInformatiqueListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./form/cartouches-informatique-form.component').then((m) => m.CartouchesInformatiqueFormComponent),
  },
];
