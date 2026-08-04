<?php

namespace App\Import;

use App\Entity\Direction;
use App\Repository\DirectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DirectionImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly DirectionRepository $directionRepository,
    ) {
    }

    public function getSupportedType(): TypeImport
    {
        return TypeImport::DIRECTION;
    }

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('code', 'Code (obligatoire, unique)', true, 'DAGE'),
            new ImportColumnDefinition('nom', 'Nom (obligatoire)', true, "Direction de l'Administration Générale et des Équipements"),
            new ImportColumnDefinition('description', 'Description', false),
            new ImportColumnDefinition('actif', "Actif : oui/non (par défaut oui)", false, 'oui'),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $code = $this->parseString($row['code'] ?? null);
        if (null === $code) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code est obligatoire.');
        }

        if ($this->directionRepository->findOneBy(['code' => $code])) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("La direction '%s' existe déjà.", $code));
        }

        $nom = $this->parseString($row['nom'] ?? null);
        if (null === $nom) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le nom est obligatoire.');
        }

        $direction = new Direction();
        $direction->setCode($code);
        $direction->setNom($nom);
        $direction->setDescription($this->parseString($row['description'] ?? null));
        $direction->setActif($this->parseBool($row['actif'] ?? null, true));

        $violations = $this->validator->validate($direction);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($direction);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
