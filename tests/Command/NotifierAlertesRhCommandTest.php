<?php

namespace App\Tests\Command;

use App\Command\NotifierAlertesRhCommand;
use App\Entity\Direction;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\DirectionRepository;
use App\Repository\ServiceRepository;
use App\Service\EcheanceRhService;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class NotifierAlertesRhCommandTest extends TestCase
{
    private const VIDE = ['enRetard' => [], 'aVenir' => []];

    private function tester(
        EcheanceRhService $echeanceRhService,
        NotificationService $notificationService,
        ?ServiceRepository $serviceRepository = null,
        ?DirectionRepository $directionRepository = null,
    ): CommandTester {
        $serviceRepository ??= $this->createStubReturningEmpty(ServiceRepository::class, 'findSansResponsable');
        $directionRepository ??= $this->createStubReturningEmpty(DirectionRepository::class, 'findSansDirecteur');

        $application = new Application();
        $application->addCommand(new NotifierAlertesRhCommand($echeanceRhService, $serviceRepository, $directionRepository, $notificationService));

        return new CommandTester($application->find('app:notifier-alertes-rh'));
    }

    /** @template T @param class-string<T> $class @return T */
    private function createStubReturningEmpty(string $class, string $method)
    {
        $stub = $this->createStub($class);
        $stub->method($method)->willReturn([]);

        return $stub;
    }

    private function echeanceRhServiceVide(): EcheanceRhService
    {
        $stub = $this->createStub(EcheanceRhService::class);
        $stub->method('calculerDocuments')->willReturn(self::VIDE);
        $stub->method('calculerCartes')->willReturn(self::VIDE);
        $stub->method('calculerDecisions')->willReturn(self::VIDE);
        $stub->method('calculerDelegations')->willReturn(self::VIDE);

        return $stub;
    }

    public function testAucuneEcheanceNeNotifiePersonne(): void
    {
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())->method('notifierRoles');

        $tester = $this->tester($this->echeanceRhServiceVide(), $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDocumentsExpirantsNotifientRhPersonnelEtResponsable(): void
    {
        $echeanceRhService = $this->createStub(EcheanceRhService::class);
        $echeanceRhService->method('calculerDocuments')->willReturn([
            'enRetard' => [],
            'aVenir' => [['documentId' => 1, 'personnelId' => 1, 'personnel' => 'Awa Diop', 'libelle' => 'CNI', 'type' => 'Justificatif', 'echeance' => '2026-09-15', 'jours' => 5]],
        ]);
        $echeanceRhService->method('calculerCartes')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerDecisions')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerDelegations')->willReturn(self::VIDE);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_RH_PERSONNEL, User::ROLE_RH_RESPONSABLE],
                $this->stringContains('1 document(s) administratif(s)'),
                '/dashboard',
                $this->stringContains('CNI'),
            );

        $tester = $this->tester($echeanceRhService, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testLesTroisRubriquesNotifientIndependamment(): void
    {
        $echeanceRhService = $this->createStub(EcheanceRhService::class);
        $echeanceRhService->method('calculerDocuments')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerCartes')->willReturn([
            'enRetard' => [],
            'aVenir' => [['carteId' => 1, 'personnelId' => 1, 'personnel' => 'Fall', 'numero' => 'CP-001', 'echeance' => '2026-10-01', 'jours' => 30]],
        ]);
        $echeanceRhService->method('calculerDecisions')->willReturn([
            'enRetard' => [['decisionId' => 1, 'personnelId' => 2, 'personnel' => 'Ndiaye', 'numeroDecision' => 'DC-042', 'echeance' => '2026-08-20', 'jours' => 10]],
            'aVenir' => [],
        ]);
        $echeanceRhService->method('calculerDelegations')->willReturn(self::VIDE);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->exactly(2))
            ->method('notifierRoles')
            ->with(
                $this->logicalOr([User::ROLE_RH_CARTE_PRO, User::ROLE_RH_RESPONSABLE], [User::ROLE_RH_CONGE, User::ROLE_RH_RESPONSABLE]),
                $this->anything(),
                $this->anything(),
                $this->anything(),
            );

        $tester = $this->tester($echeanceRhService, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDelegationsExpirantesNotifientUniquementLeRhResponsable(): void
    {
        $echeanceRhService = $this->createStub(EcheanceRhService::class);
        $echeanceRhService->method('calculerDocuments')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerCartes')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerDecisions')->willReturn(self::VIDE);
        $echeanceRhService->method('calculerDelegations')->willReturn([
            'enRetard' => [],
            'aVenir' => [['delegationId' => 1, 'delegant' => 'Awa Sow', 'delegataire' => 'Moussa Ba', 'role' => 'RH Congé', 'echeance' => '2026-09-05', 'jours' => 5]],
        ]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_RH_RESPONSABLE],
                $this->stringContains('1 délégation(s)'),
                '/delegations',
                $this->logicalAnd($this->stringContains('Awa Sow'), $this->stringContains('Moussa Ba')),
            );

        $tester = $this->tester($echeanceRhService, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testStructureIncompleteNotifieLeRhResponsable(): void
    {
        $service = new Service();
        $service->setCode('SVC');
        $service->setNom('Service Test');

        $direction = new Direction();
        $direction->setCode('DIR');
        $direction->setNom('Direction Test');

        $serviceRepository = $this->createStub(ServiceRepository::class);
        $serviceRepository->method('findSansResponsable')->willReturn([$service]);

        $directionRepository = $this->createStub(DirectionRepository::class);
        $directionRepository->method('findSansDirecteur')->willReturn([$direction]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_RH_RESPONSABLE],
                $this->stringContains('2 élément(s)'),
                '/dashboard',
                $this->logicalAnd($this->stringContains('Service Test'), $this->stringContains('Direction Test')),
            );

        $tester = $this->tester($this->echeanceRhServiceVide(), $notificationService, $serviceRepository, $directionRepository);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
