<?php

namespace App\Repository;

use App\Entity\Personnel;
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
}
