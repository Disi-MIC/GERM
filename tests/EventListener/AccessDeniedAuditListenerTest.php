<?php

namespace App\Tests\EventListener;

use App\Entity\Enum\ActionAudit;
use App\EventListener\AccessDeniedAuditListener;
use App\Service\AuditLogService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedAuditListenerTest extends TestCase
{
    private function noyauFactice(): HttpKernelInterface
    {
        return $this->createStub(HttpKernelInterface::class);
    }

    private function evenement(string $uri, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent($this->noyauFactice(), Request::create($uri), HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testAccesRefuseSurApiJournalise(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::ACCES_REFUSE,
            'echec',
            'GET /api/personnels/42',
        );

        $event = $this->evenement('/api/personnels/42', new AccessDeniedException());

        (new AccessDeniedAuditListener($auditLog))->__invoke($event);
    }

    /**
     * Le cas réel en production : Symfony convertit systématiquement
     * AccessDeniedException (Core) en AccessDeniedHttpException avant que
     * cet écouteur, enregistré à basse priorité, ne s'exécute — vérifié
     * empiriquement (voir le docblock de la classe). Une régression ici
     * ferait taire silencieusement TOUS les accès refusés en conditions
     * réelles, un scénario qui aurait pu passer inaperçu sans ce test — c'est
     * exactement ce qui s'est produit avant qu'il ne soit écrit.
     */
    public function testAccesRefuseConvertiEnHttpExceptionEstAussiJournalise(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::ACCES_REFUSE,
            'echec',
            'GET /api/personnels/42',
        );

        $event = $this->evenement('/api/personnels/42', new AccessDeniedHttpException('Access Denied.'));

        (new AccessDeniedAuditListener($auditLog))->__invoke($event);
    }

    public function testAccesRefuseSurAdminJournalise(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')->with(
            ActionAudit::ACCES_REFUSE,
            'echec',
            self::anything(),
        );

        $event = $this->evenement('/admin/personnel', new AccessDeniedException());

        (new AccessDeniedAuditListener($auditLog))->__invoke($event);
    }

    /** Une route hors /api et /admin (ex. /verif-carte) ne relève pas de ce contrôle : ne rien journaliser. */
    public function testAccesRefuseHorsPerimetreIgnore(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::never())->method('enregistrer');

        $event = $this->evenement('/verif-carte/CP-1', new AccessDeniedException());

        (new AccessDeniedAuditListener($auditLog))->__invoke($event);
    }

    /** Toute autre exception (404, erreur de validation...) n'est pas de son ressort. */
    public function testAutreExceptionIgnoree(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::never())->method('enregistrer');

        $event = $this->evenement('/api/personnels/42', new \RuntimeException('autre chose'));

        (new AccessDeniedAuditListener($auditLog))->__invoke($event);
    }

    /** L'écouteur ne doit jamais toucher à la réponse : elle reste entièrement produite par le gestionnaire de sécurité natif. */
    public function testNeDefinitJamaisDeReponse(): void
    {
        $auditLog = $this->createStub(AuditLogService::class);

        $event = $this->evenement('/api/personnels/42', new AccessDeniedException());
        (new AccessDeniedAuditListener($auditLog))->__invoke($event);

        self::assertFalse($event->hasResponse());
    }
}
