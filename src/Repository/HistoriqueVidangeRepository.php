<?php

namespace App\Repository;

use App\Entity\HistoriqueVidange;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueVidange>
 */
class HistoriqueVidangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueVidange::class);
    }

    /**
     * Vidange la plus récente pour ce véhicule — sert à maintenir
     * Vehicule::$derniereVidangeKm/Date après une création ou une suppression
     * (voir Api/HistoriqueVidangeController).
     */
    public function findDerniereVidange(Vehicule $vehicule): ?HistoriqueVidange
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.vehicule = :vehicule')
            ->setParameter('vehicule', $vehicule)
            ->orderBy('v.date', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Nombre de vidanges journalisées pour ce véhicule — garde-fou avant suppression (voir VehiculeController). */
    public function countPourVehicule(Vehicule $vehicule): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.vehicule = :vehicule')
            ->setParameter('vehicule', $vehicule)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
