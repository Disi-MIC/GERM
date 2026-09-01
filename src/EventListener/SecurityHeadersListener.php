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
 * y charge Bootstrap et Bootstrap Icons — retirer cette autorisation casserait
 * tout le rendu de l'admin Twig. `style-src 'unsafe-inline'` reste nécessaire
 * pour les nombreux attributs `style="..."` des templates admin (aucun
 * `<script>` inline trouvé en revanche, donc script-src reste strict).
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
        ."frame-ancestors 'self'; "
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

        // HSTS uniquement en HTTPS : l'envoyer sur une réponse HTTP (ne
        // devrait jamais arriver ici, le vhost redirige déjà 80→443) forcerait
        // à tort le navigateur à retenir une politique HTTPS pour un domaine
        // potentiellement mal configuré.
        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
