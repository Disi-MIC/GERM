<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Notifications non lues d'un compte, les plus récentes en premier — sert à
     * la fois au badge de la cloche et aux badges des rubriques du menu (comptés
     * côté frontend par préfixe de route sur `lien`).
     *
     * @return Notification[]
     */
    public function findNonLues(User $destinataire): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :destinataire')
            ->andWhere('n.lu = false')
            ->setParameter('destinataire', $destinataire)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Historique récent (lues et non lues) affiché dans le menu déroulant de la cloche.
     *
     * @return Notification[]
     */
    public function findRecentes(User $destinataire, int $limite = 30): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :destinataire')
            ->setParameter('destinataire', $destinataire)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
