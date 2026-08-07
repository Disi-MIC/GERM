import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { ShellComponent } from './layout/shell/shell.component';
import { LoginComponent } from './pages/login/login.component';
import { NoAccessComponent } from './pages/no-access/no-access.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  {
    path: '',
    component: ShellComponent,
    canActivate: [authGuard],
    children: [
      { path: 'acces-refuse', component: NoAccessComponent },
      {
        path: 'personnel',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_PERSONNEL'] },
        loadChildren: () =>
          import('./pages/personnel/personnel.routes').then((m) => m.PERSONNEL_ROUTES),
      },
      {
        path: 'cartes-professionnelles',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_RH_CARTE_PRO'] },
        loadChildren: () =>
          import('./pages/carte-pro/carte-pro.routes').then((m) => m.CARTE_PRO_ROUTES),
      },
      { path: '', pathMatch: 'full', redirectTo: 'personnel' },
    ],
  },
  { path: '**', redirectTo: '' },
];
