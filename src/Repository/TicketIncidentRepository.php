<?php

namespace App\Repository;

use App\Entity\MaterielInformatique;
use App\Entity\TicketIncident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketIncident>
 */
class TicketIncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketIncident::class);
    }

    /** Nombre de tickets d'incident pour ce matériel — garde-fou avant suppression (voir MaterielInformatiqueController). */
    public function countPourMateriel(MaterielInformatique $materiel): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.materiel = :materiel')
            ->setParameter('materiel', $materiel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
