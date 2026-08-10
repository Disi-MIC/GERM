import { Routes } from '@angular/router';

export const DELEGATION_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./list/delegation-list.component').then((m) => m.DelegationListComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./form/delegation-form.component').then((m) => m.DelegationFormComponent),
  },
];
