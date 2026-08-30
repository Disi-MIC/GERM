<?php

namespace App\Service;

use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\DecisionCongeRepository;
use App\Repository\DelegationRepository;
use App\Repository\DocumentAdministratifRepository;

/**
 * Échéances RH (documents administratifs, cartes professionnelles, décisions
 * de congé, délégations) calculées à la volée plutôt que stockées — même
 * principe que EcheanceMaintenanceService pour le parc informatique.
 * Réutilisé par DashboardController (affichage) et NotifierAlertesRhCommand
 * (alerte proactive), pour ne pas dupliquer le calcul.
 */
class EcheanceRhService
{
    private const FENETRE_JOURS_DOCUMENTS = 30;
    private const FENETRE_JOURS_DECISIONS = 30;
    private const FENETRE_JOURS_DELEGATIONS = 15;

    // Cartes pro : même seuil que CarteProfessionnelle::getStatutAffiche()/
    // estTropTotPourRenouvellement() ("Expire bientôt") — pour rester cohérent
    // avec l'avertissement déjà affiché carte par carte.
    private const FENETRE_JOURS_CARTES = 60;

    public function __construct(
        private readonly DocumentAdministratifRepository $documentRepository,
        private readonly CarteProfessionnelleRepository $carteRepository,
        private readonly DecisionCongeRepository $decisionRepository,
        private readonly DelegationRepository $delegationRepository,
    ) {
    }

    /**
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    public function calculerDocuments(int $fenetreJours = self::FENETRE_JOURS_DOCUMENTS): array
    {
        $enRetard = [];
        $aVenir = [];
        [$aujourdhui, $limite] = $this->bornes($fenetreJours);

        foreach ($this->documentRepository->findAvecExpiration() as $document) {
            $echeance = $document->getDateExpiration();
            if (null === $echeance) {
                continue;
            }

            $entree = [
                'documentId' => $document->getId(),
                'personnelId' => $document->getPersonnel()?->getId(),
                'personnel' => $document->getPersonnel()?->getNomComplet() ?? '',
                'libelle' => $document->getLibelle(),
                'type' => $document->getType()?->getLibelle() ?? '',
                'echeance' => $echeance->format('Y-m-d'),
            ];

            $this->classer($entree, $echeance, $aujourdhui, $limite, $enRetard, $aVenir);
        }

        return $this->trier($enRetard, $aVenir);
    }

    /**
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    public function calculerCartes(int $fenetreJours = self::FENETRE_JOURS_CARTES): array
    {
        $enRetard = [];
        $aVenir = [];
        [$aujourdhui, $limite] = $this->bornes($fenetreJours);

        foreach ($this->carteRepository->findBy(['statut' => StatutCarteProfessionnelle::VALIDE]) as $carte) {
            $echeance = $carte->getDateExpiration();
            if (null === $echeance) {
                continue;
            }

            $entree = [
                'carteId' => $carte->getId(),
                'personnelId' => $carte->getPersonnel()?->getId(),
                'personnel' => $carte->getPersonnel()?->getNomComplet() ?? '',
                'numero' => $carte->getNumero(),
                'echeance' => $echeance->format('Y-m-d'),
            ];

            $this->classer($entree, $echeance, $aujourdhui, $limite, $enRetard, $aVenir);
        }

        return $this->trier($enRetard, $aVenir);
    }

    /**
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    public function calculerDecisions(int $fenetreJours = self::FENETRE_JOURS_DECISIONS): array
    {
        $enRetard = [];
        $aVenir = [];
        [$aujourdhui, $limite] = $this->bornes($fenetreJours);

        foreach ($this->decisionRepository->findAll() as $decision) {
            $echeance = $decision->getDateExpiration();
            if (null === $echeance) {
                continue;
            }

            $entree = [
                'decisionId' => $decision->getId(),
                'personnelId' => $decision->getPersonnel()?->getId(),
                'personnel' => $decision->getPersonnel()?->getNomComplet() ?? '',
                'numeroDecision' => $decision->getNumeroDecision(),
                'echeance' => $echeance->format('Y-m-d'),
            ];

            $this->classer($entree, $echeance, $aujourdhui, $limite, $enRetard, $aVenir);
        }

        return $this->trier($enRetard, $aVenir);
    }

    /**
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    public function calculerDelegations(int $fenetreJours = self::FENETRE_JOURS_DELEGATIONS): array
    {
        $enRetard = [];
        $aVenir = [];
        [$aujourdhui, $limite] = $this->bornes($fenetreJours);

        foreach ($this->delegationRepository->findActives() as $delegation) {
            $echeance = $delegation->getDateFin();
            if (null === $echeance) {
                continue;
            }

            $entree = [
                'delegationId' => $delegation->getId(),
                'delegant' => $delegation->getDelegant()?->getNomComplet() ?? '',
                'delegataire' => $delegation->getDelegataire()?->getNomComplet() ?? '',
                'role' => $delegation->getRoleDelegue()?->label() ?? '',
                'echeance' => $echeance->format('Y-m-d'),
            ];

            $this->classer($entree, $echeance, $aujourdhui, $limite, $enRetard, $aVenir);
        }

        return $this->trier($enRetard, $aVenir);
    }

    /** @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} */
    private function bornes(int $fenetreJours): array
    {
        $aujourdhui = new \DateTimeImmutable('today');

        return [$aujourdhui, $aujourdhui->modify(\sprintf('+%d days', $fenetreJours))];
    }

    /**
     * @param array<string, mixed>          $entree
     * @param list<array<string, mixed>>    $enRetard
     * @param list<array<string, mixed>>    $aVenir
     */
    private function classer(array $entree, \DateTimeImmutable $echeance, \DateTimeImmutable $aujourdhui, \DateTimeImmutable $limite, array &$enRetard, array &$aVenir): void
    {
        if ($echeance < $aujourdhui) {
            $entree['jours'] = $aujourdhui->diff($echeance)->days;
            $enRetard[] = $entree;
        } elseif ($echeance <= $limite) {
            $entree['jours'] = $echeance->diff($aujourdhui)->days;
            $aVenir[] = $entree;
        }
    }

    /**
     * @param list<array<string, mixed>> $enRetard
     * @param list<array<string, mixed>> $aVenir
     *
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    private function trier(array $enRetard, array $aVenir): array
    {
        usort($enRetard, fn (array $a, array $b) => $b['jours'] <=> $a['jours']);
        usort($aVenir, fn (array $a, array $b) => $a['jours'] <=> $b['jours']);

        return ['enRetard' => $enRetard, 'aVenir' => $aVenir];
    }
}
