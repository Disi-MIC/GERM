<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Rejette toute requête d'écriture (POST/PUT/PATCH/DELETE) sur /api dont le
 * Content-Type n'est ni JSON ni un envoi de fichier (multipart/form-data) —
 * défense contre le CSRF côté API : le cookie de session doit être
 * SameSite=None (voir framework.yaml) pour fonctionner dans la WebView
 * Capacitor cross-origin, ce qui l'expose à une requête "simple" (donc sans
 * préflight CORS) forgée par un site tiers via un <form enctype="text/plain">
 * dont le corps est construit pour rester un JSON valide malgré l'encodage
 * form — un navigateur ne peut pas donner à un tel formulaire un Content-Type
 * "application/json" (réservé aux requêtes non-simples, bloquées par CORS ici),
 * donc ce contrôle ferme la faille sans exiger de jeton CSRF ni de
 * changement côté Angular (HttpClient envoie déjà application/json par
 * défaut pour un corps objet, et multipart/form-data pour un FormData).
 */
#[AsEventListener(event: RequestEvent::class)]
class JsonContentTypeGuardListener
{
    private const METHODES_SURVEILLEES = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if (!\in_array($request->getMethod(), self::METHODES_SURVEILLEES, true)) {
            return;
        }

        // Aucun corps à valider (ex. DELETE sans payload) : rien à bloquer.
        if ('' === $request->getContent()) {
            return;
        }

        // Comparaison directe sur l'en-tête plutôt que
        // Request::getContentTypeFormat() : celui-ci classe
        // application/x-www-form-urlencoded dans le même panier ("form") que
        // multipart/form-data, alors que urlencoded est justement l'un des
        // Content-Type "simples" qu'un <form> tiers peut forger sans
        // préflight CORS — à rejeter ici comme text/plain, pas à autoriser.
        $contentType = $request->headers->get('Content-Type', '');
        $autorise = str_starts_with($contentType, 'application/json')
            || str_starts_with($contentType, 'multipart/form-data');

        if (!$autorise) {
            $event->setResponse(new JsonResponse(
                ['errors' => ['content-type' => 'Type de contenu non supporté.']],
                JsonResponse::HTTP_UNSUPPORTED_MEDIA_TYPE,
            ));
        }
    }
}
