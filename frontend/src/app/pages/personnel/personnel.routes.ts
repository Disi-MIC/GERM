import { Routes } from '@angular/router';

export const PERSONNEL_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/personnel-list.component').then((m) => m.PersonnelListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./detail/personnel-detail.component').then((m) => m.PersonnelDetailComponent),
  },
  {
    path: ':id',
    loadComponent: () =>
      import('./detail/personnel-detail.component').then((m) => m.PersonnelDetailComponent),
  },
  {
    path: ':id/dossier',
    loadComponent: () =>
      import('./dossier/personnel-dossier.component').then((m) => m.PersonnelDossierComponent),
  },
];
