<?php

namespace App\Import;

use App\Entity\Service;
use App\Repository\DirectionRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServiceImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly ServiceRepository $serviceRepository,
        private readonly DirectionRepository $directionRepository,
    ) {
    }

    public function getSupportedType(): TypeImport
    {
        return TypeImport::SERVICE;
    }

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('code', 'Code (obligatoire, unique)', true, 'DRH'),
            new ImportColumnDefinition('nom', 'Nom (obligatoire)', true, 'Direction des Ressources Humaines'),
            new ImportColumnDefinition('direction_code', 'Code de la direction de rattachement (obligatoire)', true, 'DAGE'),
            new ImportColumnDefinition('description', 'Description', false),
            new ImportColumnDefinition('actif', 'Actif : oui/non (par défaut oui)', false, 'oui'),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $code = $this->parseString($row['code'] ?? null);
        if (null === $code) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code est obligatoire.');
        }

        if ($this->serviceRepository->findOneBy(['code' => $code])) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("Le service '%s' existe déjà.", $code));
        }

        $nom = $this->parseString($row['nom'] ?? null);
        if (null === $nom) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le nom est obligatoire.');
        }

        $directionCode = $this->parseString($row['direction_code'] ?? null);
        if (null === $directionCode) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code de direction est obligatoire.');
        }

        $direction = $this->directionRepository->findOneBy(['code' => $directionCode]);
        if (null === $direction) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, sprintf("Direction '%s' introuvable.", $directionCode));
        }

        $service = new Service();
        $service->setCode($code);
        $service->setNom($nom);
        $service->setDirection($direction);
        $service->setDescription($this->parseString($row['description'] ?? null));
        $service->setActif($this->parseBool($row['actif'] ?? null, true));

        $violations = $this->validator->validate($service);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($service);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
