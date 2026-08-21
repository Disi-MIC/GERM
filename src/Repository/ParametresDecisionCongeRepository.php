<?php

namespace App\Repository;

use App\Entity\Enum\CategorieAgentConge;
use App\Entity\ParametresDecisionConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParametresDecisionConge>
 */
class ParametresDecisionCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametresDecisionConge::class);
    }

    /**
     * Une ligne par catégorie, créée aux réglages vides au premier accès
     * plutôt que d'exiger une migration de données à part — les valeurs par
     * défaut sont simplement nulles tant que le RH Admin n'a pas encore
     * renseigné les réglages depuis le backoffice.
     */
    public function recupererOuCreer(CategorieAgentConge $categorie): ParametresDecisionConge
    {
        $parametres = $this->findOneBy(['categorie' => $categorie]);
        if (!$parametres) {
            $parametres = new ParametresDecisionConge($categorie);
            $this->getEntityManager()->persist($parametres);
            $this->getEntityManager()->flush();
        }

        return $parametres;
    }

    /** @return ParametresDecisionConge[] */
    public function recupererTous(): array
    {
        return array_map(
            fn (CategorieAgentConge $categorie) => $this->recupererOuCreer($categorie),
            CategorieAgentConge::cases(),
        );
    }
}
