import { Routes } from '@angular/router';

export const VEHICULES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./list/vehicule-list.component').then((m) => m.VehiculeListComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./detail/vehicule-detail.component').then((m) => m.VehiculeDetailComponent),
  },
  {
    path: ':id/carte',
    loadComponent: () => import('./carte/vehicule-carte.component').then((m) => m.VehiculeCarteComponent),
  },
  {
    path: ':id',
    loadComponent: () => import('./detail/vehicule-detail.component').then((m) => m.VehiculeDetailComponent),
  },
];
