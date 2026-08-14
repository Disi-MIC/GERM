<?php

namespace App\Repository;

use App\Entity\LienGoogleSheet;
use App\Import\TypeImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LienGoogleSheet>
 */
class LienGoogleSheetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LienGoogleSheet::class);
    }

    public function findOneByType(TypeImport $type): ?LienGoogleSheet
    {
        return $this->find($type);
    }
}
