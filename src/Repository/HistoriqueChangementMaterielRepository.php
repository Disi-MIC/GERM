<?php

namespace App\Repository;

use App\Entity\HistoriqueChangementMateriel;
use App\Entity\MaterielInformatique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueChangementMateriel>
 */
class HistoriqueChangementMaterielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueChangementMateriel::class);
    }

    /** Utilisé par MaterielInformatiqueController::delete() pour bloquer la suppression d'un matériel encore historisé. */
    public function countPourMateriel(MaterielInformatique $materiel): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.materiel = :materiel')
            ->setParameter('materiel', $materiel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
