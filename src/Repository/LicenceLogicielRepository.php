<?php

namespace App\Repository;

use App\Entity\LicenceLogiciel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicenceLogiciel>
 */
class LicenceLogicielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenceLogiciel::class);
    }

    /**
     * Licences avec une date d'expiration renseignée, triées par échéance —
     * chaque ligne de licence est désormais rattachée explicitement à du
     * matériel (voir MaterielInformatique::$systemeExploitation et
     * MaterielInformatiqueRepository::countParLicence()), donc plus de notion
     * de licence "en cours" par logiciel à isoler : un ancien renouvellement
     * reste pertinent tant que du matériel y pointe encore. C'est
     * DashboardController::calculerEcheancesLicences() qui écarte les lignes
     * à 0 poste (renouvellement abandonné, jamais rattaché à du matériel).
     *
     * @return LicenceLogiciel[]
     */
    public function findAvecExpiration(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.dateExpiration IS NOT NULL')
            ->orderBy('l.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
