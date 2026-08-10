import { Routes } from '@angular/router';

export const DOCUMENTS_ADMINISTRATIFS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./list/documents-administratifs-list.component').then((m) => m.DocumentsAdministratifsListComponent),
  },
  {
    path: 'new',
    loadComponent: () =>
      import('./form/document-administratif-form.component').then((m) => m.DocumentAdministratifFormComponent),
  },
];
