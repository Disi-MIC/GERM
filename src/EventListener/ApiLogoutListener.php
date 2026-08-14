<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Sans réponse explicite, le LogoutListener du firewall "api" répond à
 * /api/logout par une redirection HTTP (comportement par défaut, pensé pour
 * un firewall web classique) — que le XHR Angular suit silencieusement
 * jusqu'à une page HTML, fait échouer le parsing JSON de HttpClient, et
 * empêche donc le `.subscribe()` de AuthService.logout() d'atteindre son
 * callback de succès (la session est bien détruite côté serveur, mais
 * l'app ne redirige jamais vers /login sans rechargement manuel). Le
 * firewall "main" (Twig, navigation plein-page) n'est pas concerné : sa
 * redirection vers app_login fonctionne nativement dans le navigateur.
 */
#[AsEventListener(event: LogoutEvent::class)]
class ApiLogoutListener
{
    public function __invoke(LogoutEvent $event): void
    {
        if (str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT));
        }
    }
}
