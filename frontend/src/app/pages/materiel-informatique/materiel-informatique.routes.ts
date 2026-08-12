import { Routes } from '@angular/router';

export const MATERIEL_INFORMATIQUE_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/materiel-informatique-list.component').then((m) => m.MaterielInformatiqueListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./detail/materiel-informatique-detail.component').then((m) => m.MaterielInformatiqueDetailComponent),
  },
  {
    // Doit rester avant ':id' ci-dessous : Angular route sur le premier
    // segment qui matche, et ':id' matcherait sinon ce chemin statique.
    path: 'affectation',
    loadComponent: () =>
      import('./affectation/affectation-materiel.component').then((m) => m.AffectationMaterielComponent),
  },
  {
    path: ':id',
    loadComponent: () =>
      import('./detail/materiel-informatique-detail.component').then((m) => m.MaterielInformatiqueDetailComponent),
  },
];
