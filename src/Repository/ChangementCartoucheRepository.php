<?php

namespace App\Repository;

use App\Entity\ChangementCartouche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChangementCartouche>
 */
class ChangementCartoucheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangementCartouche::class);
    }
}
