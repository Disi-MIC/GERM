import { Routes } from '@angular/router';

export const CARRIERE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./list/carriere-list.component').then((m) => m.CarriereListComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./form/mouvement-form.component').then((m) => m.MouvementFormComponent),
  },
];
