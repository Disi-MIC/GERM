<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\MaterielInformatique;
use App\Repository\ListeValeurRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MaterielInformatiqueImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly MaterielInformatiqueRepository $materielRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly PersonnelRepository $personnelRepository,
    ) {
    }

    public function getSupportedType(): TypeImport
    {
        return TypeImport::MATERIEL;
    }

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('numero_inventaire', "N° d'inventaire (obligatoire, unique)", true, 'INV-0001'),
            new ImportColumnDefinition('type_code', 'Code du type de matériel (obligatoire, voir Listes de valeurs)', true, 'ordinateur_bureau'),
            new ImportColumnDefinition('marque', 'Marque (obligatoire)', true, 'Dell'),
            new ImportColumnDefinition('modele', 'Modèle (obligatoire)', true, 'OptiPlex'),
            new ImportColumnDefinition('numero_serie', 'Numéro de série', false),
            new ImportColumnDefinition('specifications', 'Caractéristiques techniques', false),
            new ImportColumnDefinition('date_acquisition', "Date d'acquisition (AAAA-MM-JJ)", false, '2023-03-01'),
            new ImportColumnDefinition('valeur_acquisition', "Valeur d'acquisition", false, '450000'),
            new ImportColumnDefinition('fournisseur', 'Fournisseur', false),
            new ImportColumnDefinition('garantie_jusquau', "Garantie jusqu'au (AAAA-MM-JJ)", false, '2026-03-01'),
            new ImportColumnDefinition('etat_code', "Code de l'état (obligatoire, voir Listes de valeurs)", true, 'en_stock'),
            new ImportColumnDefinition('service_code', 'Code du service de rattachement (obligatoire)', true, 'DRH'),
            new ImportColumnDefinition('affecte_a_matricule', "Matricule de l'agent affecté", false),
            new ImportColumnDefinition('observations', 'Observations', false),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $numeroInventaire = $this->parseString($row['numero_inventaire'] ?? null);
        if (null === $numeroInventaire) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, "Le n° d'inventaire est obligatoire.");
        }

        if ($this->materielRepository->findOneBy(['numeroInventaire' => $numeroInventaire])) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("Le matériel '%s' existe déjà.", $numeroInventaire));
        }

        $marque = $this->parseString($row['marque'] ?? null);
        $modele = $this->parseString($row['modele'] ?? null);
        if (null === $marque || null === $modele) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Marque et modèle sont obligatoires.');
        }

        $typeCode = $this->parseString($row['type_code'] ?? null);
        $type = null !== $typeCode ? $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::TYPE_MATERIEL, $typeCode) : null;
        if (null === $type) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Type de matériel '%s' introuvable.", $typeCode ?? ''));
        }

        $etatCode = $this->parseString($row['etat_code'] ?? null);
        $etat = null !== $etatCode ? $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::ETAT_MATERIEL, $etatCode) : null;
        if (null === $etat) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("État '%s' introuvable.", $etatCode ?? ''));
        }

        $serviceCode = $this->parseString($row['service_code'] ?? null);
        $service = null !== $serviceCode ? $this->serviceRepository->findOneBy(['code' => $serviceCode]) : null;
        if (null === $service) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Service '%s' introuvable.", $serviceCode ?? ''));
        }

        $affecteAMatricule = $this->parseString($row['affecte_a_matricule'] ?? null);
        $affecteA = null;
        if (null !== $affecteAMatricule) {
            $affecteA = $this->personnelRepository->findOneBy(['matricule' => $affecteAMatricule]);
            if (null === $affecteA) {
                return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Agent affecté '%s' introuvable.", $affecteAMatricule));
            }
        }

        try {
            $dateAcquisition = $this->parseDate($row['date_acquisition'] ?? null);
            $garantieJusquau = $this->parseDate($row['garantie_jusquau'] ?? null);
            $valeurAcquisition = $this->parseDecimal($row['valeur_acquisition'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $e->getMessage());
        }

        $materiel = new MaterielInformatique();
        $materiel->setNumeroInventaire($numeroInventaire);
        $materiel->setType($type);
        $materiel->setMarque($marque);
        $materiel->setModele($modele);
        $materiel->setNumeroSerie($this->parseString($row['numero_serie'] ?? null));
        $materiel->setSpecifications($this->parseString($row['specifications'] ?? null));
        $materiel->setDateAcquisition($dateAcquisition);
        $materiel->setValeurAcquisition($valeurAcquisition);
        $materiel->setFournisseur($this->parseString($row['fournisseur'] ?? null));
        $materiel->setGarantieJusquau($garantieJusquau);
        $materiel->setEtat($etat);
        $materiel->setService($service);
        $materiel->setAffecteA($affecteA);
        $materiel->setObservations($this->parseString($row['observations'] ?? null));

        $violations = $this->validator->validate($materiel);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($materiel);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
