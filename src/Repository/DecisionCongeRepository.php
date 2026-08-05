<?php

namespace App\Repository;

use App\Entity\DecisionConge;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DecisionConge>
 */
class DecisionCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DecisionConge::class);
    }

    /**
     * Décisions non expirées, éventuellement filtrées sur un agent. Utilisé pour
     * peupler le choix de décision d'une demande de jouissance de congé annuel.
     *
     * @return DecisionConge[]
     */
    public function findValides(?Personnel $personnel = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.personnel', 'p')->addSelect('p')
            ->andWhere('d.dateExpiration >= :aujourdhui')
            ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
            ->orderBy('d.dateExpiration', 'ASC');

        if ($personnel) {
            $qb->andWhere('d.personnel = :personnel')->setParameter('personnel', $personnel);
        }

        return $qb->getQuery()->getResult();
    }
}
