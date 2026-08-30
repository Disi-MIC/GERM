<?php

namespace App\Repository;

use App\Entity\Delegation;
use App\Entity\Enum\StatutDelegation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delegation>
 */
class DelegationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delegation::class);
    }

    /**
     * Délégations encore actives (ni révoquées, ni déjà expirées) — pour le
     * calcul des échéances (voir EcheanceRhService::calculerDelegations()),
     * inutile d'itérer celles déjà closes.
     *
     * @return Delegation[]
     */
    public function findActives(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.delegant', 'dg')->addSelect('dg')
            ->leftJoin('d.delegataire', 'dt')->addSelect('dt')
            ->andWhere('d.statut = :active')
            ->andWhere('d.dateFin >= :aujourdhui')
            ->setParameter('active', StatutDelegation::ACTIVE)
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }
}
