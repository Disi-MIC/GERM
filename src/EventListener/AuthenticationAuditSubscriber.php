<?php

namespace App\EventListener;

use App\Entity\Enum\ActionAudit;
use App\Entity\User;
use App\Service\AuditLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Journalise les connexions, échecs de connexion et déconnexions — sur les
 * DEUX pare-feu (`api` et `main`) puisque ces événements Symfony sont émis
 * indépendamment de l'authentificateur qui les déclenche (json_login comme
 * LoginFormAuthenticator). Un seul point d'écriture, pas un par pare-feu.
 */
class AuthenticationAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $utilisateur = $event->getUser();

        $this->auditLog->enregistrer(
            ActionAudit::LOGIN_REUSSI,
            utilisateurEmail: $utilisateur instanceof User ? $utilisateur->getEmail() : $utilisateur->getUserIdentifier(),
        );
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->auditLog->enregistrer(
            ActionAudit::LOGIN_ECHEC,
            resultat: 'echec',
            details: $event->getException()->getMessageKey(),
            utilisateurEmail: $this->extraireEmailTente($event->getRequest()),
        );
    }

    public function onLogout(LogoutEvent $event): void
    {
        // getToken() peut être null (déconnexion sans session active) : rien
        // d'exploitable à journaliser dans ce cas.
        if (null === $event->getToken()) {
            return;
        }

        $this->auditLog->enregistrer(
            ActionAudit::LOGOUT,
            utilisateurEmail: $event->getToken()->getUserIdentifier(),
        );
    }

    /**
     * L'email tenté n'est disponible ni sur LoginFailureEvent ni de façon
     * fiable sur son passeport (absent si UserBadge a déjà échoué à
     * résoudre l'utilisateur) : on retombe sur le corps de la requête,
     * différent entre json_login (JSON) et LoginFormAuthenticator (formulaire).
     */
    private function extraireEmailTente(Request $request): ?string
    {
        $email = $request->request->get('email');
        if (\is_string($email)) {
            return $email;
        }

        try {
            $donnees = $request->toArray();
        } catch (\Throwable) {
            return null;
        }

        return \is_string($donnees['email'] ?? null) ? $donnees['email'] : null;
    }
}
