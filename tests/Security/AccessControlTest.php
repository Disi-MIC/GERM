<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Matrice de tests de sécurité du back-office Twig et de l'état des comptes.
 *
 * Verrouille des correctifs dont la régression serait silencieuse : rien dans
 * le comportement visible de l'application ne signalerait qu'un compte
 * désactivé a retrouvé l'accès, ou que la barrière ^/admin est retombée à
 * ROLE_AGENT.
 */
class AccessControlTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    /**
     * Ces tests ont besoin d'une base de test peuplée. Tant qu'elle n'existe
     * pas, ils se désactivent d'eux-mêmes plutôt que d'échouer : un test rouge
     * en permanence finit par être ignoré, et masque les vraies régressions.
     *
     * Pour les activer :
     *   mysql -u root -e "CREATE DATABASE germ_db_test;
     *     GRANT ALL ON germ_db_test.* TO 'germ_user'@'127.0.0.1';"
     *   php bin/console doctrine:schema:create --env=test
     *   (puis charger des comptes de test : voir doctrine/doctrine-fixtures-bundle,
     *    déjà présent dans composer.json)
     */
    private function connecter(string $email): User
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de test indisponible : '.$e->getMessage());
        }

        if (null === $user) {
            self::markTestSkipped(\sprintf('Compte de test "%s" absent de la base de test.', $email));
        }
        $this->client->loginUser($user);

        return $user;
    }

    /**
     * GERM-013 — la page d'atterrissage doit rester joignable par tout agent
     * connecté, malgré le passage de la règle ^/admin à ROLE_SUPERADMIN. Elle
     * est l'exception explicite placée avant la règle générale : si l'ordre
     * des règles d'access_control était inversé, un agent sans rôle métier
     * n'aurait plus aucune page de repli après connexion.
     */
    public function testPageAucunAccesResteOuverteAUnAgentSansRole(): void
    {
        $this->connecter('test.agent@germ.local');
        $this->client->request('GET', '/admin/aucun-acces');

        self::assertResponseIsSuccessful();
    }

    /**
     * GERM-013 — un compte authentifié mais non superadmin ne doit atteindre
     * aucune page du back-office.
     */
    #[DataProvider('urlsBackOffice')]
    public function testBackOfficeRefuseUnCompteNonSuperadmin(string $url): void
    {
        $this->connecter('test.adminrh@germ.local');
        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public static function urlsBackOffice(): iterable
    {
        yield 'tableau de bord' => ['/admin'];
        yield 'personnel' => ['/admin/personnel'];
        yield 'comptes utilisateurs' => ['/admin/agent'];
        yield 'véhicules' => ['/admin/vehicule'];
    }

    /** GERM-013 — non-régression : le superadmin conserve son accès. */
    public function testBackOfficeResteAccessibleAuSuperadmin(): void
    {
        $this->connecter('superadmin@industriecommerce.gouv.sn');
        $this->client->request('GET', '/admin/personnel');

        self::assertResponseIsSuccessful();
    }

    /** La page de connexion reste publique. */
    public function testLoginRestePublic(): void
    {
        $this->client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
    }

    /**
     * GERM-022 — la documentation interactive de l'API (mapping complet des
     * ressources/opérations/champs) ne doit pas être accessible à un compte
     * authentifié sans rôle superadmin, même si chaque opération reste par
     * ailleurs protégée individuellement.
     */
    public function testDocumentationApiRefuseUnCompteNonSuperadmin(): void
    {
        $this->connecter('test.agent@germ.local');
        $this->client->request('GET', '/api/docs');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /** GERM-022 — non-régression : le superadmin conserve l'accès à la documentation. */
    public function testDocumentationApiResteAccessibleAuSuperadmin(): void
    {
        $this->connecter('superadmin@industriecommerce.gouv.sn');
        $this->client->request('GET', '/api/docs');

        self::assertResponseIsSuccessful();
    }
}
