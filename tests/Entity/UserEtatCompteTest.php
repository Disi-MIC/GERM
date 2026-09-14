<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Verrouille les deux garde-fous liés à l'état d'un compte (GERM-001 et
 * GERM-004). Leur régression serait invisible à l'usage : l'application
 * continuerait de fonctionner normalement, en laissant simplement un compte
 * révoqué garder ses accès.
 */
class UserEtatCompteTest extends TestCase
{
    private function compte(bool $actif, string $motDePasse = 'hash', string $email = 'agent@germ.local'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($motDePasse);
        $user->setActif($actif);

        return $user;
    }

    /** GERM-001 — défense en profondeur : un compte désactivé ne porte plus aucun rôle. */
    public function testCompteDesactiveNaPlusAucunRole(): void
    {
        self::assertSame([], $this->compte(actif: false)->getRoles());
    }

    /** Non-régression : un compte actif conserve au minimum ROLE_AGENT. */
    public function testCompteActifConserveRoleAgent(): void
    {
        self::assertContains(User::ROLE_AGENT, $this->compte(actif: true)->getRoles());
    }

    /** GERM-004 — désactiver un compte invalide les sessions déjà ouvertes. */
    public function testChangementDeStatutRendLesSessionsInvalides(): void
    {
        self::assertFalse($this->compte(actif: true)->isEqualTo($this->compte(actif: false)));
    }

    /**
     * GERM-004, non-régression critique : implémenter EquatableInterface fait
     * que Symfony s'appuie EXCLUSIVEMENT sur isEqualTo() et abandonne ses
     * propres comparaisons. Ce test garantit que le changement de mot de passe
     * continue d'invalider les sessions — protection native que le correctif
     * aurait silencieusement supprimée si isEqualTo() ne comparait que `actif`.
     */
    public function testChangementDeMotDePasseRendLesSessionsInvalides(): void
    {
        self::assertFalse(
            $this->compte(actif: true, motDePasse: 'ancien')
                ->isEqualTo($this->compte(actif: true, motDePasse: 'nouveau')),
        );
    }

    /** GERM-004, non-régression : idem pour un changement d'identifiant. */
    public function testChangementDIdentifiantRendLesSessionsInvalides(): void
    {
        self::assertFalse(
            $this->compte(actif: true, email: 'avant@germ.local')
                ->isEqualTo($this->compte(actif: true, email: 'apres@germ.local')),
        );
    }

    /** Non-régression : une session inchangée reste valide (pas de déconnexion intempestive). */
    public function testCompteInchangeResteValide(): void
    {
        self::assertTrue($this->compte(actif: true)->isEqualTo($this->compte(actif: true)));
    }
}
