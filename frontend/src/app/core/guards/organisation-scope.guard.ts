import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../auth.service';
import { CurrentUser } from '../models/user.model';

/**
 * Vérifie un champ de périmètre organisationnel dérivé (`data.champ` de la
 * route, ex. 'serviceResponsableId') plutôt qu'un rôle : l'accès à "Aperçu
 * de mon service"/"ma direction" découle du champ Service::$responsable /
 * Direction::$directeur assigné par le RH Admin (voir /api/me), pas d'un
 * rôle Symfony dédié — voir ApercuOrganisationController. À utiliser après
 * authGuard, comme roleGuard.
 */
export const organisationScopeGuard: CanActivateFn = (route) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const champ = route.data['champ'] as keyof CurrentUser | undefined;
  const utilisateur = auth.currentUser();

  if (champ && utilisateur && utilisateur[champ] != null) {
    return true;
  }

  return router.parseUrl('/acces-refuse');
};
