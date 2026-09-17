<?php

namespace App\Tests\Security;

use App\Entity\CarteProfessionnelle;
use App\Entity\DecisionConge;
use App\Entity\DemandeCartePro;
use App\Entity\DemandeDecision;
use App\Entity\DemandeJouissance;
use App\Entity\DocumentAdministratif;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\User;
use App\Entity\Vehicule;
use App\Security\PersonnelOwnershipVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * GERM-020 : verrouille le comportement du Voter qui centralise désormais
 * les contrôles d'appartenance jusqu'ici dupliqués dans MeController et
 * MeDemandesController — voir le docblock de la classe pour la liste des
 * types couverts et ceux volontairement exclus.
 */
class PersonnelOwnershipVoterTest extends TestCase
{
    private function jeton(?Personnel $personnel): TokenInterface
    {
        $user = $this->createStub(User::class);
        $user->method('getPersonnel')->willReturn($personnel);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public static function entitesCouvertes(): iterable
    {
        yield CarteProfessionnelle::class => [static function (Personnel $p) {
            $carte = new CarteProfessionnelle();
            $carte->setPersonnel($p);

            return $carte;
        }];
        yield DocumentAdministratif::class => [static function (Personnel $p) {
            $document = new DocumentAdministratif();
            $document->setPersonnel($p);

            return $document;
        }];
        yield DemandeCartePro::class => [static function (Personnel $p) {
            $demande = new DemandeCartePro();
            $demande->setPersonnel($p);

            return $demande;
        }];
        yield DemandeDecision::class => [static function (Personnel $p) {
            $demande = new DemandeDecision();
            $demande->setPersonnel($p);

            return $demande;
        }];
        yield DemandeJouissance::class => [static function (Personnel $p) {
            $demande = new DemandeJouissance();
            $demande->setPersonnel($p);

            return $demande;
        }];
        yield DecisionConge::class => [static function (Personnel $p) {
            $decision = new DecisionConge();
            $decision->setPersonnel($p);

            return $decision;
        }];
        yield MaterielInformatique::class => [static function (Personnel $p) {
            $materiel = new MaterielInformatique();
            $materiel->setAffecteA($p);

            return $materiel;
        }];
    }

    #[DataProvider('entitesCouvertes')]
    public function testAccordeLaccesAuProprietaire(callable $fabrique): void
    {
        $personnel = new Personnel();
        $entite = $fabrique($personnel);

        $voter = new PersonnelOwnershipVoter();
        $decision = $voter->vote($this->jeton($personnel), $entite, [PersonnelOwnershipVoter::EST_PROPRIETAIRE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $decision);
    }

    #[DataProvider('entitesCouvertes')]
    public function testRefuseLaccesAUnTiers(callable $fabrique): void
    {
        $proprietaire = new Personnel();
        $tiers = new Personnel();
        $entite = $fabrique($proprietaire);

        $voter = new PersonnelOwnershipVoter();
        $decision = $voter->vote($this->jeton($tiers), $entite, [PersonnelOwnershipVoter::EST_PROPRIETAIRE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testRefuseUnPersonnelSansCompteLie(): void
    {
        $carte = new CarteProfessionnelle();
        $carte->setPersonnel(new Personnel());

        $voter = new PersonnelOwnershipVoter();
        $decision = $voter->vote($this->jeton(null), $carte, [PersonnelOwnershipVoter::EST_PROPRIETAIRE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testAbstientPourUneEntiteNonCouverte(): void
    {
        $voter = new PersonnelOwnershipVoter();
        $decision = $voter->vote($this->jeton(new Personnel()), new Vehicule(), [PersonnelOwnershipVoter::EST_PROPRIETAIRE]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }
}
