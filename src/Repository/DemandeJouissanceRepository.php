<?php

namespace App\Repository;

use App\Entity\DemandeJouissance;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeConge;
use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeJouissance>
 */
class DemandeJouissanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeJouissance::class);
    }

    /**
     * @return DemandeJouissance[]
     */
    public function search(
        ?Personnel $personnel,
        ?TypeConge $type,
        ?StatutDemande $statut,
        ?\DateTimeImmutable $dateDebut,
        ?\DateTimeImmutable $dateFin,
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.personnel', 'p')->addSelect('p')
            ->orderBy('d.createdAt', 'DESC');

        if ($personnel) {
            $qb->andWhere('d.personnel = :personnel')->setParameter('personnel', $personnel);
        }
        if ($type) {
            $qb->andWhere('d.type = :type')->setParameter('type', $type);
        }
        if ($statut) {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $statut);
        }
        if ($dateDebut) {
            $qb->andWhere('d.dateFin >= :dateDebut')->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('d.dateDebut <= :dateFin')->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }
}
