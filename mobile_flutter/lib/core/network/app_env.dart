/// Équivalent de environment.mobile.ts / environment.mobile.prod.ts côté
/// Angular : apiBase doit être une URL absolue (contrairement au web, l'app
/// native ne partage pas d'origine avec l'API — un chemin relatif comme
/// '/api' résoudrait vers l'app elle-même, pas vers le serveur).
///
/// Valeur injectée à la compilation via --dart-define=API_BASE=... (un
/// flavor par cible, voir mobile_flutter/README.md pour les commandes) :
/// - dev  : hôte Bonjour/mDNS de la machine de développement, port 8000
///          (symfony serve) — cohérent avec environment.mobile.ts.
/// - prod : IP interne du ministère (10.112.26.30) — cohérent avec
///          environment.mobile.prod.ts. HTTPS signé par un CA interne (voir
///          scripts/germ-internal-ca/), chaque appareil doit lui faire
///          confiance avant de pouvoir s'y connecter (voir la note Android
///          dans le plan : network_security_config.xml doit explicitement
///          faire confiance aux CA installés par l'utilisateur, contrairement
///          à iOS où c'est le comportement par défaut une fois le profil
///          approuvé).
class AppEnv {
  AppEnv._();

  static const apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://macbook-pro-de-alassane.local:8000/api',
  );
}
