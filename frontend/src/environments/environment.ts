/**
 * Environnement de développement local. Doit rester cohérent avec le port
 * du serveur "germ" dans .claude/launch.json (symfony serve -d, qui gère
 * son propre certificat HTTPS local — voir `symfony server:status`).
 * Remplacé par environment.prod.ts en build production (voir angular.json,
 * fileReplacements) — même logique que FRONTEND_URL côté Symfony.
 */
export const environment = {
  production: false,
  apiBase: 'https://localhost:8000/api',
};
