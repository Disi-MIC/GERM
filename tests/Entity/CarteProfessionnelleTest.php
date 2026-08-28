<?php

namespace App\Tests\Entity;

use App\Entity\CarteProfessionnelle;
use App\Entity\Enum\StatutCarteProfessionnelle;
use PHPUnit\Framework\TestCase;

class CarteProfessionnelleTest extends TestCase
{
    /** dateExpiration n'a pas de setter public (toujours dérivée de dateDelivrance) — fixée par réflexion pour un test déterministe. */
    private function carte(StatutCarteProfessionnelle $statut, ?\DateTimeImmutable $dateExpiration): CarteProfessionnelle
    {
        $carte = new CarteProfessionnelle();
        $carte->setStatut($statut);

        $propriete = new \ReflectionProperty(CarteProfessionnelle::class, 'dateExpiration');
        $propriete->setValue($carte, $dateExpiration);

        return $carte;
    }

    public function testTropTotQuandValideEtExpirationLointaine(): void
    {
        $carte = $this->carte(StatutCarteProfessionnelle::VALIDE, new \DateTimeImmutable('+6 months'));

        $this->assertTrue($carte->estTropTotPourRenouvellement());
    }

    public function testPasTropTotQuandExpirationDansLaFenetreDe60Jours(): void
    {
        $carte = $this->carte(StatutCarteProfessionnelle::VALIDE, new \DateTimeImmutable('+30 days'));

        $this->assertFalse($carte->estTropTotPourRenouvellement());
    }

    public function testPasTropTotQuandDejaExpiree(): void
    {
        $carte = $this->carte(StatutCarteProfessionnelle::VALIDE, new \DateTimeImmutable('-10 days'));

        $this->assertFalse($carte->estTropTotPourRenouvellement());
    }

    public function testPasTropTotQuandStatutNonValide(): void
    {
        $carte = $this->carte(StatutCarteProfessionnelle::PERDUE, new \DateTimeImmutable('+6 months'));

        $this->assertFalse($carte->estTropTotPourRenouvellement());
    }

    public function testPasTropTotQuandExpirationInconnue(): void
    {
        $carte = $this->carte(StatutCarteProfessionnelle::VALIDE, null);

        $this->assertFalse($carte->estTropTotPourRenouvellement());
    }
}
