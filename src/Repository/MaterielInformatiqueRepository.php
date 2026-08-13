<?php

namespace App\Repository;

use App\Entity\LicenceLogiciel;
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
            ->leftJoin('m.type', 't')->addSelect('t')
            ->leftJoin('m.etat', 'e')->addSelect('e')
            ->leftJoin('m.service', 's')->addSelect('s')
            ->leftJoin('m.affecteA', 'p')->addSelect('p')
            ->orderBy('m.numeroInventaire', 'ASC');

        if ($query) {
            $qb->andWhere('m.numeroInventaire LIKE :q OR m.marque LIKE :q OR m.modele LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Matériels avec une périodicité de maintenance préventive définie —
     * ceux à ignorer dans le calcul des échéances (DashboardController).
     *
     * @return MaterielInformatique[]
     */
    public function findAvecPeriodiciteMaintenance(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.periodiciteMois IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de matériels rattachés à une licence donnée (système
     * d'exploitation, suite bureautique ou antivirus — on ne sait pas à
     * l'avance dans lequel des trois champs elle apparaît, d'où le OR) —
     * remplace un compteur "postes couverts" saisi à la main sur
     * LicenceLogiciel, toujours à jour puisque recalculé à chaque lecture.
     * Compte par ligne de licence précise, pas par produit : deux
     * renouvellements du même logiciel (deux LicenceLogiciel distinctes)
     * ont chacun leur propre décompte, reflet de l'association explicite
     * faite à l'installation (voir MaterielInformatique::$systemeExploitation).
     */
    public function countParLicence(LicenceLogiciel $licence): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.systemeExploitation = :licence OR m.suiteBureautique = :licence OR m.antivirus = :licence')
            ->setParameter('licence', $licence)
            ->getQuery()
            ->getSingleScalarResult();
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
