/**
 * Environnement de production. À ajuster avec le domaine réel du backend
 * une fois déployé (ex: https://api.germ.mincom.sn/api ou un chemin relatif
 * '/api' si frontend et backend sont servis derrière le même reverse proxy).
 */
export const environment = {
  production: true,
  apiBase: '/api',
};
