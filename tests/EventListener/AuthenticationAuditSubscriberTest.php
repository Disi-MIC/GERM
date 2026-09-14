<?php

namespace App\Tests\EventListener;

use App\Entity\Enum\ActionAudit;
use App\Entity\User;
use App\EventListener\AuthenticationAuditSubscriber;
use App\Service\AuditLogService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Verrouille GERM-018 (socle minimal) : chaque connexion, échec de connexion
 * et déconnexion doit produire exactement une ligne d'audit, sur les deux
 * pare-feu — ces événements Symfony ne distinguent pas api/main, un seul
 * abonné suffit.
 */
class AuthenticationAuditSubscriberTest extends TestCase
{
    private function authenticatorFactice(): AuthenticatorInterface
    {
        return $this->createStub(AuthenticatorInterface::class);
    }

    public function testEvenementsSouscrits(): void
    {
        $evenements = AuthenticationAuditSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(LoginSuccessEvent::class, $evenements);
        self::assertArrayHasKey(LoginFailureEvent::class, $evenements);
        self::assertArrayHasKey(LogoutEvent::class, $evenements);
    }

    public function testLoginReussiJournaliseAvecEmailDuCompteEntite(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGIN_REUSSI,
            'succes',
            null,
            null,
            null,
            'agent@germ.local',
        );

        $utilisateur = new User();
        $utilisateur->setEmail('agent@germ.local');

        $token = $this->createStub(TokenInterface::class);
        $event = new LoginSuccessEvent(
            $this->authenticatorFactice(),
            new Passport(new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge('agent@germ.local', fn () => $utilisateur), new \Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials('x')),
            $token,
            Request::create('/api/login'),
            null,
            'api',
        );

        (new AuthenticationAuditSubscriber($auditLog))->onLoginSuccess($event);
    }

    /** Un utilisateur Symfony générique (pas notre entité User) doit quand même produire un email via getUserIdentifier(). */
    public function testLoginReussiAvecUtilisateurNonEntiteUtiliseIdentifiant(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGIN_REUSSI,
            'succes',
            null,
            null,
            null,
            'generic-user',
        );

        $token = $this->createStub(TokenInterface::class);
        $event = new LoginSuccessEvent(
            $this->authenticatorFactice(),
            new Passport(new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge('generic-user', fn () => new InMemoryUser('generic-user', null)), new \Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials('x')),
            $token,
            Request::create('/admin/login'),
            null,
            'main',
        );

        (new AuthenticationAuditSubscriber($auditLog))->onLoginSuccess($event);
    }

    public function testLoginEchecJournaliseEnEchecAvecEmailExtraitDuJson(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGIN_ECHEC,
            'echec',
            self::anything(),
            null,
            null,
            'tente@germ.local',
        );

        $request = Request::create('/api/login', 'POST', content: json_encode(['email' => 'tente@germ.local', 'password' => 'x']));
        $event = new LoginFailureEvent(
            new BadCredentialsException(),
            $this->authenticatorFactice(),
            $request,
            null,
            'api',
        );

        (new AuthenticationAuditSubscriber($auditLog))->onLoginFailure($event);
    }

    /** Le pare-feu main (LoginFormAuthenticator) transmet l'email en champ de formulaire, pas en JSON. */
    public function testLoginEchecExtraitEmailDunFormulaireClassique(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGIN_ECHEC,
            'echec',
            self::anything(),
            null,
            null,
            'admin@germ.local',
        );

        $request = Request::create('/admin/login', 'POST', ['email' => 'admin@germ.local', 'password' => 'x']);
        $event = new LoginFailureEvent(
            new BadCredentialsException(),
            $this->authenticatorFactice(),
            $request,
            null,
            'main',
        );

        (new AuthenticationAuditSubscriber($auditLog))->onLoginFailure($event);
    }

    /** Ni champ de formulaire ni JSON exploitable : ne doit jamais faire planter la journalisation. */
    public function testLoginEchecSansEmailExtractibleNePlantePas(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGIN_ECHEC,
            'echec',
            self::anything(),
            null,
            null,
            null,
        );

        $request = Request::create('/api/login', 'POST', content: 'ceci-nest-pas-du-json');
        $event = new LoginFailureEvent(
            new BadCredentialsException(),
            $this->authenticatorFactice(),
            $request,
            null,
            'api',
        );

        (new AuthenticationAuditSubscriber($auditLog))->onLoginFailure($event);
    }

    public function testLogoutJournaliseAvecIdentifiantDuToken(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::LOGOUT,
            'succes',
            null,
            null,
            null,
            'agent@germ.local',
        );

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('agent@germ.local');

        (new AuthenticationAuditSubscriber($auditLog))->onLogout(new LogoutEvent(Request::create('/api/logout'), $token));
    }

    /** Déconnexion sans session active (token null) : rien à journaliser, et surtout ne pas planter. */
    public function testLogoutSansTokenNecritRien(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::never())->method('enregistrer');

        (new AuthenticationAuditSubscriber($auditLog))->onLogout(new LogoutEvent(Request::create('/api/logout'), null));
    }
}
