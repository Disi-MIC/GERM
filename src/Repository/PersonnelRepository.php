<?php

namespace App\Repository;

use App\Entity\Direction;
use App\Entity\Personnel;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personnel>
 */
class PersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnel::class);
    }

    /**
     * @return Personnel[]
     */
    public function search(?string $query): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.service', 's')->addSelect('s')
            ->orderBy('p.nom', 'ASC');

        if ($query) {
            $qb->andWhere('p.nom LIKE :q OR p.prenom LIKE :q OR p.matricule LIKE :q OR p.fonction LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Personnel filtré par direction et/ou service, pour les statistiques du tableau de bord.
     * Si un service est précisé, il prévaut sur la direction.
     *
     * @return Personnel[]
     */
    public function findForStats(?Direction $direction, ?Service $service): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.service', 's')->addSelect('s')
            ->leftJoin('s.direction', 'd')->addSelect('d');

        if ($service) {
            $qb->andWhere('p.service = :service')->setParameter('service', $service);
        } elseif ($direction) {
            $qb->andWhere('d = :direction')->setParameter('direction', $direction);
        }

        return $qb->getQuery()->getResult();
    }
}
