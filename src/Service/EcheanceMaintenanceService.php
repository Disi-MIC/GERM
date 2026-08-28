<?php

namespace App\Service;

use App\Repository\MaintenanceRepository;
use App\Repository\MaterielInformatiqueRepository;

/**
 * Échéances de maintenance préventive du parc informatique, calculées à la
 * volée plutôt que stockées : dernière maintenance réalisée pour le matériel
 * (ou, à défaut, sa date d'acquisition puis sa date d'enregistrement) + sa
 * périodicité (MaterielInformatique::periodiciteMois). Seuls les matériels
 * avec une périodicité définie sont concernés.
 *
 * Extrait de DashboardController (qui l'utilise pour l'affichage) pour être
 * réutilisé par NotifierEcheancesMaintenanceCommand (alerte proactive) sans
 * dupliquer le calcul.
 */
class EcheanceMaintenanceService
{
    public function __construct(
        private readonly MaterielInformatiqueRepository $materielRepository,
        private readonly MaintenanceRepository $maintenanceRepository,
    ) {
    }

    /**
     * @return array{enRetard: list<array<string, mixed>>, aVenir: list<array<string, mixed>>}
     */
    public function calculer(int $fenetreJours = 30): array
    {
        $dernieresDates = $this->maintenanceRepository->findDernieresDatesParMateriel();
        $aujourdhui = new \DateTimeImmutable('today');
        $limite = $aujourdhui->modify(\sprintf('+%d days', $fenetreJours));

        $enRetard = [];
        $aVenir = [];

        foreach ($this->materielRepository->findAvecPeriodiciteMaintenance() as $materiel) {
            $reference = $dernieresDates[$materiel->getId()]
                ?? $materiel->getDateAcquisition()
                ?? $materiel->getCreatedAt();
            $echeance = $reference->modify(\sprintf('+%d months', $materiel->getPeriodiciteMois()));

            $entree = [
                'materielId' => $materiel->getId(),
                'numeroInventaire' => $materiel->getNumeroInventaire(),
                'marque' => $materiel->getMarque(),
                'modele' => $materiel->getModele(),
                'echeance' => $echeance->format('Y-m-d'),
            ];

            if ($echeance < $aujourdhui) {
                $entree['jours'] = $aujourdhui->diff($echeance)->days;
                $enRetard[] = $entree;
            } elseif ($echeance <= $limite) {
                $entree['jours'] = $echeance->diff($aujourdhui)->days;
                $aVenir[] = $entree;
            }
        }

        usort($enRetard, fn (array $a, array $b) => $b['jours'] <=> $a['jours']);
        usort($aVenir, fn (array $a, array $b) => $a['jours'] <=> $b['jours']);

        return ['enRetard' => $enRetard, 'aVenir' => $aVenir];
    }
}
