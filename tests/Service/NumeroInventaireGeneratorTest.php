<?php

namespace App\Tests\Service;

use App\Repository\MaterielInformatiqueRepository;
use App\Service\NumeroInventaireGenerator;
use PHPUnit\Framework\TestCase;

class NumeroInventaireGeneratorTest extends TestCase
{
    private function generator(int $dernierNumero): NumeroInventaireGenerator
    {
        $repository = $this->createStub(MaterielInformatiqueRepository::class);
        $repository->method('dernierNumeroSequentiel')->willReturn($dernierNumero);

        return new NumeroInventaireGenerator($repository);
    }

    public function testPremierNumeroQuandAucunMaterielExistant(): void
    {
        self::assertSame('MAT-000001', $this->generator(0)->suivant());
    }

    public function testIncrementeLeDernierNumeroUtiliseSousLePrefixe(): void
    {
        self::assertSame('MAT-000043', $this->generator(42)->suivant());
    }

    public function testConserveLeRembourrageAuDelaDeSixChiffres(): void
    {
        self::assertSame('MAT-1000000', $this->generator(999999)->suivant());
    }

    /**
     * L'import (voir MaterielInformatiqueImporter) appelle suivant() une
     * fois par ligne sans jamais flush entre deux lignes : la base n'est
     * donc interrogée qu'une fois, les appels suivants doivent incrémenter
     * en mémoire plutôt que de renvoyer le même numéro.
     */
    public function testAppelsRepetesIncrementeEnMemoireSansRequeterLaBaseDeNouveau(): void
    {
        $repository = $this->createMock(MaterielInformatiqueRepository::class);
        $repository->expects(self::once())->method('dernierNumeroSequentiel')->willReturn(4);

        $generator = new NumeroInventaireGenerator($repository);

        self::assertSame('MAT-000005', $generator->suivant());
        self::assertSame('MAT-000006', $generator->suivant());
        self::assertSame('MAT-000007', $generator->suivant());
    }
}
