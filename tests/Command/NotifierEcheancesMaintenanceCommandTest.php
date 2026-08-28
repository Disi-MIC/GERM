<?php

namespace App\Tests\Command;

use App\Command\NotifierEcheancesMaintenanceCommand;
use App\Entity\User;
use App\Service\EcheanceMaintenanceService;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class NotifierEcheancesMaintenanceCommandTest extends TestCase
{
    private function tester(EcheanceMaintenanceService $echeanceMaintenanceService, NotificationService $notificationService): CommandTester
    {
        $application = new Application();
        $application->addCommand(new NotifierEcheancesMaintenanceCommand($echeanceMaintenanceService, $notificationService));

        return new CommandTester($application->find('app:notifier-echeances-maintenance'));
    }

    public function testAucuneEcheanceNeNotifiePersonne(): void
    {
        $echeanceMaintenanceService = $this->createStub(EcheanceMaintenanceService::class);
        $echeanceMaintenanceService->method('calculer')->willReturn(['enRetard' => [], 'aVenir' => []]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())->method('notifierRoles');

        $tester = $this->tester($echeanceMaintenanceService, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testEcheancesNotifieUneSeuleFoisLesRolesItStockEtResponsable(): void
    {
        $echeanceMaintenanceService = $this->createStub(EcheanceMaintenanceService::class);
        $echeanceMaintenanceService->method('calculer')->willReturn([
            'enRetard' => [['materielId' => 1, 'numeroInventaire' => 'INV-1', 'marque' => 'Dell', 'modele' => 'OptiPlex', 'echeance' => '2026-08-01', 'jours' => 5]],
            'aVenir' => [['materielId' => 2, 'numeroInventaire' => 'INV-2', 'marque' => 'HP', 'modele' => 'LaserJet', 'echeance' => '2026-09-01', 'jours' => 3]],
        ]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_IT_STOCK, User::ROLE_IT_RESPONSABLE],
                $this->stringContains('1 en retard, 1 à venir'),
                '/dashboard-informatique',
                $this->logicalAnd($this->stringContains('INV-1'), $this->stringContains('INV-2')),
            );

        $tester = $this->tester($echeanceMaintenanceService, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
