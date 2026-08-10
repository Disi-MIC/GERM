<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Émission des notifications internes (cloche + badges du menu). `notifierRole`
 * s'appuie sur User::getRoles() — qui fusionne déjà les délégations actives —
 * donc un délégataire temporaire d'un rôle RH reçoit aussi les notifications
 * de ce rôle sans traitement particulier ici.
 */
class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function notifier(?User $destinataire, string $titre, ?string $lien = null, ?string $message = null): void
    {
        if (!$destinataire) {
            return;
        }

        $notification = new Notification();
        $notification->setDestinataire($destinataire);
        $notification->setTitre($titre);
        $notification->setMessage($message);
        $notification->setLien($lien);
        $this->em->persist($notification);
        $this->em->flush();
    }

    /**
     * Notifie tous les comptes actifs possédant (littéralement ou par délégation
     * active) le rôle donné.
     */
    public function notifierRole(string $role, string $titre, ?string $lien = null, ?string $message = null): void
    {
        foreach ($this->userRepository->findBy(['actif' => true]) as $user) {
            if (\in_array($role, $user->getRoles(), true)) {
                $notification = new Notification();
                $notification->setDestinataire($user);
                $notification->setTitre($titre);
                $notification->setMessage($message);
                $notification->setLien($lien);
                $this->em->persist($notification);
            }
        }
        $this->em->flush();
    }
}
