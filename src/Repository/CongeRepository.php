<?php

namespace App\Repository;

use App\Entity\Conge;
use App\Entity\Enum\TypeConge;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conge>
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    /**
     * @return Conge[]
     */
    public function search(
        ?Personnel $personnel,
        ?TypeConge $type,
        ?\DateTimeImmutable $dateDebut,
        ?\DateTimeImmutable $dateFin,
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.personnel', 'p')->addSelect('p')
            ->orderBy('c.dateDebut', 'DESC');

        if ($personnel) {
            $qb->andWhere('c.personnel = :personnel')->setParameter('personnel', $personnel);
        }
        if ($type) {
            $qb->andWhere('c.type = :type')->setParameter('type', $type);
        }
        if ($dateDebut) {
            $qb->andWhere('c.dateFin >= :dateDebut')->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.dateDebut <= :dateFin')->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }
}
