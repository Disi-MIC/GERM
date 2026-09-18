<?php

namespace App\Tests\EventListener;

use App\Entity\ComposantMateriel;
use App\Entity\Enum\ActionAudit;
use App\Entity\Maintenance;
use App\Entity\Personnel;
use App\Entity\TicketEscalade;
use App\Entity\TicketIncident;
use App\Entity\User;
use App\EventListener\EntiteAuditListener;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * GERM-018 : verrouille le comportement de EntiteAuditListener, jusqu'ici
 * seulement vérifié à la main (voir OWASP-GERM-AUDIT.md, §12 point 5) —
 * couvre la régression sur les entités RH déjà auditées et l'extension du
 * 18/09/2026 aux entités opérationnelles IT (TicketIncident, Maintenance,
 * ComposantMateriel).
 */
class EntiteAuditListenerTest extends TestCase
{
    private function em(): EntityManagerInterface
    {
        return $this->createStub(EntityManagerInterface::class);
    }

    public function testCreationEntiteRhJournaliseeApresFlush(): void
    {
        $personnel = new Personnel();
        $propriete = new \ReflectionProperty(Personnel::class, 'id');
        $propriete->setValue($personnel, 42);

        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')
            ->with(ActionAudit::ENTITE_CREEE, 'succes', null, 'Personnel', 42, null);

        $listener = new EntiteAuditListener($auditLog);
        $listener->postPersist(new PostPersistEventArgs($personnel, $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }

    /** @return iterable<string, array{object, string}> */
    public static function entitesItProvider(): iterable
    {
        yield 'TicketIncident' => [new TicketIncident(), 'TicketIncident'];
        yield 'Maintenance' => [new Maintenance(), 'Maintenance'];
        yield 'ComposantMateriel' => [new ComposantMateriel(), 'ComposantMateriel'];
    }

    #[DataProvider('entitesItProvider')]
    public function testCreationEntiteItJournaliseeApresFlush(object $entite, string $libelle): void
    {
        $propriete = new \ReflectionProperty($entite::class, 'id');
        $propriete->setValue($entite, 7);

        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')
            ->with(ActionAudit::ENTITE_CREEE, 'succes', null, $libelle, 7, null);

        $listener = new EntiteAuditListener($auditLog);
        $listener->postPersist(new PostPersistEventArgs($entite, $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }

    /**
     * Doctrine efface l'identifiant avant de notifier postRemove : l'id doit
     * être capturé plus tôt, dans preRemove (voir le docblock de la classe).
     */
    public function testSuppressionEntiteItJournaliseeAvecIdCaptureAvantSuppression(): void
    {
        $ticket = new TicketIncident();
        $propriete = new \ReflectionProperty(TicketIncident::class, 'id');
        $propriete->setValue($ticket, 99);

        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::once())->method('enregistrer')
            ->with(ActionAudit::ENTITE_SUPPRIMEE, 'succes', null, 'TicketIncident', 99, null);

        $listener = new EntiteAuditListener($auditLog);
        $listener->preRemove(new PreRemoveEventArgs($ticket, $this->em()));

        // Simule le comportement observé de Doctrine : l'id est nul au moment de postRemove.
        $propriete->setValue($ticket, null);
        $listener->postRemove(new PostRemoveEventArgs($ticket, $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }

    /**
     * TicketEscalade est déjà lui-même un journal append-only (voir son
     * docblock) — comme les entités Historique et AuditLog, il est
     * délibérément exclu de ENTITES_AUDITEES pour ne pas journaliser un journal.
     */
    public function testTicketEscaladeNestJamaisJournalisee(): void
    {
        $escalade = new TicketEscalade();

        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::never())->method('enregistrer');

        $listener = new EntiteAuditListener($auditLog);
        $listener->preRemove(new PreRemoveEventArgs($escalade, $this->em()));
        $listener->postPersist(new PostPersistEventArgs($escalade, $this->em()));
        $listener->postRemove(new PostRemoveEventArgs($escalade, $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }

    /** User est une entité réelle (avec getId()) mais délibérément hors de ENTITES_AUDITEES (voir le docblock de la classe). */
    public function testEntiteHorsListeIgnoreeEtNeDeclenchePasDeFlushVide(): void
    {
        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::never())->method('enregistrer');

        $listener = new EntiteAuditListener($auditLog);
        $listener->postPersist(new PostPersistEventArgs(new User(), $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }

    /** Plusieurs créations dans le même flush doivent toutes être rejouées, puis la file vidée. */
    public function testPlusieursCreationsDansLeMemeFlushSontToutesRejoueesPuisPurgees(): void
    {
        $ticket = new TicketIncident();
        (new \ReflectionProperty(TicketIncident::class, 'id'))->setValue($ticket, 1);
        $maintenance = new Maintenance();
        (new \ReflectionProperty(Maintenance::class, 'id'))->setValue($maintenance, 2);

        $auditLog = $this->createMock(AuditLogService::class);
        $auditLog->expects(self::exactly(2))->method('enregistrer');

        $listener = new EntiteAuditListener($auditLog);
        $listener->postPersist(new PostPersistEventArgs($ticket, $this->em()));
        $listener->postPersist(new PostPersistEventArgs($maintenance, $this->em()));
        $listener->postFlush(new PostFlushEventArgs($this->em()));

        // Un flush ultérieur sans nouvel événement ne doit rien rejouer une seconde fois.
        $listener->postFlush(new PostFlushEventArgs($this->em()));
    }
}
