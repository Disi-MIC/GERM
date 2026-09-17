<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes minimaux requis par tests/Security/AccessControlTest.php (GERM-014,
 * GERM-013) : un agent sans rôle métier, un compte avec un rôle métier mais
 * pas superadmin, et un superadmin. Volontairement limité à ces trois
 * comptes — cette fixture sert la matrice de sécurité, pas une base de
 * démonstration complète.
 */
class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->utilisateur('test.agent@germ.local', []));
        $manager->persist($this->utilisateur('test.adminrh@germ.local', [User::ROLE_RH_RESPONSABLE]));
        $manager->persist($this->utilisateur('superadmin@industriecommerce.gouv.sn', [User::ROLE_SUPERADMIN]));

        $manager->flush();
    }

    private function utilisateur(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setActif(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'mot-de-passe-de-test-ci'));

        return $user;
    }
}
