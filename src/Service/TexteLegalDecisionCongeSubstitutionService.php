<?php

namespace App\Service;

use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\Sexe;

/**
 * Substitue les emplacements ("{{agent.nomComplet}}", "{{decision.periodeDebut}}",
 * etc.) présents dans le texte légal saisi par le RH Admin
 * (ParametresDecisionConge : visas, article 2, article 3, ampliations) par les
 * valeurs propres à l'agent et à la décision en cours de génération. Appelé
 * une seule fois, côté serveur, au moment de la génération
 * (DemandeDecisionController::genererEtTransmettre()) — le résultat déjà
 * substitué est ensuite figé sur la DecisionConge, jamais recalculé : une
 * modification ultérieure des réglages RH Admin (texte ou emplacements) ne
 * doit jamais changer le contenu d'une décision déjà délivrée.
 *
 * Un emplacement inconnu ou mal orthographié reste tel quel dans le texte
 * (pas d'exception) — le RH Congé voit et imprime l'aperçu final avant remise
 * physique de la décision, donc une coquille reste visible avant tout usage
 * réel.
 */
class TexteLegalDecisionCongeSubstitutionService
{
    private const MOIS_FR = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public function substituer(?string $texte, DemandeDecision $demande, DecisionConge $decision): ?string
    {
        if (null === $texte) {
            return null;
        }

        return strtr($texte, $this->valeurs($demande, $decision));
    }

    /** @return array<string, string> */
    private function valeurs(DemandeDecision $demande, DecisionConge $decision): array
    {
        // Toujours renseigné à ce stade : $personnel est obligatoire (nullable: false)
        // sur DemandeDecision, et genererEtTransmettre() n'appelle ce service
        // qu'une fois la demande déjà validée et persistée.
        $personnel = $demande->getPersonnel();

        return [
            '{{demande.dateDemande}}' => $this->dateFr($demande->getCreatedAt()),
            '{{demande.datePriseDeService}}' => $this->dateFr($demande->getDatePriseDeService()),
            '{{demande.numeroPriseDeService}}' => $demande->getNumeroPriseDeService() ?? '',
            '{{agent.civilite}}' => Sexe::FEMME === $personnel->getSexe() ? 'Madame' : 'Monsieur',
            '{{agent.nomComplet}}' => $personnel->getNomComplet(),
            '{{agent.matricule}}' => $personnel->getMatricule() ?? '',
            '{{agent.fonction}}' => $personnel->getFonction() ?? '',
            '{{agent.service}}' => $personnel->getService()?->getNom() ?? '',
            '{{agent.direction}}' => $personnel->getService()?->getDirection()?->getNom() ?? '',
            '{{decision.numeroDecision}}' => $decision->getNumeroDecision() ?? '',
            '{{decision.nombreJours}}' => (string) ($decision->getNombreJours() ?? ''),
            '{{decision.dateDecision}}' => $this->dateFr($decision->getDateDecision()),
            '{{decision.dateExpiration}}' => $this->dateFr($decision->getDateExpiration()),
            '{{decision.periodeDebut}}' => $this->dateFr($decision->getPeriodeDebut()),
            '{{decision.numeroAttestationNonJouissance}}' => $decision->getNumeroAttestationNonJouissance() ?? '',
            '{{decision.dateAttestationNonJouissance}}' => $this->dateFr($decision->getDateAttestationNonJouissance()),
            '{{decision.numeroDerniereDecisionReferencee}}' => $decision->getNumeroDerniereDecisionReferencee() ?? '',
        ];
    }

    /** "24 mars 2023" — même convention que frontend/src/app/shared/date-fr.ts. */
    private function dateFr(?\DateTimeImmutable $date): string
    {
        if (!$date) {
            return '';
        }

        return \sprintf('%d %s %d', (int) $date->format('j'), self::MOIS_FR[(int) $date->format('n')], (int) $date->format('Y'));
    }
}
