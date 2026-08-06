import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from '../auth.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.initialized()) {
    return auth.isLoggedIn() || router.parseUrl('/login');
  }

  return auth.fetchMe().pipe(
    map(() => true),
    catchError(() => of(router.parseUrl('/login'))),
  );
};
