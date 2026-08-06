<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Profil de l'utilisateur connecté, utilisé par le frontend Angular juste
 * après la connexion pour savoir quels rôles il porte et vers quelle
 * rubrique se rediriger.
 */
#[IsGranted('ROLE_AGENT')]
class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
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
}
