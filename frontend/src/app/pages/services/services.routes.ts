import { Routes } from '@angular/router';

export const SERVICES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./list/service-list.component').then((m) => m.ServiceListComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./detail/service-detail.component').then((m) => m.ServiceDetailComponent),
  },
  {
    path: ':id',
    loadComponent: () => import('./detail/service-detail.component').then((m) => m.ServiceDetailComponent),
  },
];
