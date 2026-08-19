import { Routes } from '@angular/router';
import { roleGuard } from '../../core/guards/role.guard';

export const CARTE_PRO_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/carte-pro-list.component').then((m) => m.CarteProListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./form/carte-pro-form.component').then((m) => m.CarteProFormComponent),
  },
  {
    path: 'validation',
    canActivate: [roleGuard],
    data: { roles: ['ROLE_RH_RESPONSABLE'] },
    loadComponent: () =>
      import('./validation/carte-pro-validation.component').then(
        (m) => m.CarteProValidationComponent,
      ),
  },
  {
    path: 'demandes',
    loadComponent: () =>
      import('./demandes/list/demande-carte-pro-list.component').then(
        (m) => m.DemandeCarteProListComponent,
      ),
  },
  {
    path: 'demandes/new',
    loadComponent: () =>
      import('./demandes/form/demande-carte-pro-form.component').then(
        (m) => m.DemandeCarteProFormComponent,
      ),
  },
  {
    path: 'demandes/:id/traiter',
    loadComponent: () =>
      import('./demandes/traiter/demande-carte-pro-traiter.component').then(
        (m) => m.DemandeCarteProTraiterComponent,
      ),
  },
  {
    path: ':id',
    loadComponent: () =>
      import('./preview/carte-pro-preview.component').then((m) => m.CarteProPreviewComponent),
  },
  {
    path: ':id/edit',
    loadComponent: () =>
      import('./form/carte-pro-form.component').then((m) => m.CarteProFormComponent),
  },
];
