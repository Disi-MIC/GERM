<?php

namespace App\Repository;

use App\Entity\MaterielInformatique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaterielInformatique>
 */
class MaterielInformatiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterielInformatique::class);
    }

    /**
     * @return MaterielInformatique[]
     */
    public function search(?string $query): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.service', 's')->addSelect('s')
            ->leftJoin('m.affecteA', 'p')->addSelect('p')
            ->orderBy('m.numeroInventaire', 'ASC');

        if ($query) {
            $qb->andWhere('m.numeroInventaire LIKE :q OR m.marque LIKE :q OR m.modele LIKE :q OR m.numeroSerie LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Matériels dont la garantie expire dans les 30 prochains jours (ou déjà expirée).
     *
     * @return MaterielInformatique[]
     */
    public function findGarantiesExpirantBientot(int $jours = 30): array
    {
        $limite = new \DateTimeImmutable(sprintf('+%d days', $jours));

        return $this->createQueryBuilder('m')
            ->andWhere('m.garantieJusquau IS NOT NULL')
            ->andWhere('m.garantieJusquau <= :limite')
            ->setParameter('limite', $limite)
            ->orderBy('m.garantieJusquau', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
