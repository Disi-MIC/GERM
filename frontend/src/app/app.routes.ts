import { Capacitor } from '@capacitor/core';
import { Routes } from '@angular/router';
import { adminAccessGuard } from './core/guards/admin-access.guard';
import { authGuard } from './core/guards/auth.guard';
import { homeGuard } from './core/guards/home.guard';
import { organisationScopeGuard } from './core/guards/organisation-scope.guard';
import { roleGuard } from './core/guards/role.guard';
import { ShellComponent } from './layout/shell/shell.component';
import { MobileShellComponent } from './layout/mobile-shell/mobile-shell.component';
import { LoginComponent } from './pages/login/login.component';
import { NoAccessComponent } from './pages/no-access/no-access.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';

/**
 * L'app native (Capacitor) n'expose que « Mon espace » : coquille dédiée
 * (barre d'onglets, pas de bascule Administration) plutôt que la sidebar
 * desktop — voir MobileShellComponent. Le reste de l'arbre de routes est
 * partagé tel quel : les rubriques admin restent chargées à la demande
 * (loadComponent/loadChildren), donc leur présence ici ne pèse pas sur le
 * bundle initial mobile, et homeGuard empêche de toute façon d'y atterrir.
 */
const shellComponent = Capacitor.isNativePlatform() ? MobileShellComponent : ShellComponent;

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  {
    path: '',
    component: shellComponent,
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
        path: 'mon-espace/tableau-de-bord',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/dashboard/mon-tableau-de-bord.component').then(
            (m) => m.MonTableauDeBordComponent,
          ),
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
        path: 'mon-espace/conges/nouvelle-demande-decision',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/conges/nouvelle-demande-decision/nouvelle-demande-decision.component').then(
            (m) => m.NouvelleDemandeDecisionComponent,
          ),
      },
      {
        path: 'mon-espace/conges/nouvelle-demande-jouissance',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/conges/nouvelle-demande-jouissance/nouvelle-demande-jouissance.component').then(
            (m) => m.NouvelleDemandeJouissanceComponent,
          ),
      },
      {
        path: 'mon-espace/documents',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/documents/mes-documents.component').then((m) => m.MesDocumentsComponent),
      },
      {
        path: 'mon-espace/tickets/nouveau',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/tickets/nouveau/nouveau-ticket.component').then((m) => m.NouveauTicketComponent),
      },
      {
        path: 'mon-espace/tickets',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () => import('./pages/profil/tickets/mes-tickets.component').then((m) => m.MesTicketsComponent),
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
        path: 'mon-espace/carte-professionnelle/nouvelle-demande',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/carte-professionnelle/nouvelle-demande/nouvelle-demande-carte-pro.component').then(
            (m) => m.NouvelleDemandeCarteProComponent,
          ),
      },
      {
        // Doit rester après 'nouvelle-demande' ci-dessus : Angular route sur le
        // premier segment qui matche, et ':id' matcherait sinon ce chemin statique.
        path: 'mon-espace/carte-professionnelle/:id',
        canActivate: [roleGuard],
        data: { roles: ['ROLE_AGENT'] },
        loadComponent: () =>
          import('./pages/profil/carte-professionnelle/preview/ma-carte-preview.component').then(
            (m) => m.MaCartePreviewComponent,
          ),
      },
      {
        // Accès dérivé de Service::$responsable (voir /api/me), pas d'un rôle.
        path: 'mon-espace/apercu-service',
        canActivate: [roleGuard, organisationScopeGuard],
        data: { roles: ['ROLE_AGENT'], champ: 'serviceResponsableId' },
        loadComponent: () =>
          import('./pages/apercu-organisation/mon-service/apercu-service.component').then(
            (m) => m.ApercuServiceComponent,
          ),
      },
      {
        // Accès dérivé de Direction::$directeur (voir /api/me), pas d'un rôle.
        path: 'mon-espace/apercu-direction',
        canActivate: [roleGuard, organisationScopeGuard],
        data: { roles: ['ROLE_AGENT'], champ: 'directionDirigeeId' },
        loadComponent: () =>
          import('./pages/apercu-organisation/ma-direction/apercu-direction.component').then(
            (m) => m.ApercuDirectionComponent,
          ),
      },
      {
        path: 'dashboard',
        canActivate: [roleGuard, adminAccessGuard],
        // ROLE_ADMIN_RH inclus explicitement : la hiérarchie de rôles Symfony
        // (ROLE_ADMIN_RH → RH_PERSONNEL/RH_CONGE/RH_CARTE_PRO) n'est appliquée
        // que côté serveur, jamais côté Angular (AuthService.hasRole() ne lit
        // que les rôles littéraux de /api/me) — le RH Admin doit donc être
        // transversal, listé explicitement à côté de chaque rôle RH métier.
        data: { roles: ['ROLE_RH_PERSONNEL', 'ROLE_ADMIN_RH'] },
        component: DashboardComponent,
      },
      {
        path: 'dashboard-conges',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_CONGE', 'ROLE_ADMIN_RH'] },
        loadComponent: () =>
          import('./pages/dashboard-conges/dashboard-conges.component').then((m) => m.DashboardCongesComponent),
      },
      {
        path: 'dashboard-cartes-professionnelles',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_CARTE_PRO', 'ROLE_ADMIN_RH'] },
        loadComponent: () =>
          import('./pages/dashboard-cartes-professionnelles/dashboard-cartes-professionnelles.component').then(
            (m) => m.DashboardCartesProfessionnellesComponent,
          ),
      },
      {
        path: 'dashboard-informatique',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_IT_STOCK', 'ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE'] },
        loadComponent: () =>
          import('./pages/dashboard-informatique/dashboard-informatique.component').then(
            (m) => m.DashboardInformatiqueComponent,
          ),
      },
      {
        path: 'apercu-ministere',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_AUTORITE'] },
        loadComponent: () =>
          import('./pages/apercu-organisation/ministere/apercu-ministere.component').then(
            (m) => m.ApercuMinistereComponent,
          ),
      },
      {
        path: 'personnel',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_PERSONNEL', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/personnel/personnel.routes').then((m) => m.PERSONNEL_ROUTES),
      },
      {
        path: 'carrieres',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_PERSONNEL', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/carriere/carriere.routes').then((m) => m.CARRIERE_ROUTES),
      },
      {
        path: 'documents-administratifs',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_PERSONNEL', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/documents-administratifs/documents-administratifs.routes').then(
            (m) => m.DOCUMENTS_ADMINISTRATIFS_ROUTES,
          ),
      },
      {
        path: 'materiel-informatique',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_IT_STOCK', 'ROLE_IT_RESPONSABLE'] },
        loadChildren: () =>
          import('./pages/materiel-informatique/materiel-informatique.routes').then(
            (m) => m.MATERIEL_INFORMATIQUE_ROUTES,
          ),
      },
      {
        path: 'tickets-informatique',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE'] },
        loadChildren: () =>
          import('./pages/tickets-informatique/tickets-informatique.routes').then((m) => m.TICKETS_INFORMATIQUE_ROUTES),
      },
      {
        path: 'maintenance-informatique',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_IT_STOCK', 'ROLE_IT_RESPONSABLE'] },
        loadChildren: () =>
          import('./pages/maintenance-informatique/maintenance-informatique.routes').then(
            (m) => m.MAINTENANCE_INFORMATIQUE_ROUTES,
          ),
      },
      {
        path: 'licences-logicielles',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_IT_STOCK', 'ROLE_IT_RESPONSABLE'] },
        loadChildren: () =>
          import('./pages/licences-logicielles/licences-logicielles.routes').then(
            (m) => m.LICENCES_LOGICIELLES_ROUTES,
          ),
      },
      {
        path: 'conges',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_CONGE', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/conge/conge.routes').then((m) => m.CONGE_ROUTES),
      },
      {
        path: 'cartes-professionnelles',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_RH_CARTE_PRO', 'ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/carte-pro/carte-pro.routes').then((m) => m.CARTE_PRO_ROUTES),
      },
      {
        path: 'delegations',
        canActivate: [roleGuard, adminAccessGuard],
        data: { roles: ['ROLE_ADMIN_RH'] },
        loadChildren: () =>
          import('./pages/delegation/delegation.routes').then((m) => m.DELEGATION_ROUTES),
      },
      { path: '', pathMatch: 'full', canActivate: [homeGuard], children: [] },
    ],
  },
  { path: '**', redirectTo: '' },
];
