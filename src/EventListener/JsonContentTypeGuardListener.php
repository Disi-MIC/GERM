<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Protection CSRF de l'API, en deux barrières complémentaires : l'origine de
 * la requête d'abord (voir origineAutorisee(), seule barrière efficace sur les
 * écritures sans corps), le Content-Type ensuite.
 *
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

    public function __construct(
        #[Autowire('%env(CORS_ALLOW_ORIGIN)%')] private readonly string $originesAutorisees,
    ) {
    }

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

        // Barrière principale : l'origine déclarée par le navigateur.
        //
        // Le contrôle du Content-Type ci-dessous ne couvre pas les écritures
        // sans corps (ex. POST /api/demandes-decision/{id}/valider, qui ne
        // prend aucun payload) : un site tiers pouvait les déclencher par un
        // <form> vide ou un fetch() sans corps, tous deux "simples" donc sans
        // préflight CORS, avec le cookie de session joint puisqu'il est
        // SameSite=None.
        //
        // L'en-tête Origin est posé par le navigateur lui-même sur toute
        // requête d'écriture et ne peut pas être falsifié par la page
        // attaquante. On réutilise l'allowlist CORS déjà en place plutôt que
        // d'en maintenir une seconde : c'est la même question ("qui a le droit
        // de nous appeler ?"), donc la même source de vérité — et l'app
        // Capacitor (capacitor://localhost) y figure déjà.
        //
        // Origin absent (client non navigateur, ou même origine sur certains
        // navigateurs) : on retombe sur le contrôle du Content-Type. Un client
        // qui n'envoie pas d'Origin n'est de toute façon pas un vecteur CSRF,
        // puisque le CSRF suppose un navigateur porteur du cookie.
        $origine = $request->headers->get('Origin');
        if (null !== $origine && !$this->origineAutorisee($origine)) {
            $event->setResponse(new JsonResponse(
                ['errors' => ['origin' => 'Origine non autorisée.']],
                JsonResponse::HTTP_FORBIDDEN,
            ));

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

    /**
     * Même évaluation que NelmioCorsBundle avec `origin_regex: true` (voir
     * config/packages/nelmio_cors.yaml) : CORS_ALLOW_ORIGIN est un motif sans
     * délimiteurs, appliqué ici avec les mêmes délimiteurs que le bundle pour
     * que les deux contrôles ne puissent pas diverger.
     */
    private function origineAutorisee(string $origine): bool
    {
        return 1 === preg_match('{'.$this->originesAutorisees.'}i', $origine);
    }
}
