<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Ajoute les en-têtes de sécurité HTTP standards à toute réponse qui
 * transite par le noyau Symfony (API, /admin Twig, /verif-carte, /logout).
 *
 * Ne couvre PAS le shell SPA Angular lui-même (index.html, JS, CSS) : en
 * production, Apache le sert directement en fichier statique sans jamais
 * passer par index.php (voir la RewriteRule vers /app/index.html du vhost),
 * donc ce listener ne peut pas l'atteindre — les mêmes en-têtes y sont posés
 * séparément côté configuration Apache (mod_headers).
 *
 * CSP autorise cdn.jsdelivr.net (script/style/font) : templates/base.html.twig
 * y charge Bootstrap Icons, les polices Inter (@fontsource, voir ce même
 * fichier) et le bundle JS de Bootstrap — Bootstrap CSS lui-même est
 * recompilé localement (public/css/admin-theme.css, voir sa source .scss)
 * pour reprendre la palette Gentelella du SPA Angular, donc servi en
 * 'self'. Retirer l'autorisation jsdelivr casserait quand même le rendu de
 * l'admin Twig (icônes, police, interactions JS). `style-src 'unsafe-inline'`
 * reste nécessaire pour les nombreux attributs `style="..."` des templates
 * admin (aucun `<script>` inline trouvé en revanche, donc script-src reste
 * strict).
 */
#[AsEventListener(event: ResponseEvent::class)]
class SecurityHeadersListener
{
    private const CSP = "default-src 'self'; "
        ."script-src 'self' https://cdn.jsdelivr.net; "
        ."style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        ."font-src 'self' https://cdn.jsdelivr.net; "
        ."img-src 'self' data:; "
        ."connect-src 'self'; "
        // 'none' et non 'self' : l'en-tête X-Frame-Options ci-dessous vaut
        // DENY, et les navigateurs modernes donnent la priorité à la CSP.
        // Avec 'self', le framing depuis la même origine restait donc
        // autorisé malgré un X-Frame-Options qui prétendait l'interdire —
        // deux en-têtes contradictoires, exactement ce que le durcissement
        // cherchait à éviter. Aucune page servie par Symfony n'est embarquée
        // dans une iframe (les seules iframes du projet sont côté Angular, et
        // affichent des blob: — ce que régit frame-src, pas frame-ancestors).
        ."frame-ancestors 'none'; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."object-src 'none'";

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Content-Security-Policy', self::CSP);
        // Aucune page servie par Symfony (admin Twig, vérification de carte,
        // réponses d'API) n'a besoin de ces API navigateur : les refuser
        // explicitement limite ce qu'un script injecté pourrait en tirer.
        // L'app mobile utilise bien l'appareil photo, mais via les plugins
        // natifs Capacitor, hors de portée de cet en-tête.
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // HSTS uniquement en HTTPS : l'envoyer sur une réponse HTTP (ne
        // devrait jamais arriver ici, le vhost redirige déjà 80→443) forcerait
        // à tort le navigateur à retenir une politique HTTPS pour un domaine
        // potentiellement mal configuré.
        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
