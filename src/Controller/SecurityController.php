<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    // Volontairement pas '/login' : Angular possède déjà cette route côté
    // client pour le flux principal (json_login sur /api/login) — un même
    // chemin pour les deux aurait créé une collision en déploiement même-domaine
    // (le point d'entrée du firewall "main" redirigeant vers /login atterrissait
    // sur la page Angular, jamais sur ce formulaire Twig). Sous /admin comme le
    // reste du back-office de secours réservé au superadmin.
    #[Route('/admin/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): Response {
        // Si déjà connecté, direction le tableau de bord si superadmin (seul
        // rôle gardant un accès Twig), sinon directement le frontend Angular —
        // même logique qu'un ternaire dans
        // LoginFormAuthenticator::urlApresConnexion().
        if ($this->getUser()) {
            return $authorizationChecker->isGranted('ROLE_SUPERADMIN')
                ? $this->redirectToRoute('admin_dashboard_personnel')
                : $this->redirect($frontendUrl);
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut être vide - elle sera interceptée par la clé logout du firewall.');
    }
}
