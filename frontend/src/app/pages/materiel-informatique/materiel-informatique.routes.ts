import { Routes } from '@angular/router';

export const MATERIEL_INFORMATIQUE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/materiel-informatique-list.component').then((m) => m.MaterielInformatiqueListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./detail/materiel-informatique-detail.component').then((m) => m.MaterielInformatiqueDetailComponent),
  },
  {
    path: ':id',
    loadComponent: () =>
      import('./detail/materiel-informatique-detail.component').then((m) => m.MaterielInformatiqueDetailComponent),
  },
];
