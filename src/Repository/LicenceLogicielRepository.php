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
     * Licence "en cours" par logiciel — celle avec la date d'expiration la
     * plus lointaine, pour ignorer les renouvellements passés lors du calcul
     * des échéances (DashboardController::informatique()). Un logiciel sans
     * aucune date d'expiration renseignée sur ses licences n'est pas retenu.
     *
     * @return LicenceLogiciel[]
     */
    public function findDernieresParLogiciel(): array
    {
        $licences = $this->createQueryBuilder('l')
            ->andWhere('l.dateExpiration IS NOT NULL')
            ->orderBy('l.dateExpiration', 'DESC')
            ->getQuery()
            ->getResult();

        $dernieres = [];
        foreach ($licences as $licence) {
            $logicielId = $licence->getLogiciel()?->getId();
            if (null !== $logicielId && !isset($dernieres[$logicielId])) {
                $dernieres[$logicielId] = $licence;
            }
        }

        return array_values($dernieres);
    }
}
