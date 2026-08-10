import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../auth.service';

/**
 * Route d'accueil ('/') : redirige vers le premier espace pertinent parmi
 * les rôles littéraux de l'utilisateur connecté (la hiérarchie de rôles
 * Symfony n'est pas répercutée côté frontend, voir AuthService.hasRole).
 * Un agent sans aucun rôle RH (seulement ROLE_AGENT, le rôle de base de
 * tout compte) atterrit sur son profil.
 */
export const homeGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.hasRole('ROLE_RH_PERSONNEL')) {
    return router.parseUrl('/personnel');
  }
  if (auth.hasRole('ROLE_RH_CONGE')) {
    return router.parseUrl('/conges');
  }
  if (auth.hasRole('ROLE_RH_CARTE_PRO') || auth.hasRole('ROLE_ADMIN_RH')) {
    return router.parseUrl('/cartes-professionnelles');
  }

  return router.parseUrl('/profil');
};
