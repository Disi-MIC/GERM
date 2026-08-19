import { Routes } from '@angular/router';

export const DIRECTIONS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./list/direction-list.component').then((m) => m.DirectionListComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./detail/direction-detail.component').then((m) => m.DirectionDetailComponent),
  },
  {
    path: ':id',
    loadComponent: () => import('./detail/direction-detail.component').then((m) => m.DirectionDetailComponent),
  },
];
