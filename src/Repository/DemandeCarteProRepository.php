<?php

namespace App\Repository;

use App\Entity\DemandeCartePro;
use App\Entity\Enum\StatutDemandeCartePro;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeCartePro>
 */
class DemandeCarteProRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeCartePro::class);
    }

    /**
     * @return DemandeCartePro[]
     */
    public function search(?Personnel $personnel, ?StatutDemandeCartePro $statut): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.personnel', 'p')->addSelect('p')
            ->orderBy('d.createdAt', 'DESC');

        if ($personnel) {
            $qb->andWhere('d.personnel = :personnel')->setParameter('personnel', $personnel);
        }
        if ($statut) {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }
}
