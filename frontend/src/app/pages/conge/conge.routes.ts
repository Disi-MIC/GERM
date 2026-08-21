import { Routes } from '@angular/router';
import { roleGuard } from '../../core/guards/role.guard';

export const CONGE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./conges/list/conge-list.component').then((m) => m.CongeListComponent),
  },
  {
    path: 'parametres-decision',
    canActivate: [roleGuard],
    data: { roles: ['ROLE_RH_RESPONSABLE'] },
    loadComponent: () =>
      import('./parametres-decision/parametres-decision.component').then((m) => m.ParametresDecisionComponent),
  },
  {
    path: 'parametres-eligibilite',
    canActivate: [roleGuard],
    data: { roles: ['ROLE_RH_RESPONSABLE'] },
    loadComponent: () =>
      import('./parametres-eligibilite/parametres-eligibilite.component').then((m) => m.ParametresEligibiliteComponent),
  },
  {
    path: 'new',
    loadComponent: () => import('./conges/form/conge-form.component').then((m) => m.CongeFormComponent),
  },
  {
    path: 'decisions',
    loadComponent: () =>
      import('./decisions/list/decision-conge-list.component').then((m) => m.DecisionCongeListComponent),
  },
  {
    path: 'decisions/new',
    loadComponent: () =>
      import('./decisions/form/decision-conge-form.component').then((m) => m.DecisionCongeFormComponent),
  },
  {
    path: 'decisions/:id/edit',
    loadComponent: () =>
      import('./decisions/form/decision-conge-form.component').then((m) => m.DecisionCongeFormComponent),
  },
  {
    path: 'demandes-decision',
    loadComponent: () =>
      import('./demandes-decision/list/demande-decision-list.component').then(
        (m) => m.DemandeDecisionListComponent,
      ),
  },
  {
    path: 'demandes-decision/new',
    loadComponent: () =>
      import('./demandes-decision/form/demande-decision-form.component').then(
        (m) => m.DemandeDecisionFormComponent,
      ),
  },
  {
    path: 'demandes-decision/:id/traiter',
    loadComponent: () =>
      import('./demandes-decision/traiter/demande-decision-traiter.component').then(
        (m) => m.DemandeDecisionTraiterComponent,
      ),
  },
  {
    path: 'demandes-jouissance',
    loadComponent: () =>
      import('./demandes-jouissance/list/demande-jouissance-list.component').then(
        (m) => m.DemandeJouissanceListComponent,
      ),
  },
  {
    path: 'demandes-jouissance/new',
    loadComponent: () =>
      import('./demandes-jouissance/form/demande-jouissance-form.component').then(
        (m) => m.DemandeJouissanceFormComponent,
      ),
  },
  {
    path: 'demandes-jouissance/:id/traiter',
    loadComponent: () =>
      import('./demandes-jouissance/traiter/demande-jouissance-traiter.component').then(
        (m) => m.DemandeJouissanceTraiterComponent,
      ),
  },
  {
    path: ':id/edit',
    loadComponent: () => import('./conges/form/conge-form.component').then((m) => m.CongeFormComponent),
  },
];
