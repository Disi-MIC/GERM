<?php

namespace App\Repository;

use App\Entity\BonEssence;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonEssence>
 */
class BonEssenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonEssence::class);
    }

    /** Nombre de bons d'essence journalisés pour ce véhicule — garde-fou avant suppression (voir VehiculeController). */
    public function countPourVehicule(Vehicule $vehicule): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.vehicule = :vehicule')
            ->setParameter('vehicule', $vehicule)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
