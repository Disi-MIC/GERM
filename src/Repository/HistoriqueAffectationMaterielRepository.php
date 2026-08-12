<?php

namespace App\Repository;

use App\Entity\HistoriqueAffectationMateriel;
use App\Entity\MaterielInformatique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueAffectationMateriel>
 */
class HistoriqueAffectationMaterielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueAffectationMateriel::class);
    }

    /** L'entrée en cours (sans date de fin) pour ce matériel, s'il y en a une. */
    public function findEnCoursPourMateriel(MaterielInformatique $materiel): ?HistoriqueAffectationMateriel
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.materiel = :materiel')
            ->andWhere('h.dateFinAffectation IS NULL')
            ->setParameter('materiel', $materiel)
            ->orderBy('h.dateAffectation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Nombre d'entrées d'historique pour ce matériel — garde-fou avant suppression (voir MaterielInformatiqueController). */
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
