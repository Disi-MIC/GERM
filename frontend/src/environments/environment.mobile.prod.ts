/**
 * Build consommé par l'app native (Capacitor) pour un usage en interne au
 * Ministère — voir environment.mobile.ts pour la variante développement
 * (pointant sur ce Mac). apiBase pointe ici sur germ-appdbserver
 * (10.112.26.30), joignable uniquement depuis le réseau interne du
 * ministère (WiFi sur site ou VPN) : pas de domaine public pour l'instant,
 * déploiement provisoire en attendant l'aval de l'autorité pour une
 * diffusion plus large (voir aussi config/packages/framework.yaml,
 * bloc when@prod, pour le cookie de session cross-origin).
 *
 * Le certificat HTTPS de ce serveur est signé par une autorité interne
 * dédiée ("GERM Internal Root CA", voir scripts/germ-internal-ca/) plutôt
 * que par une autorité publique reconnue — chaque appareil doit installer
 * son profil de confiance (GERM-Internal-CA.mobileconfig) avant que l'app
 * puisse s'y connecter, sans quoi la connexion HTTPS est rejetée.
 */
export const environment = {
  production: true,
  apiBase: 'https://10.112.26.30/api',
};
