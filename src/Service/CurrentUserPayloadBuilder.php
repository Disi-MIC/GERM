<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DirectionRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Construit le payload "profil utilisateur connecté" partagé par
 * Api/MeController::moi() (/api/me) et Api/ApiSecurityController::login()
 * (/api/login) — les deux doivent renvoyer exactement les mêmes champs : le
 * frontend peuple AuthService.currentUser depuis l'une ou l'autre réponse
 * indifféremment (login() à la connexion, fetchMe() ensuite), et un champ
 * absent de l'une des deux (ex. serviceResponsableId) laisse le signal
 * incomplet jusqu'au prochain fetchMe(), qui peut ne jamais survenir dans la
 * même session (voir AuthService/authGuard : fetchMe() n'est rappelé que si
 * initialized() est encore faux).
 */
class CurrentUserPayloadBuilder
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly DirectionRepository $directionRepository,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function build(User $user): array
    {
        $personnel = $user->getPersonnel();

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $this->roleHierarchy->getReachableRoleNames($user->getRoles()),
            'serviceResponsableId' => $personnel ? $this->serviceRepository->findOneBy(['responsable' => $personnel])?->getId() : null,
            'directionDirigeeId' => $personnel ? $this->directionRepository->findOneBy(['directeur' => $personnel])?->getId() : null,
        ];
    }
}
