<?php

namespace App\Repository;

use App\Entity\Enum\CategorieAgentConge;
use App\Entity\ParametreEligibiliteConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParametreEligibiliteConge>
 */
class ParametreEligibiliteCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametreEligibiliteConge::class);
    }

    /**
     * Une ligne par catégorie, créée aux valeurs par défaut au premier accès
     * plutôt que par une migration de données à part — voir le commentaire
     * de classe de ParametreEligibiliteConge.
     */
    public function recupererOuCreer(CategorieAgentConge $categorie): ParametreEligibiliteConge
    {
        $parametres = $this->findOneBy(['categorie' => $categorie]);
        if (!$parametres) {
            $parametres = new ParametreEligibiliteConge($categorie);
            $this->getEntityManager()->persist($parametres);
            $this->getEntityManager()->flush();
        }

        return $parametres;
    }

    /** @return ParametreEligibiliteConge[] */
    public function recupererTous(): array
    {
        return array_map(
            fn (CategorieAgentConge $categorie) => $this->recupererOuCreer($categorie),
            CategorieAgentConge::cases(),
        );
    }
}
