<?php

namespace App\Repository;

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
     * Singleton (id=1 fixe) : crée la ligne au premier accès plutôt que
     * d'exiger une migration de données à part — les valeurs par défaut sont
     * simplement nulles tant que le RH Admin n'a pas encore renseigné les
     * réglages depuis le backoffice.
     */
    public function recupererOuCreer(): ParametresDecisionConge
    {
        $parametres = $this->find(1);
        if (!$parametres) {
            $parametres = new ParametresDecisionConge();
            $this->getEntityManager()->persist($parametres);
            $this->getEntityManager()->flush();
        }

        return $parametres;
    }
}
