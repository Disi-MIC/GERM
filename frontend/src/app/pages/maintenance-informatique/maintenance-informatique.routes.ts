import { Routes } from '@angular/router';

export const MAINTENANCE_INFORMATIQUE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/maintenance-informatique-list.component').then((m) => m.MaintenanceInformatiqueListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./form/maintenance-informatique-form.component').then((m) => m.MaintenanceInformatiqueFormComponent),
  },
];
