<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Émet le jeton SSO permettant à un agent déjà connecté sur GERM d'accéder
 * à MIC-GAR sans ressaisir ses identifiants — voir ApiSecurityController::
 * login(). RS256 (clé privée ici, clé publique côté MIC-GAR) plutôt qu'un
 * secret partagé HS256 : MIC-GAR n'a besoin que de vérifier la signature,
 * jamais de pouvoir en émettre — la clé privée ne sort donc jamais de GERM.
 *
 * Les claims reprennent exactement les champs de CurrentUserPayloadBuilder
 * (id/email/nom/prenom/roles), pour que l'identité vue par MIC-GAR soit
 * cohérente avec celle affichée dans GERM lui-même.
 */
class SsoTokenService
{
    private const ALGORITHM = 'RS256';
    private const DUREE_VALIDITE_SECONDES = 8 * 3600;

    public function __construct(
        #[Autowire('%kernel.project_dir%/config/jwt/sso_private.pem')]
        private readonly string $privateKeyPath,
        #[Autowire(env: 'SSO_JWT_ISSUER')]
        private readonly string $issuer,
        #[Autowire(env: 'SSO_JWT_AUDIENCE')]
        private readonly string $audience,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function issue(User $user): string
    {
        $now = time();

        $claims = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'exp' => $now + self::DUREE_VALIDITE_SECONDES,
            'sub' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $this->roleHierarchy->getReachableRoleNames($user->getRoles()),
        ];

        return JWT::encode($claims, $this->privateKey(), self::ALGORITHM);
    }

    private function privateKey(): string
    {
        $contents = @file_get_contents($this->privateKeyPath);

        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Clé privée SSO introuvable : "%s". Voir Partie 1 du plan MIC-GAR (génération de la paire de clés RS256 dans config/jwt/).', $this->privateKeyPath));
        }

        return $contents;
    }
}
