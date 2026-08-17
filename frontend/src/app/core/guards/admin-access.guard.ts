import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AdminAccessService } from '../admin-access.service';

/**
 * Posé sur chaque route d'administration (en plus de roleGuard, qui vérifie
 * le rôle) : bloque la navigation tant que le mot de passe n'a pas été
 * reconfirmé pour cette session (voir AdminAccessService), quelle que soit
 * la façon d'y arriver — bouton "Administration", lien direct, retour
 * navigateur, favori. La modale de confirmation (affichée globalement, voir
 * AppComponent) résout la promesse retournée par demanderAcces().
 */
export const adminAccessGuard: CanActivateFn = async () => {
  const adminAccess = inject(AdminAccessService);
  const router = inject(Router);

  const autorise = await adminAccess.demanderAcces();

  return autorise ? true : router.parseUrl('/mon-espace/tableau-de-bord');
};
