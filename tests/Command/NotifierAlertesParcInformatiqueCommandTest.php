<?php

namespace App\Tests\Command;

use App\Command\NotifierAlertesParcInformatiqueCommand;
use App\Entity\MaterielInformatique;
use App\Entity\User;
use App\Repository\MaterielInformatiqueRepository;
use App\Service\EcheanceMaintenanceService;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class NotifierAlertesParcInformatiqueCommandTest extends TestCase
{
    private function tester(
        EcheanceMaintenanceService $echeanceMaintenanceService,
        MaterielInformatiqueRepository $materielRepository,
        NotificationService $notificationService,
    ): CommandTester {
        $application = new Application();
        $application->addCommand(new NotifierAlertesParcInformatiqueCommand($echeanceMaintenanceService, $materielRepository, $notificationService));

        return new CommandTester($application->find('app:notifier-alertes-parc-informatique'));
    }

    public function testAucuneAlerteNeNotifiePersonne(): void
    {
        $echeanceMaintenanceService = $this->createStub(EcheanceMaintenanceService::class);
        $echeanceMaintenanceService->method('calculer')->willReturn(['enRetard' => [], 'aVenir' => []]);

        $materielRepository = $this->createStub(MaterielInformatiqueRepository::class);
        $materielRepository->method('findSansNiveauVulnerabilite')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->never())->method('notifierRoles');

        $tester = $this->tester($echeanceMaintenanceService, $materielRepository, $notificationService);
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

        $materielRepository = $this->createStub(MaterielInformatiqueRepository::class);
        $materielRepository->method('findSansNiveauVulnerabilite')->willReturn([]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_IT_STOCK, User::ROLE_IT_RESPONSABLE],
                $this->stringContains('2 maintenance(s), 0 matériel(s) non évalué(s)'),
                '/dashboard-informatique',
                $this->logicalAnd($this->stringContains('INV-1'), $this->stringContains('INV-2')),
            );

        $tester = $this->tester($echeanceMaintenanceService, $materielRepository, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testMaterielNonEvalueDeclencheAussiUneNotification(): void
    {
        $echeanceMaintenanceService = $this->createStub(EcheanceMaintenanceService::class);
        $echeanceMaintenanceService->method('calculer')->willReturn(['enRetard' => [], 'aVenir' => []]);

        $materiel = new MaterielInformatique();
        $materiel->setNumeroInventaire('INV-3');
        $materiel->setMarque('Lenovo');
        $materiel->setModele('ThinkPad');

        $materielRepository = $this->createStub(MaterielInformatiqueRepository::class);
        $materielRepository->method('findSansNiveauVulnerabilite')->willReturn([$materiel]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifierRoles')
            ->with(
                [User::ROLE_IT_STOCK, User::ROLE_IT_RESPONSABLE],
                $this->stringContains('0 maintenance(s), 1 matériel(s) non évalué(s)'),
                '/dashboard-informatique',
                $this->stringContains('INV-3'),
            );

        $tester = $this->tester($echeanceMaintenanceService, $materielRepository, $notificationService);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
