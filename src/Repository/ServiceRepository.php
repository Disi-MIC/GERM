<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * Services actifs sans responsable désigné — signalé au tableau de bord
     * Personnel comme un défaut de complétude de l'organigramme (voir
     * Service::$responsable). Un service inactif (archivé) n'a plus vocation
     * à être piloté, volontairement exclu.
     *
     * @return Service[]
     */
    public function findSansResponsable(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.direction', 'd')->addSelect('d')
            ->andWhere('s.responsable IS NULL')
            ->andWhere('s.actif != false')
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
