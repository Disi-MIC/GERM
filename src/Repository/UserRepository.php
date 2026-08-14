<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trié par le nom affiché : celui de la fiche agent liée si le compte y
     * est rattaché, sinon le nom propre du compte (voir User::getNom()).
     * Un simple ORDER BY u.nom ne suffit plus depuis que ce champ n'est
     * renseigné que pour les comptes sans fiche liée.
     *
     * @return User[]
     */
    public function findTriesParNomAffiche(): array
    {
        // COALESCE doit passer par un select HIDDEN pour être utilisable dans
        // l'ORDER BY : cette version de Doctrine ne l'accepte pas telle
        // quelle directement en orderBy() (QueryException "Expected known
        // function, got COALESCE").
        return $this->createQueryBuilder('u')
            ->leftJoin('u.personnel', 'p')->addSelect('p')
            ->addSelect('COALESCE(p.nom, u.nom) AS HIDDEN nomTri')
            ->addSelect('COALESCE(p.prenom, u.prenom) AS HIDDEN prenomTri')
            ->orderBy('nomTri', 'ASC')
            ->addOrderBy('prenomTri', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
