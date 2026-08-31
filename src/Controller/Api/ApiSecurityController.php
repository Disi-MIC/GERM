<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Service\CurrentUserPayloadBuilder;
use App\Service\SsoTokenService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Routes techniques pour le firewall "api". La déconnexion est entièrement
 * interceptée par la clé "logout" du firewall (jamais exécutée, même idiome
 * que SecurityController::logout() pour "main"). La connexion est différente :
 * sans "success_handler" explicite, json_login authentifie PUIS laisse la
 * requête continuer jusqu'à ce contrôleur — il doit donc renvoyer une vraie
 * réponse (le profil de l'utilisateur, comme /api/me) plutôt que lever une
 * exception.
 */
class ApiSecurityController extends AbstractController
{
    public function __construct(
        private readonly CurrentUserPayloadBuilder $currentUserPayloadBuilder,
        private readonly SsoTokenService $ssoTokenService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Doit renvoyer le même payload que MeController::moi(), plus `ssoToken`
     * si l'émission réussit (absent de /api/me : seule la connexion émet un
     * nouveau jeton SSO, voir SsoTokenService) — voir CurrentUserPayloadBuilder :
     * AuthService.login() (frontend) peuple son signal currentUser directement
     * depuis cette réponse, sans jamais rappeler /api/me dans la même session
     * app.
     *
     * L'émission du jeton SSO est volontairement non bloquante : la clé
     * privée RS256 (config/jwt/sso_private.pem, voir SsoTokenService) doit
     * être générée par environnement et n'est pas garantie présente partout
     * dès le déploiement de cette fonctionnalité (Partie 1 du plan MIC-GAR,
     * pas encore complétée sur tous les environnements) — la connexion à GERM
     * elle-même ne doit jamais dépendre de cette intégration secondaire.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $payload = $this->currentUserPayloadBuilder->build($user);

        try {
            $payload['ssoToken'] = $this->ssoTokenService->issue($user);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Émission du jeton SSO impossible, connexion GERM poursuivie sans lui.', ['exception' => $e]);
        }

        return $this->json($payload);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne doit jamais être exécutée : interceptée par la clé logout du firewall.');
    }
}
