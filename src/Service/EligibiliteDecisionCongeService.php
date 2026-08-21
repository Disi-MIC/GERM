<?php

namespace App\Service;

use App\Dto\EligibiliteDecisionCongeResult;
use App\Entity\DemandeDecision;
use App\Entity\Enum\CategorieAgentConge;
use App\Entity\Personnel;
use App\Repository\DecisionCongeRepository;
use App\Repository\ParametreEligibiliteCongeRepository;

/**
 * Calcule l'éligibilité d'un agent à une nouvelle décision de congé et le
 * nombre de jours à lui octroyer. Seule source de vérité pour cette règle
 * métier — DemandeDecisionController (endpoint de consultation et
 * genererEtTransmettre()) l'appelle systématiquement, Angular ne fait
 * qu'afficher le résultat.
 *
 * Règle : jours acquis par mois PLEIN écoulé depuis la date de référence,
 * plafonnés, sans proratisation des jours restants sous un mois plein —
 * taux, plafond et délai d'éligibilité réglables séparément par catégorie
 * (fonctionnaire/non-fonctionnaire), chacune correspondant à une base légale
 * différente (Loi 61-33 du 15/06/1961 pour les fonctionnaires, Décret
 * 74-347 du 12/04/1974 pour les non-fonctionnaires) — voir
 * ParametreEligibiliteConge et Api/ParametreEligibiliteCongeController
 * (réglages RH Admin). Valeurs par défaut identiques pour les deux
 * catégories (2 jours/mois, plafond 90, délai 12 mois) tant que le RH Admin
 * n'a pas différencié l'une des deux.
 *
 * Date de référence : la dernière DecisionConge réellement délivrée à
 * l'agent (DecisionCongeRepository::findDerniereDecision(), source
 * faisant autorité — indépendante de ce qui a pu être saisi sur la demande
 * en cours) ; à défaut (aucune décision antérieure), la date de prise de
 * service ou de dernière décision déclarée sur la demande traitée
 * (DemandeDecision::$datePriseDeService / $dateDerniereDecision, seules
 * sources disponibles pour un tout premier agent) ; en dernier recours,
 * Personnel::$dateEmbauche.
 */
class EligibiliteDecisionCongeService
{
    public function __construct(
        private readonly DecisionCongeRepository $decisionCongeRepository,
        private readonly ParametreEligibiliteCongeRepository $parametreRepository,
    ) {
    }

    /**
     * "fonctionnaire" est le seul code de la liste de valeurs "type de
     * contrat" (Personnel::$typeContrat) à valoir CategorieAgentConge::FONCTIONNAIRE —
     * tout le reste (contractuel/stagiaire/consultant/non renseigné) vaut
     * NON_FONCTIONNAIRE, ces codes restant par ailleurs librement éditables
     * depuis le backoffice (Admin/ListeValeurController).
     */
    public function categorieDe(Personnel $personnel): CategorieAgentConge
    {
        return 'fonctionnaire' === $personnel->getTypeContrat()?->getCode()
            ? CategorieAgentConge::FONCTIONNAIRE
            : CategorieAgentConge::NON_FONCTIONNAIRE;
    }

    public function calculer(Personnel $personnel, ?DemandeDecision $demande, \DateTimeImmutable $dateDecision): ?EligibiliteDecisionCongeResult
    {
        $derniereDecision = $this->decisionCongeRepository->findDerniereDecision($personnel);
        $dateDerniereDecision = $derniereDecision?->getDateDecision();

        $dateReference = $dateDerniereDecision
            ?? $demande?->getDatePriseDeService()
            ?? $demande?->getDateDerniereDecision()
            ?? $personnel->getDateEmbauche();

        if (!$dateReference) {
            return null;
        }

        $parametres = $this->parametreRepository->recupererOuCreer($this->categorieDe($personnel));

        $dateEligibilite = $dateReference->modify(\sprintf('+%d months', $parametres->getDelaiEligibiliteMois()));
        $eligible = $dateDecision >= $dateEligibilite;

        $moisPleins = $this->moisPleinsEcoules($dateReference, $dateDecision);
        $joursAccordables = min($moisPleins * $parametres->getJoursParMois(), $parametres->getPlafondJours());

        $message = $eligible
            ? "L'agent est éligible à une nouvelle décision."
            : "L'agent n'a pas encore accompli une année complète de travail.";

        return new EligibiliteDecisionCongeResult(
            eligible: $eligible,
            dateReference: $dateReference,
            dateEligibilite: $dateEligibilite,
            dateDerniereDecision: $dateDerniereDecision,
            dateDecision: $dateDecision,
            joursAccordables: $joursAccordables,
            message: $message,
        );
    }

    /**
     * Nombre de mois pleins entre deux dates — pas de proratisation des
     * jours restants (voir docblock de la classe).
     */
    private function moisPleinsEcoules(\DateTimeImmutable $debut, \DateTimeImmutable $fin): int
    {
        $mois = ((int) $fin->format('Y') - (int) $debut->format('Y')) * 12
            + ((int) $fin->format('n') - (int) $debut->format('n'));

        if ((int) $fin->format('j') < (int) $debut->format('j')) {
            --$mois;
        }

        return max(0, $mois);
    }
}
