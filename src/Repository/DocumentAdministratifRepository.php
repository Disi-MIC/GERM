<?php

namespace App\Repository;

use App\Entity\DocumentAdministratif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentAdministratif>
 */
class DocumentAdministratifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentAdministratif::class);
    }

    /**
     * Documents avec une date d'expiration renseignée — la plupart n'en ont
     * pas (un CV ou une CNI n'expire pas nécessairement), inutile de les
     * itérer dans le calcul des échéances (voir EcheanceRhService).
     *
     * @return DocumentAdministratif[]
     */
    public function findAvecExpiration(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.personnel', 'p')->addSelect('p')
            ->andWhere('d.dateExpiration IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
