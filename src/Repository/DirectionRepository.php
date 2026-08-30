<?php

namespace App\Repository;

use App\Entity\Direction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Direction>
 */
class DirectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Direction::class);
    }

    /**
     * Directions actives sans directeur désigné — même logique que
     * ServiceRepository::findSansResponsable().
     *
     * @return Direction[]
     */
    public function findSansDirecteur(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.directeur IS NULL')
            ->andWhere('d.actif != false')
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
