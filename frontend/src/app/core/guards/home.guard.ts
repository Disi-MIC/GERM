import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../auth.service';

/**
 * URL du premier espace d'administration pertinent parmi les rôles littéraux
 * de l'utilisateur (la hiérarchie de rôles Symfony n'est pas répercutée côté
 * frontend, voir AuthService.hasRole) — même priorité que la redirection
 * post-connexion, réutilisée par le bouton de bascule "Mon espace" /
 * "Administration" de la navbar (voir ShellComponent).
 */
export function administrationLandingUrl(auth: AuthService): string {
  if (auth.hasRole('ROLE_RH_PERSONNEL')) {
    return '/personnel';
  }
  if (auth.hasRole('ROLE_RH_CONGE')) {
    return '/conges';
  }
  if (auth.hasRole('ROLE_RH_CARTE_PRO') || auth.hasRole('ROLE_ADMIN_RH')) {
    return '/cartes-professionnelles';
  }
  if (auth.hasRole('ROLE_IT_TECHNICIEN') || auth.hasRole('ROLE_IT_RESPONSABLE')) {
    return '/materiel-informatique';
  }

  return '/profil';
}

/**
 * Route d'accueil ('/') : redirige vers le premier espace pertinent parmi
 * les rôles littéraux de l'utilisateur connecté. Un agent sans aucun rôle RH
 * (seulement ROLE_AGENT, le rôle de base de tout compte) atterrit sur son
 * profil.
 */
export const homeGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return router.parseUrl(administrationLandingUrl(auth));
};
