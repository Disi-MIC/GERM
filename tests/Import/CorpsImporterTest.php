<?php

namespace App\Tests\Import;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Import\CorpsImporter;
use App\Import\ImportRowStatus;
use App\Repository\ListeValeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Représentatif des 4 importeurs de listes paramétrables (Corps, Hiérarchie,
 * Programme, Chapitre) : tous partagent AbstractListeValeurImporter, verrouillé
 * ici une seule fois plutôt que dupliqué dans un test par sous-classe.
 */
class CorpsImporterTest extends TestCase
{
    private function importeur(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        ListeValeurRepository $repository,
    ): CorpsImporter {
        return new CorpsImporter($em, $validator, $repository);
    }

    public function testSupporteLeTypeCorpsEtSaCategorie(): void
    {
        $importeur = $this->importeur(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(ValidatorInterface::class),
            $this->createStub(ListeValeurRepository::class),
        );

        self::assertSame(\App\Import\TypeImport::CORPS, $importeur->getSupportedType());
    }

    public function testLigneValideEstCreee(): void
    {
        $repository = $this->createStub(ListeValeurRepository::class);
        $repository->method('findOneByCategorieAndCode')->willReturn(null);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(
            fn (ListeValeur $v) => CategorieListeValeur::CORPS === $v->getCategorie()
                && 'administrateurs_civils' === $v->getCode()
                && 'Administrateurs civils' === $v->getLibelle()
                && true === $v->isActif(),
        ));

        $importeur = $this->importeur($em, $validator, $repository);
        $resultat = $importeur->importRow(['code' => 'administrateurs_civils', 'libelle' => 'Administrateurs civils', 'actif' => 'oui'], 2);

        self::assertSame(ImportRowStatus::CREATED, $resultat->status);
    }

    public function testCodeManquantEstUneErreurSansPersistance(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $importeur = $this->importeur(
            $em,
            $this->createStub(ValidatorInterface::class),
            $this->createStub(ListeValeurRepository::class),
        );

        $resultat = $importeur->importRow(['code' => '', 'libelle' => 'Administrateurs civils'], 2);

        self::assertSame(ImportRowStatus::ERROR, $resultat->status);
    }

    public function testLibelleManquantEstUneErreurSansPersistance(): void
    {
        $repository = $this->createStub(ListeValeurRepository::class);
        $repository->method('findOneByCategorieAndCode')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $importeur = $this->importeur($em, $this->createStub(ValidatorInterface::class), $repository);

        $resultat = $importeur->importRow(['code' => 'administrateurs_civils', 'libelle' => ''], 2);

        self::assertSame(ImportRowStatus::ERROR, $resultat->status);
    }

    public function testCodeDejaExistantDansLaCategorieEstIgnore(): void
    {
        $repository = $this->createStub(ListeValeurRepository::class);
        $repository->method('findOneByCategorieAndCode')->willReturn(new ListeValeur());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $importeur = $this->importeur($em, $this->createStub(ValidatorInterface::class), $repository);

        $resultat = $importeur->importRow(['code' => 'administrateurs_civils', 'libelle' => 'Administrateurs civils'], 2);

        self::assertSame(ImportRowStatus::SKIPPED_EXISTING, $resultat->status);
    }

    public function testActifNonRenseigneVautTrueParDefaut(): void
    {
        $repository = $this->createStub(ListeValeurRepository::class);
        $repository->method('findOneByCategorieAndCode')->willReturn(null);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(fn (ListeValeur $v) => true === $v->isActif()));

        $importeur = $this->importeur($em, $validator, $repository);
        $importeur->importRow(['code' => 'administrateurs_civils', 'libelle' => 'Administrateurs civils'], 2);
    }
}
