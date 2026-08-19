<?php

namespace App\Repository;

use App\Entity\Direction;
use App\Entity\Personnel;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personnel>
 */
class PersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnel::class);
    }

    /**
     * @return Personnel[]
     */
    public function search(?string $query): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.service', 's')->addSelect('s')
            ->orderBy('p.nom', 'ASC');

        if ($query) {
            $qb->andWhere('p.nom LIKE :q OR p.prenom LIKE :q OR p.matricule LIKE :q OR p.fonction LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Personnel sans compte de connexion lié, disponibles pour être rattachés à
     * un nouveau compte agent — plus, en édition, le personnel actuellement lié
     * (pour qu'il reste sélectionné dans le formulaire).
     *
     * @return Personnel[]
     */
    public function findDisponiblesPourCompte(?Personnel $inclureActuel = null): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.nom', 'ASC');

        if ($inclureActuel) {
            $qb->andWhere('p.user IS NULL OR p = :actuel')->setParameter('actuel', $inclureActuel);
        } else {
            $qb->andWhere('p.user IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Personnel filtré par direction et/ou service et/ou grade, pour les
     * statistiques du tableau de bord (voir aussi ApercuOrganisationController).
     * Si un service est précisé, il prévaut sur la direction.
     *
     * @return Personnel[]
     */
    public function findForStats(?Direction $direction, ?Service $service, ?string $grade = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.service', 's')->addSelect('s')
            ->leftJoin('s.direction', 'd')->addSelect('d');

        if ($service) {
            $qb->andWhere('p.service = :service')->setParameter('service', $service);
        } elseif ($direction) {
            $qb->andWhere('d = :direction')->setParameter('direction', $direction);
        }

        if ($grade) {
            $qb->andWhere('p.grade = :grade')->setParameter('grade', $grade);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Grades distincts renseignés, pour peupler le filtre "hiérarchie" de
     * l'aperçu Ministère (ApercuOrganisationController) — Personnel::$grade
     * est un champ texte libre, pas une liste de valeurs.
     *
     * @return string[]
     */
    public function findDistinctGrades(): array
    {
        $resultats = $this->createQueryBuilder('p')
            ->select('DISTINCT p.grade')
            ->andWhere('p.grade IS NOT NULL')
            ->orderBy('p.grade', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($resultats, 'grade');
    }
}
