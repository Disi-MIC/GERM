<?php

namespace App\Security;

use App\Entity\CarteProfessionnelle;
use App\Entity\DecisionConge;
use App\Entity\DemandeCartePro;
use App\Entity\DemandeDecision;
use App\Entity\DemandeJouissance;
use App\Entity\DocumentAdministratif;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * GERM-020 : centralise les contrôles « cet enregistrement est-il à moi ? »
 * du self-service agent (`/api/me/*`), jusqu'ici réécrits à la main à 11
 * endroits dans `MeController` et `MeDemandesController`, sous 3 formes
 * différentes (comparaison inline, requête scopée, méthode privée
 * `appartientAMoi()` dupliquant la même logique). La correspondance
 * entité → propriétaire est fixée ici, une seule fois : une future route
 * sur une de ces entités hérite du contrôle sans le réécrire.
 *
 * Volontairement restreint aux types d'entité où l'appartenance se lit
 * directement (`getPersonnel()`/`getAffecteA()`). La plupart des appelants
 * utilisent `denyAccessUnlessGranted()` (403 uniforme) ; trois endroits de
 * `MeDemandesController` (`creerDemandeCartePro()`, `creerDemandeJouissance()`,
 * `creerTicket()`) vérifient l'appartenance d'une entité *référencée* et
 * répondent en 422 avec un message de champ dédié, consommé par le
 * frontend — ils appellent donc `isGranted()` (sans lever d'exception) pour
 * réutiliser la même logique sans changer leur code HTTP ni la forme de
 * leur réponse. Deux endroits proches restent hors de ce Voter pour une
 * raison documentée sur place : `MeController::marquerNotificationLue()` et
 * `materielQrcode()` renvoient délibérément 404 (pas 403) pour ne pas
 * confirmer l'existence d'un objet à un tiers — changer ce code de retour
 * serait une régression, pas une simplification.
 */
class PersonnelOwnershipVoter extends Voter
{
    public const EST_PROPRIETAIRE = 'PERSONNEL_OWNERSHIP';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EST_PROPRIETAIRE === $attribute && false !== $this->proprietaire($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $personnel = $user->getPersonnel();

        return null !== $personnel && $this->proprietaire($subject) === $personnel;
    }

    /**
     * @return Personnel|null|false null si l'objet existe mais n'a personne
     *                               d'affecté ; false si ce type d'objet
     *                               n'est pas couvert par ce Voter
     */
    private function proprietaire(mixed $subject): Personnel|null|false
    {
        return match (true) {
            $subject instanceof CarteProfessionnelle,
            $subject instanceof DocumentAdministratif,
            $subject instanceof DemandeCartePro,
            $subject instanceof DemandeDecision,
            $subject instanceof DemandeJouissance,
            $subject instanceof DecisionConge => $subject->getPersonnel(),
            $subject instanceof MaterielInformatique => $subject->getAffecteA(),
            default => false,
        };
    }
}
