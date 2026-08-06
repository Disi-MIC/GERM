import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../auth.service';

/**
 * Vérifie que l'utilisateur connecté porte au moins un des rôles indiqués
 * dans `data.roles` de la route. À utiliser après authGuard (suppose que
 * l'utilisateur courant est déjà chargé).
 */
export const roleGuard: CanActivateFn = (route) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const requiredRoles = (route.data['roles'] as string[] | undefined) ?? [];

  if (requiredRoles.length === 0 || auth.hasAnyRole(requiredRoles)) {
    return true;
  }

  return router.parseUrl('/acces-refuse');
};
