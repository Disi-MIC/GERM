<?php

namespace App\Tests\Service;

use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Entity\Personnel;
use App\Repository\DecisionCongeRepository;
use App\Service\EligibiliteDecisionCongeService;
use PHPUnit\Framework\TestCase;

/**
 * Règle vérifiée ici (confirmée par le RH Congé, aucune valeur devinée) : 2
 * jours acquis par mois PLEIN écoulé depuis la date de référence, sans
 * distinction fonctionnaire/non-fonctionnaire sur le taux, plafonné à 90
 * jours, sans proratisation. Voir EligibiliteDecisionCongeService pour le
 * détail de la source de la date de référence.
 */
class EligibiliteDecisionCongeServiceTest extends TestCase
{
    private function service(?DecisionConge $derniereDecision): EligibiliteDecisionCongeService
    {
        $repository = $this->createMock(DecisionCongeRepository::class);
        $repository->method('findDerniereDecision')->willReturn($derniereDecision);

        return new EligibiliteDecisionCongeService($repository);
    }

    private function personnel(string $typeContratCode = 'fonctionnaire'): Personnel
    {
        $type = new ListeValeur();
        $type->setCategorie(CategorieListeValeur::TYPE_CONTRAT);
        $type->setCode($typeContratCode);
        $type->setLibelle($typeContratCode);

        $personnel = new Personnel();
        $personnel->setTypeContrat($type);

        return $personnel;
    }

    private function demandeNouvelAgent(\DateTimeImmutable $datePriseDeService): DemandeDecision
    {
        $demande = new DemandeDecision();
        $demande->setNouvellementAffecte(true);
        $demande->setDatePriseDeService($datePriseDeService);

        return $demande;
    }

    public function testNouvelAgentMoinsDUnAnNestPasEligible(): void
    {
        $service = $this->service(null);
        $demande = $this->demandeNouvelAgent(new \DateTimeImmutable('2025-09-01'));

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-08-20'));

        $this->assertNotNull($resultat);
        $this->assertFalse($resultat->eligible);
        $this->assertSame('2025-09-01', $resultat->dateReference->format('Y-m-d'));
        $this->assertSame('2026-09-01', $resultat->dateEligibilite->format('Y-m-d'));
    }

    public function testNouvelAgentExactementUnAnEstEligible(): void
    {
        $service = $this->service(null);
        $demande = $this->demandeNouvelAgent(new \DateTimeImmutable('2025-09-01'));

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-09-01'));

        $this->assertNotNull($resultat);
        $this->assertTrue($resultat->eligible);
    }

    public function testNouvelAgentPlusDUnAnEstEligible(): void
    {
        $service = $this->service(null);
        $demande = $this->demandeNouvelAgent(new \DateTimeImmutable('2025-09-01'));

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-09-05'));

        $this->assertNotNull($resultat);
        $this->assertTrue($resultat->eligible);
    }

    public function testAgentAvecDerniereDecisionMoinsDUnAnNestPasEligible(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2025-03-15'));

        $service = $this->service($derniereDecision);
        $demande = new DemandeDecision();
        $demande->setNouvellementAffecte(false);

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-03-10'));

        $this->assertNotNull($resultat);
        $this->assertFalse($resultat->eligible);
        $this->assertSame('2025-03-15', $resultat->dateReference->format('Y-m-d'));
    }

    public function testAgentAvecDerniereDecisionPlusDUnAnEstEligible(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2025-03-15'));

        $service = $this->service($derniereDecision);
        $demande = new DemandeDecision();
        $demande->setNouvellementAffecte(false);

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-03-20'));

        $this->assertNotNull($resultat);
        $this->assertTrue($resultat->eligible);
    }

    /**
     * La dernière DecisionConge réellement délivrée à l'agent (source
     * faisant autorité) prévaut toujours sur les champs déclaratifs de la
     * demande en cours, même si celle-ci porte une autre date.
     */
    public function testLaDerniereDecisionReellePrevautSurLaDemande(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2025-01-01'));

        $service = $this->service($derniereDecision);
        $demande = new DemandeDecision();
        $demande->setNouvellementAffecte(false);
        $demande->setDateDerniereDecision(new \DateTimeImmutable('2025-06-01'));

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-06-15'));

        $this->assertNotNull($resultat);
        $this->assertSame('2025-01-01', $resultat->dateReference->format('Y-m-d'));
    }

    /**
     * Aucune distinction fonctionnaire/non-fonctionnaire sur le taux
     * (uniquement la base légale diffère) — même nombre de jours pour les
     * deux, à période égale.
     */
    public function testPasDeDistinctionFonctionnaireNonFonctionnaire(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2025-01-01'));
        $dateDecision = new \DateTimeImmutable('2026-01-01');

        $resultatFonctionnaire = $this->service($derniereDecision)->calculer(
            $this->personnel('fonctionnaire'),
            new DemandeDecision(),
            $dateDecision,
        );
        $resultatNonFonctionnaire = $this->service($derniereDecision)->calculer(
            $this->personnel('contractuel'),
            new DemandeDecision(),
            $dateDecision,
        );

        $this->assertSame($resultatFonctionnaire->joursAccordables, $resultatNonFonctionnaire->joursAccordables);
        $this->assertSame(24, $resultatFonctionnaire->joursAccordables);
    }

    public function testDeuxJoursParMoisPleinPlafonneA90(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2020-01-01'));

        $resultat = $this->service($derniereDecision)->calculer(
            $this->personnel(),
            new DemandeDecision(),
            new \DateTimeImmutable('2026-01-01'), // 72 mois pleins écoulés
        );

        $this->assertNotNull($resultat);
        $this->assertTrue($resultat->eligible);
        $this->assertSame(90, $resultat->joursAccordables);
    }

    /**
     * Mois pleins uniquement : les jours restants sous un mois plein ne
     * comptent pas (pas de proratisation, confirmé par le RH Congé).
     */
    public function testPasDeProratisationDesJoursRestants(): void
    {
        $derniereDecision = new DecisionConge();
        $derniereDecision->setDateDecision(new \DateTimeImmutable('2025-01-01'));

        // 13 mois pleins + 10 jours écoulés au 11/02/2026 -> seulement 13
        // mois comptent, soit 26 jours (pas 13,3 mois ~ 26,6 jours).
        $resultat = $this->service($derniereDecision)->calculer(
            $this->personnel(),
            new DemandeDecision(),
            new \DateTimeImmutable('2026-02-11'),
        );

        $this->assertNotNull($resultat);
        $this->assertSame(26, $resultat->joursAccordables);
    }

    public function testAucuneDateDeReferenceRenvoieNull(): void
    {
        $service = $this->service(null);
        $demande = new DemandeDecision();
        $demande->setNouvellementAffecte(false);

        $resultat = $service->calculer($this->personnel(), $demande, new \DateTimeImmutable('2026-01-01'));

        $this->assertNull($resultat);
    }
}
