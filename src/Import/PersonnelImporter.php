<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\Sexe;
use App\Entity\Enum\StatutPersonnel;
use App\Entity\Personnel;
use App\Repository\ListeValeurRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PersonnelImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly PersonnelRepository $personnelRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
    ) {
    }

    public function getSupportedType(): TypeImport
    {
        return TypeImport::PERSONNEL;
    }

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('matricule', 'Matricule (obligatoire, unique)', true, 'AG-0001'),
            new ImportColumnDefinition('nom', 'Nom (obligatoire)', true, 'Diop'),
            new ImportColumnDefinition('prenom', 'Prénom (obligatoire)', true, 'Fatou'),
            new ImportColumnDefinition('sexe', 'Sexe : M ou F (obligatoire)', true, 'F'),
            new ImportColumnDefinition('date_naissance', 'Date de naissance (AAAA-MM-JJ)', false, '1990-05-12'),
            new ImportColumnDefinition('service_code', 'Code du service de rattachement (obligatoire)', true, 'DRH'),
            new ImportColumnDefinition('fonction', 'Fonction (obligatoire)', true, 'Chargée de projet'),
            new ImportColumnDefinition('grade', 'Grade', false),
            new ImportColumnDefinition('type_contrat_code', 'Code du type de contrat (obligatoire, voir Listes de valeurs)', true, 'fonctionnaire'),
            new ImportColumnDefinition('date_embauche', "Date d'embauche (AAAA-MM-JJ)", false, '2020-01-15'),
            new ImportColumnDefinition('statut', 'Statut : actif/en_conge/suspendu/retraite/demissionnaire (par défaut actif)', false, 'actif'),
            new ImportColumnDefinition('telephone', 'Téléphone', false),
            new ImportColumnDefinition('email', 'Email professionnel', false),
            new ImportColumnDefinition('adresse', 'Adresse', false),
            new ImportColumnDefinition('observations', 'Observations', false),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $matricule = $this->parseString($row['matricule'] ?? null);
        if (null === $matricule) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le matricule est obligatoire.');
        }

        if ($this->personnelRepository->findOneBy(['matricule' => $matricule])) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("Le matricule '%s' existe déjà.", $matricule));
        }

        $nom = $this->parseString($row['nom'] ?? null);
        $prenom = $this->parseString($row['prenom'] ?? null);
        $fonction = $this->parseString($row['fonction'] ?? null);
        if (null === $nom || null === $prenom || null === $fonction) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Nom, prénom et fonction sont obligatoires.');
        }

        $sexeValeur = $this->parseString($row['sexe'] ?? null);
        $sexe = null !== $sexeValeur ? Sexe::tryFrom(strtoupper($sexeValeur)) : null;
        if (null === $sexe) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, "Sexe invalide (attendu : M ou F).");
        }

        $serviceCode = $this->parseString($row['service_code'] ?? null);
        if (null === $serviceCode) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code de service est obligatoire.');
        }
        $service = $this->serviceRepository->findOneBy(['code' => $serviceCode]);
        if (null === $service) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Service '%s' introuvable.", $serviceCode));
        }

        $typeContratCode = $this->parseString($row['type_contrat_code'] ?? null);
        if (null === $typeContratCode) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code de type de contrat est obligatoire.');
        }
        $typeContrat = $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::TYPE_CONTRAT, $typeContratCode);
        if (null === $typeContrat) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Type de contrat '%s' introuvable.", $typeContratCode));
        }

        $statutValeur = $this->parseString($row['statut'] ?? null);
        $statut = null !== $statutValeur ? StatutPersonnel::tryFrom($statutValeur) : StatutPersonnel::ACTIF;
        if (null === $statut) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Statut '%s' invalide.", $statutValeur));
        }

        try {
            $dateNaissance = $this->parseDate($row['date_naissance'] ?? null);
            $dateEmbauche = $this->parseDate($row['date_embauche'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $e->getMessage());
        }

        $personnel = new Personnel();
        $personnel->setMatricule($matricule);
        $personnel->setNom($nom);
        $personnel->setPrenom($prenom);
        $personnel->setSexe($sexe);
        $personnel->setDateNaissance($dateNaissance);
        $personnel->setService($service);
        $personnel->setFonction($fonction);
        $personnel->setGrade($this->parseString($row['grade'] ?? null));
        $personnel->setTypeContrat($typeContrat);
        $personnel->setDateEmbauche($dateEmbauche);
        $personnel->setStatut($statut);
        $personnel->setTelephone($this->parseString($row['telephone'] ?? null));
        $personnel->setEmail($this->parseString($row['email'] ?? null));
        $personnel->setAdresse($this->parseString($row['adresse'] ?? null));
        $personnel->setObservations($this->parseString($row['observations'] ?? null));

        $violations = $this->validator->validate($personnel);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($personnel);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
