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
  if (auth.hasRole('ROLE_IT_STOCK') || auth.hasRole('ROLE_IT_TICKETS') || auth.hasRole('ROLE_IT_RESPONSABLE')) {
    // Tableau de bord plutôt qu'une sous-page précise : accessible aux 3
    // profils IT (Stock/Tickets/Responsable), contrairement à
    // /materiel-informatique ou /tickets-informatique qui ne le sont pas
    // tous les deux à la fois.
    return '/dashboard-informatique';
  }
  if (auth.hasRole('ROLE_AUTORITE')) {
    return '/apercu-ministere';
  }

  return '/profil';
}

/**
 * Route d'accueil ('/') : atterrit toujours sur « Mon espace », web comme
 * mobile, même pour un compte RH/IT — administrationLandingUrl() n'entre en
 * jeu qu'ensuite, une fois le bouton "Administration" utilisé (voir
 * ShellComponent.basculerVue / MobileShellComponent.allerVersAdministration),
 * et seulement après reconfirmation du mot de passe (adminAccessGuard).
 */
export const homeGuard: CanActivateFn = () => {
  const router = inject(Router);

  return router.parseUrl('/mon-espace/tableau-de-bord');
};
