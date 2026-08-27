import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'sn.gouv.mincom.germ',
  appName: 'GERM',
  webDir: 'dist/frontend/browser',
  plugins: {
    // Sans ça, les requêtes passent par le moteur réseau de la WebView
    // (WKWebView), qui a un cookie jar à part et ne renvoie pas fiablement
    // le cookie de session sur les appels vers une origine différente
    // (capacitor://localhost → https://<ip>:8010), même avec
    // SameSite=None + Secure côté serveur : le login réussit mais chaque
    // appel suivant repart anonyme (401). CapacitorHttp fait transiter
    // fetch/XHR par la couche native (URLSession) à la place, qui gère
    // correctement les cookies cross-origin.
    CapacitorHttp: {
      enabled: true,
    },
    SplashScreen: {
      // Pas d'écran de lancement personnalisé : masqué immédiatement, sans
      // délai ni fondu (voir LaunchScreen.storyboard, qui n'a plus d'image).
      launchAutoHide: true,
      launchShowDuration: 0,
    },
  },
};

export default config;
