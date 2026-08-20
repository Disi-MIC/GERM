<?php

namespace App\Dto;

/**
 * Résultat du calcul d'éligibilité d'un agent à une nouvelle décision de
 * congé — voir EligibiliteDecisionCongeService. Value object immuable :
 * jamais construit ailleurs que par le service, jamais modifié après coup.
 */
final class EligibiliteDecisionCongeResult
{
    public function __construct(
        public readonly bool $eligible,
        public readonly \DateTimeImmutable $dateReference,
        public readonly \DateTimeImmutable $dateEligibilite,
        public readonly ?\DateTimeImmutable $dateDerniereDecision,
        public readonly \DateTimeImmutable $dateDecision,
        public readonly int $joursAccordables,
        public readonly string $message,
    ) {
    }

    /**
     * @return array{eligible: bool, dateReference: string, dateEligibilite: string, dateDerniereDecision: ?string, dateDecision: string, joursAccordables: int, message: string}
     */
    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'dateReference' => $this->dateReference->format('Y-m-d'),
            'dateEligibilite' => $this->dateEligibilite->format('Y-m-d'),
            'dateDerniereDecision' => $this->dateDerniereDecision?->format('Y-m-d'),
            'dateDecision' => $this->dateDecision->format('Y-m-d'),
            'joursAccordables' => $this->joursAccordables,
            'message' => $this->message,
        ];
    }
}
