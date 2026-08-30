<?php

namespace App\Repository;

use App\Entity\HistoriqueChangementPersonnel;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueChangementPersonnel>
 */
class HistoriqueChangementPersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueChangementPersonnel::class);
    }

    /** Utilisé par PersonnelController::delete() pour bloquer la suppression d'une fiche encore historisée. */
    public function countPourPersonnel(Personnel $personnel): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.personnel = :personnel')
            ->setParameter('personnel', $personnel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
