<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\Carburant;
use App\Entity\Vehicule;
use App\Repository\ListeValeurRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VehiculeImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly VehiculeRepository $vehiculeRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly PersonnelRepository $personnelRepository,
    ) {
    }

    public function getSupportedType(): TypeImport
    {
        return TypeImport::VEHICULE;
    }

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('immatriculation', 'Immatriculation (obligatoire, unique)', true, 'AA-001-BB'),
            new ImportColumnDefinition('type_code', 'Code du type de véhicule (obligatoire, voir Listes de valeurs)', true, 'berline'),
            new ImportColumnDefinition('marque', 'Marque (obligatoire)', true, 'Toyota'),
            new ImportColumnDefinition('modele', 'Modèle (obligatoire)', true, 'Corolla'),
            new ImportColumnDefinition('numero_chassis', 'Numéro de châssis', false),
            new ImportColumnDefinition('carburant', 'Carburant : essence/diesel/electrique/hybride', false, 'essence'),
            new ImportColumnDefinition('date_acquisition', "Date d'acquisition (AAAA-MM-JJ)", false, '2023-03-01'),
            new ImportColumnDefinition('valeur_acquisition', "Valeur d'acquisition", false, '12000000'),
            new ImportColumnDefinition('kilometrage', 'Kilométrage', false, '15000'),
            new ImportColumnDefinition('assurance_jusquau', "Assurance valable jusqu'au (AAAA-MM-JJ)", false, '2026-06-01'),
            new ImportColumnDefinition('visite_technique_jusquau', "Visite technique valable jusqu'au (AAAA-MM-JJ)", false, '2026-06-01'),
            new ImportColumnDefinition('etat_code', "Code de l'état (obligatoire, voir Listes de valeurs)", true, 'en_service'),
            new ImportColumnDefinition('service_code', 'Code du service de rattachement (obligatoire)', true, 'DRH'),
            new ImportColumnDefinition('chauffeur_affecte_matricule', 'Matricule du chauffeur affecté', false),
            new ImportColumnDefinition('observations', 'Observations', false),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $immatriculation = $this->parseString($row['immatriculation'] ?? null);
        if (null === $immatriculation) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, "L'immatriculation est obligatoire.");
        }

        if ($this->vehiculeRepository->findOneBy(['immatriculation' => $immatriculation])) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("Le véhicule '%s' existe déjà.", $immatriculation));
        }

        $marque = $this->parseString($row['marque'] ?? null);
        $modele = $this->parseString($row['modele'] ?? null);
        if (null === $marque || null === $modele) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Marque et modèle sont obligatoires.');
        }

        $typeCode = $this->parseString($row['type_code'] ?? null);
        $type = null !== $typeCode ? $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::TYPE_VEHICULE, $typeCode) : null;
        if (null === $type) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Type de véhicule '%s' introuvable.", $typeCode ?? ''));
        }

        $etatCode = $this->parseString($row['etat_code'] ?? null);
        $etat = null !== $etatCode ? $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::ETAT_VEHICULE, $etatCode) : null;
        if (null === $etat) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("État '%s' introuvable.", $etatCode ?? ''));
        }

        $serviceCode = $this->parseString($row['service_code'] ?? null);
        $service = null !== $serviceCode ? $this->serviceRepository->findOneBy(['code' => $serviceCode]) : null;
        if (null === $service) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Service '%s' introuvable.", $serviceCode ?? ''));
        }

        $carburantValeur = $this->parseString($row['carburant'] ?? null);
        $carburant = null;
        if (null !== $carburantValeur) {
            $carburant = Carburant::tryFrom($carburantValeur);
            if (null === $carburant) {
                return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Carburant '%s' invalide.", $carburantValeur));
            }
        }

        $chauffeurMatricule = $this->parseString($row['chauffeur_affecte_matricule'] ?? null);
        $chauffeur = null;
        if (null !== $chauffeurMatricule) {
            $chauffeur = $this->personnelRepository->findOneBy(['matricule' => $chauffeurMatricule]);
            if (null === $chauffeur) {
                return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Chauffeur '%s' introuvable.", $chauffeurMatricule));
            }
        }

        try {
            $dateAcquisition = $this->parseDate($row['date_acquisition'] ?? null);
            $assuranceJusquau = $this->parseDate($row['assurance_jusquau'] ?? null);
            $visiteTechniqueJusquau = $this->parseDate($row['visite_technique_jusquau'] ?? null);
            $valeurAcquisition = $this->parseDecimal($row['valeur_acquisition'] ?? null);
            $kilometrage = $this->parseInt($row['kilometrage'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $e->getMessage());
        }

        $vehicule = new Vehicule();
        $vehicule->setImmatriculation($immatriculation);
        $vehicule->setType($type);
        $vehicule->setMarque($marque);
        $vehicule->setModele($modele);
        $vehicule->setNumeroChassis($this->parseString($row['numero_chassis'] ?? null));
        $vehicule->setCarburant($carburant);
        $vehicule->setDateAcquisition($dateAcquisition);
        $vehicule->setValeurAcquisition($valeurAcquisition);
        $vehicule->setKilometrage($kilometrage);
        $vehicule->setAssuranceJusquau($assuranceJusquau);
        $vehicule->setVisiteTechniqueJusquau($visiteTechniqueJusquau);
        $vehicule->setEtat($etat);
        $vehicule->setService($service);
        $vehicule->setChauffeurAffecte($chauffeur);
        $vehicule->setObservations($this->parseString($row['observations'] ?? null));

        $violations = $this->validator->validate($vehicule);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($vehicule);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
