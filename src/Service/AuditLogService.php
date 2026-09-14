<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Enum\ActionAudit;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Point d'écriture unique du journal d'audit (voir AuditLog). Centraliser ici
 * la lecture de la requête courante (IP, user-agent) et de l'utilisateur
 * connecté évite de dupliquer cette logique — et le risque d'oubli — à
 * chaque nouveau point de journalisation.
 *
 * `resultat` accepte volontairement une chaîne libre ('succes'/'echec')
 * plutôt qu'un enum : le sens de "succès" ou "échec" dépend de l'action
 * (un login raté est un échec, un accès refusé EST le résultat attendu du
 * contrôle qui l'a produit), et cette nuance reste à la charge de l'appelant.
 *
 * Ne recevoir ici, sous aucun prétexte, ni mot de passe, ni jeton (JWT,
 * session, CSRF), ni clé — voir le docblock de AuditLog.
 */
class AuditLogService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    /**
     * @param string|null $utilisateurEmail À renseigner explicitement pour un événement
     *                                       sans utilisateur connecté (ex. échec de connexion) —
     *                                       ignoré si un utilisateur authentifié est détecté.
     */
    public function enregistrer(
        ActionAudit $action,
        string $resultat = 'succes',
        ?string $details = null,
        ?string $entite = null,
        ?int $entiteId = null,
        ?string $utilisateurEmail = null,
    ): void {
        $log = new AuditLog();
        $log->setAction($action);
        $log->setResultat($resultat);
        $log->setDetails($details);
        $log->setEntite($entite);
        $log->setEntiteId($entiteId);

        $utilisateur = $this->security->getUser();
        if ($utilisateur instanceof User) {
            $log->setUtilisateur($utilisateur);
            $log->setUtilisateurEmail($utilisateur->getEmail());
        } else {
            $log->setUtilisateurEmail($utilisateurEmail);
        }

        $request = $this->requestStack->getCurrentRequest();
        $log->setIpAddress($request?->getClientIp() ?? 'inconnue');
        $log->setUserAgent($request?->headers->get('User-Agent'));

        $this->em->persist($log);
        $this->em->flush();
    }
}
