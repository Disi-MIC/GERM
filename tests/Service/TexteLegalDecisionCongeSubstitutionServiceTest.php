<?php

namespace App\Tests\Service;

use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Direction;
use App\Entity\Enum\Sexe;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Service\TexteLegalDecisionCongeSubstitutionService;
use PHPUnit\Framework\TestCase;

class TexteLegalDecisionCongeSubstitutionServiceTest extends TestCase
{
    private function personnel(): Personnel
    {
        $direction = new Direction();
        $direction->setNom('Direction des Ressources Humaines');

        $service = new Service();
        $service->setNom('Service de la Solde');
        $service->setDirection($direction);

        $personnel = new Personnel();
        $personnel->setNom('Tambédou');
        $personnel->setPrenom('Alassane');
        $personnel->setSexe(Sexe::HOMME);
        $personnel->setMatricule('709192V');
        $personnel->setFonction('Ingénieur Informaticien');
        $personnel->setService($service);

        return $personnel;
    }

    /** createdAt n'a pas de setter public (toujours "maintenant" au constructeur) — fixé par réflexion pour une assertion déterministe. */
    private function demande(Personnel $personnel, \DateTimeImmutable $dateDemande, ?\DateTimeImmutable $datePriseDeService = null): DemandeDecision
    {
        $demande = new DemandeDecision();
        $demande->setPersonnel($personnel);
        $demande->setDatePriseDeService($datePriseDeService);

        $propriete = new \ReflectionProperty(DemandeDecision::class, 'createdAt');
        $propriete->setValue($demande, $dateDemande);

        return $demande;
    }

    private function decision(): DecisionConge
    {
        $decision = new DecisionConge();
        $decision->setNumeroDecision('MIC/DAGE/RH/at');
        $decision->setDateDecision(new \DateTimeImmutable('2026-08-21'));
        $decision->setDateExpiration(new \DateTimeImmutable('2029-08-21'));
        $decision->setNombreJours(24);
        $decision->setPeriodeDebut(new \DateTimeImmutable('2025-08-21'));
        $decision->setNumeroDerniereDecisionReferencee('MIC/DAGE/RH/xy');
        $decision->setNumeroAttestationNonJouissance('123/2026');
        $decision->setDateAttestationNonJouissance(new \DateTimeImmutable('2026-08-15'));

        return $decision;
    }

    public function testSubstitueTousLesEmplacementsConnus(): void
    {
        $service = new TexteLegalDecisionCongeSubstitutionService();
        $demande = $this->demande($this->personnel(), new \DateTimeImmutable('2026-08-10'), new \DateTimeImmutable('2023-01-05'));

        $texte = 'Demande du {{demande.dateDemande}}, prise de service le {{demande.datePriseDeService}} — '
            .'{{agent.civilite}} {{agent.nomComplet}}, matricule {{agent.matricule}}, '
            .'{{agent.fonction}}, {{agent.service}}, {{agent.direction}} — décision {{decision.numeroDecision}}, '
            .'{{decision.nombreJours}} jours, du {{decision.periodeDebut}} au {{decision.dateDecision}}, '
            .'expire le {{decision.dateExpiration}}, réf. décision antérieure {{decision.numeroDerniereDecisionReferencee}}, '
            .'attestation {{decision.numeroAttestationNonJouissance}} du {{decision.dateAttestationNonJouissance}}.';

        $resultat = $service->substituer($texte, $demande, $this->decision());

        $this->assertSame(
            'Demande du 10 août 2026, prise de service le 5 janvier 2023 — '
            .'Monsieur Alassane Tambédou, matricule 709192V, '
            .'Ingénieur Informaticien, Service de la Solde, Direction des Ressources Humaines — décision MIC/DAGE/RH/at, '
            .'24 jours, du 21 août 2025 au 21 août 2026, '
            .'expire le 21 août 2029, réf. décision antérieure MIC/DAGE/RH/xy, '
            .'attestation 123/2026 du 15 août 2026.',
            $resultat,
        );
    }

    public function testCiviliteFemme(): void
    {
        $service = new TexteLegalDecisionCongeSubstitutionService();
        $personnel = $this->personnel();
        $personnel->setSexe(Sexe::FEMME);
        $demande = $this->demande($personnel, new \DateTimeImmutable('2026-08-10'));

        $resultat = $service->substituer('{{agent.civilite}}', $demande, $this->decision());

        $this->assertSame('Madame', $resultat);
    }

    public function testEmplacementInconnuResteTelQuel(): void
    {
        $service = new TexteLegalDecisionCongeSubstitutionService();
        $demande = $this->demande($this->personnel(), new \DateTimeImmutable('2026-08-10'));

        $resultat = $service->substituer('Bonjour {{agnet.nomComplet}} !', $demande, $this->decision());

        $this->assertSame('Bonjour {{agnet.nomComplet}} !', $resultat);
    }

    public function testTexteNullRenvoieNull(): void
    {
        $service = new TexteLegalDecisionCongeSubstitutionService();
        $demande = $this->demande($this->personnel(), new \DateTimeImmutable('2026-08-10'));

        $this->assertNull($service->substituer(null, $demande, $this->decision()));
    }

    public function testValeurAbsenteDonneChaineVide(): void
    {
        $service = new TexteLegalDecisionCongeSubstitutionService();
        $personnel = $this->personnel();
        $personnel->setService(null);
        $demande = $this->demande($personnel, new \DateTimeImmutable('2026-08-10'));

        $resultat = $service->substituer('[{{agent.direction}}]', $demande, new DecisionConge());

        $this->assertSame('[]', $resultat);
    }
}
