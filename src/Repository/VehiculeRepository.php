<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    /**
     * @return Vehicule[]
     */
    public function search(?string $query): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.service', 's')->addSelect('s')
            ->leftJoin('v.chauffeurAffecte', 'p')->addSelect('p')
            ->orderBy('v.immatriculation', 'ASC');

        if ($query) {
            $qb->andWhere('v.immatriculation LIKE :q OR v.marque LIKE :q OR v.modele LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Véhicules dont l'assurance ou la visite technique expire bientôt (ou déjà expirée).
     *
     * @return Vehicule[]
     */
    public function findEcheancesProches(int $jours = 30): array
    {
        $limite = new \DateTimeImmutable(sprintf('+%d days', $jours));

        return $this->createQueryBuilder('v')
            ->andWhere('(v.assuranceJusquau IS NOT NULL AND v.assuranceJusquau <= :limite) OR (v.visiteTechniqueJusquau IS NOT NULL AND v.visiteTechniqueJusquau <= :limite)')
            ->setParameter('limite', $limite)
            ->orderBy('v.assuranceJusquau', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
