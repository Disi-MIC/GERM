<?php

namespace App\Tests\Service;

use App\Entity\AuditLog;
use App\Entity\Enum\ActionAudit;
use App\Entity\User;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Verrouille le comportement du point d'écriture unique du journal d'audit :
 * résolution de l'utilisateur connecté vs email fourni explicitement, capture
 * de l'IP et du user-agent, et troncature défensive de ce dernier.
 */
class AuditLogServiceTest extends TestCase
{
    private function service(?User $utilisateurConnecte, ?Request $request, EntityManagerInterface $em): AuditLogService
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($utilisateurConnecte);

        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return new AuditLogService($em, $requestStack, $security);
    }

    /**
     * @return array{0: EntityManagerInterface, 1: object{log: ?AuditLog}}
     *
     * Un stdClass plutôt qu'un tableau : un tableau serait copié par valeur
     * au retour de cette méthode et à la destructuration côté appelant, ce
     * qui isolerait la mutation faite par le callback du reste du test. Un
     * objet reste une seule et même instance, partagée par les deux.
     */
    private function emCapturant(): array
    {
        $capture = new \stdClass();
        $capture->log = null;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')
            ->with(self::isInstanceOf(AuditLog::class))
            ->willReturnCallback(static function (AuditLog $log) use ($capture): void {
                $capture->log = $log;
            });
        $em->expects(self::once())->method('flush');

        return [$em, $capture];
    }

    public function testUtilisateurConnecteRenseigneCompteEtEmail(): void
    {
        [$em, $capture] = $this->emCapturant();
        $utilisateur = new User();
        $utilisateur->setEmail('agent@germ.local');

        $request = Request::create('/api/personnels/export.csv');
        $request->headers->set('User-Agent', 'Mozilla/5.0');

        $this->service($utilisateur, $request, $em)->enregistrer(ActionAudit::EXPORT_CREE);

        self::assertSame($utilisateur, $capture->log->getUtilisateur());
        self::assertSame('agent@germ.local', $capture->log->getUtilisateurEmail());
    }

    /** Un échec de connexion n'a pas d'utilisateur authentifié : l'email tenté, passé explicitement, doit être conservé. */
    public function testSansUtilisateurConnecteUtiliseEmailExplicite(): void
    {
        [$em, $capture] = $this->emCapturant();
        $request = Request::create('/api/login');

        $this->service(null, $request, $em)->enregistrer(
            ActionAudit::LOGIN_ECHEC,
            resultat: 'echec',
            utilisateurEmail: 'inconnu@germ.local',
        );

        self::assertNull($capture->log->getUtilisateur());
        self::assertSame('inconnu@germ.local', $capture->log->getUtilisateurEmail());
    }

    public function testCaptureIpEtUserAgentDeLaRequeteCourante(): void
    {
        [$em, $capture] = $this->emCapturant();
        $request = Request::create('/api/composants-materiel');
        $request->headers->set('User-Agent', 'TestAgent/1.0');
        $request->server->set('REMOTE_ADDR', '198.51.100.7');

        $this->service(null, $request, $em)->enregistrer(ActionAudit::ACCES_REFUSE, resultat: 'echec');

        self::assertSame('198.51.100.7', $capture->log->getIpAddress());
        self::assertSame('TestAgent/1.0', $capture->log->getUserAgent());
    }

    /** Défense contre un en-tête User-Agent forgé et démesurément long. */
    public function testUserAgentTronqueA500Caracteres(): void
    {
        [$em, $capture] = $this->emCapturant();
        $request = Request::create('/api/login');
        $request->headers->set('User-Agent', str_repeat('A', 2000));

        $this->service(null, $request, $em)->enregistrer(ActionAudit::LOGIN_ECHEC, resultat: 'echec');

        self::assertSame(500, mb_strlen($capture->log->getUserAgent()));
    }

    public function testAucuneRequeteCouranteRetombeSurIpInconnue(): void
    {
        [$em, $capture] = $this->emCapturant();

        $this->service(null, null, $em)->enregistrer(ActionAudit::LOGOUT);

        self::assertSame('inconnue', $capture->log->getIpAddress());
        self::assertNull($capture->log->getUserAgent());
    }

    public function testEntiteEtDetailsTransmisTelsQuels(): void
    {
        [$em, $capture] = $this->emCapturant();

        $this->service(null, Request::create('/api/cartes-professionnelles/1/valider'), $em)->enregistrer(
            ActionAudit::CARTE_VALIDEE,
            details: 'CP-2026-042',
            entite: 'CarteProfessionnelle',
            entiteId: 1,
        );

        self::assertSame('CarteProfessionnelle', $capture->log->getEntite());
        self::assertSame(1, $capture->log->getEntiteId());
        self::assertSame('CP-2026-042', $capture->log->getDetails());
    }

    public function testResultatSuccesParDefaut(): void
    {
        [$em, $capture] = $this->emCapturant();

        $this->service(null, Request::create('/'), $em)->enregistrer(ActionAudit::LOGIN_REUSSI);

        self::assertSame('succes', $capture->log->getResultat());
    }
}
