/**
 * Environnement de développement local. Doit rester cohérent avec le port
 * du serveur "germ" dans .claude/launch.json (php -S 127.0.0.1:8010).
 * Remplacé par environment.prod.ts en build production (voir angular.json,
 * fileReplacements) — même logique que FRONTEND_URL côté Symfony.
 */
export const environment = {
  production: false,
  apiBase: 'http://localhost:8010/api',
};
