import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { homeGuard } from './core/guards/home.guard';
import { roleGuard } from './core/guards/role.guard';
import { ShellComponent } from './layout/shell/shell.component';
import { LoginComponent } from './pages/login/login.component';
import { NoAccessComponent } from './pages/no-access/no-access.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  {
    path: '',
    component: ShellComponent,
    canActivate: [authGuard],
    children: [
      { path: 'acces-refuse', component: NoAccessComponent },
      {
        path: 'profil',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () => import('./pages/profil/profil.component').then((m) => m.ProfilComponent),
      },
      {
        path: 'mon-espace/carriere',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/carriere/ma-carriere.component').then((m) => m.MaCarriereComponent),
      },
      {
        path: 'mon-espace/parc-informatique',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/parc-informatique/mon-parc-informatique.component').then(
            (m) => m.MonParcInformatiqueComponent,
          ),
      },
      {
        path: 'mon-espace/parc-automobile',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/parc-automobile/mon-parc-automobile.component').then(
            (m) => m.MonParcAutomobileComponent,
          ),
      },
      {
        path: 'mon-espace/conges',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () => import('./pages/profil/conges/mes-conges.component').then((m) => m.MesCongesComponent),
      },
      {
        path: 'mon-espace/carte-professionnelle',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/carte-professionnelle/ma-carte-professionnelle.component').then(
            (m) => m.MaCarteProfessionnelleComponent,
          ),
      },
      {
        path: 'dashboard',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_PERSONNEL'] },
        component: DashboardComponent,
      },
      {
        path: 'personnel',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_PERSONNEL'] },
        loadChildren: () =>
          import('./pages/personnel/personnel.routes').then((m) => m.PERSONNEL_ROUTES),
      },
      {
        path: 'carrieres',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_PERSONNEL'] },
        loadChildren: () =>
          import('./pages/carriere/carriere.routes').then((m) => m.CARRIERE_ROUTES),
      },
      {
        path: 'conges',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_CONGE'] },
        loadChildren: () =>
          import('./pages/conge/conge.routes').then((m) => m.CONGE_ROUTES),
      },
      {
        path: 'cartes-professionnelles',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_CARTE_PRO', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/carte-pro/carte-pro.routes').then((m) => m.CARTE_PRO_ROUTES),
      },
      {
        path: 'delegations',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/delegation/delegation.routes').then((m) => m.DELEGATION_ROUTES),
      },
      { path: '', pathMatch: 'full', canActivate: [homeGuard], children: [] },
    ],
  },
  { path: '**', redirectTo: '' },
];
