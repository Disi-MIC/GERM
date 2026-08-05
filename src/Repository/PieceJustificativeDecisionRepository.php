<?php

namespace App\Repository;

use App\Entity\PieceJustificativeDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PieceJustificativeDecision>
 */
class PieceJustificativeDecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PieceJustificativeDecision::class);
    }
}
