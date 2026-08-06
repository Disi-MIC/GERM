import { HttpInterceptorFn } from '@angular/common/http';

/**
 * Ajoute withCredentials à chaque requête pour que le cookie de session
 * (posé par /api/login sur le firewall Symfony "api") soit envoyé même en
 * développement, où Angular (localhost:4200) et Symfony (127.0.0.1:8000)
 * sont deux origines distinctes.
 */
export const credentialsInterceptor: HttpInterceptorFn = (req, next) => {
  return next(req.clone({ withCredentials: true }));
};
