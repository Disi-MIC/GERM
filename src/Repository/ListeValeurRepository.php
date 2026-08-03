<?php

namespace App\Repository;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeValeur>
 */
class ListeValeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeValeur::class);
    }

    /**
     * @return ListeValeur[]
     */
    public function findByCategorie(CategorieListeValeur $categorie): array
    {
        return $this->createQueryBuilder('lv')
            ->andWhere('lv.categorie = :cat')
            ->setParameter('cat', $categorie)
            ->orderBy('lv.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
