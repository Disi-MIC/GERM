<?php

namespace App\Repository;

use App\Entity\Maintenance;
use App\Entity\MaterielInformatique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maintenance>
 */
class MaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maintenance::class);
    }

    /**
     * Dernière date de réalisation par matériel, indexée par id de matériel —
     * une requête groupée plutôt qu'une par matériel, pour calculer
     * l'échéance de la prochaine maintenance préventive
     * (DashboardController::informatique()).
     *
     * @return array<int, \DateTimeImmutable>
     */
    public function findDernieresDatesParMateriel(): array
    {
        $lignes = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.materiel) AS materielId', 'MAX(m.dateRealisation) AS derniere')
            ->groupBy('m.materiel')
            ->getQuery()
            ->getResult();

        $dates = [];
        foreach ($lignes as $ligne) {
            $dates[(int) $ligne['materielId']] = new \DateTimeImmutable((string) $ligne['derniere']);
        }

        return $dates;
    }

    /** Nombre de maintenances journalisées pour ce matériel — garde-fou avant suppression (voir MaterielInformatiqueController). */
    public function countPourMateriel(MaterielInformatique $materiel): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.materiel = :materiel')
            ->setParameter('materiel', $materiel)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
