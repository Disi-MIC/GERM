<?php

namespace App\Repository;

use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Personnel;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueAffectation>
 */
class HistoriqueAffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueAffectation::class);
    }

    public function findCurrentForPersonnel(Personnel $personnel): ?HistoriqueAffectation
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.personnel = :personnel')
            ->setParameter('personnel', $personnel)
            ->orderBy('h.dateEffet', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return HistoriqueAffectation[]
     */
    public function search(
        ?Service $service,
        ?TypeMouvementCarriere $type,
        ?\DateTimeImmutable $dateDebut,
        ?\DateTimeImmutable $dateFin,
    ): array {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.personnel', 'p')->addSelect('p')
            ->leftJoin('h.service', 's')->addSelect('s')
            ->orderBy('h.dateEffet', 'DESC')
            ->addOrderBy('h.id', 'DESC');

        if ($service) {
            $qb->andWhere('h.service = :service')->setParameter('service', $service);
        }
        if ($type) {
            $qb->andWhere('h.typeMouvement = :type')->setParameter('type', $type);
        }
        if ($dateDebut) {
            $qb->andWhere('h.dateEffet >= :dateDebut')->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('h.dateEffet <= :dateFin')->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }
}
