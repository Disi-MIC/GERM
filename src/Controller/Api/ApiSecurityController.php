<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\User;
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
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne doit jamais être exécutée : interceptée par la clé logout du firewall.');
    }
}
