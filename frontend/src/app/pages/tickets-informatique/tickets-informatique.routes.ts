import { Routes } from '@angular/router';

export const TICKETS_INFORMATIQUE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/tickets-informatique-list.component').then((m) => m.TicketsInformatiqueListComponent),
  },
  {
    path: ':id/traiter',
    loadComponent: () =>
      import('./traiter/ticket-informatique-traiter.component').then((m) => m.TicketInformatiqueTraiterComponent),
  },
];
