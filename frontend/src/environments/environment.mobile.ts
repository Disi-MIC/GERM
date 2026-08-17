/**
 * Build consommé par l'app native (Capacitor) — voir capacitor.config.ts.
 * apiBase doit être une URL absolue : contrairement au web où Angular et
 * l'API sont servis derrière le même reverse proxy (environment.prod.ts
 * utilise '/api', un chemin relatif), la WebView native charge l'app depuis
 * une origine locale (capacitor://localhost sur iOS) qui n'a pas de lien
 * avec le vrai domaine du backend — un chemin relatif y résoudrait vers
 * l'origine de la WebView elle-même, pas vers l'API.
 *
 * Valeur actuelle : nom d'hôte Bonjour/mDNS du Mac (".local"), pensé pour
 * tester sur un iPhone PHYSIQUE en dev — un appareil réel n'est pas la même
 * machine que le Mac, donc "localhost" y désignerait le téléphone lui-même
 * (rien n'y écoute sur ce port). Le Mac et le téléphone doivent être sur le
 * même réseau Wi-Fi (multicast/mDNS non bloqué), et le serveur Symfony doit
 * tourner avec --allow-all-ip (par défaut il n'écoute que sur 127.0.0.1).
 *
 * Un nom ".local" plutôt qu'une IP brute : contrairement à l'IP Wi-Fi du Mac
 * (attribuée par DHCP, change à chaque reconnexion réseau — vécu deux fois
 * dans ce projet, cassant systématiquement apiBase ET le certificat TLS dont
 * le SAN listait l'ancienne IP), le nom Bonjour reste stable tant que le nom
 * de la machine ne change pas (Réglages Système > Général > Partage). Si le
 * Mac est un jour renommé, ajuster ici (voir `scutil --get LocalHostName`)
 * et régénérer le certificat avec le nouveau nom dans son SAN.
 * Sur SIMULATEUR iOS, "localhost" fonctionnerait aussi (réseau partagé avec
 * le Mac) mais ce nom fonctionne dans les deux cas.
 *
 * HTTPS (pas HTTP) : nécessaire pour que le cookie de session fonctionne.
 * La WebView (capacitor://localhost) et l'API sont des origines différentes
 * ("cross-site"), donc le cookie doit être SameSite=None (voir
 * config/packages/framework.yaml, bloc when@dev) — et SameSite=None exige
 * une connexion sécurisée (cookie_secure), sinon le navigateur/WebView le
 * rejette silencieusement et chaque appel API après le login échoue en 401.
 * Le certificat local (mkcert via `symfony server:ca:install`) doit être
 * installé et approuvé comme profil sur l'appareil physique pour que la
 * connexion HTTPS soit acceptée (voir rootCA.pem fourni à part).
 *
 * À remplacer par le vrai domaine public du backend (ex:
 * https://api.germ.mincom.sn/api) avant toute distribution réelle/au store.
 */
export const environment = {
  production: true,
  apiBase: 'https://macbook-pro-de-alassane.local:8010/api',
};
