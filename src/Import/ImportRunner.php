<?php

namespace App\Import;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Orchestration commune à tous les imports : choix du parseur (CSV/XLSX), choix de
 * l'importeur selon le type, vérification des colonnes obligatoires, boucle sur les
 * lignes avec flush par lot, et confinement de toute exception dans le rapport plutôt
 * que de faire planter la page.
 */
class ImportRunner
{
    private const FLUSH_EVERY = 100;

    /** @var FileParserInterface[] */
    private readonly array $parsers;

    /** @var array<string, EntityImporterInterface> */
    private readonly array $importers;

    public function __construct(
        private readonly EntityManagerInterface $em,
        CsvFileParser $csvFileParser,
        XlsxFileParser $xlsxFileParser,
        DirectionImporter $directionImporter,
        ServiceImporter $serviceImporter,
        PersonnelImporter $personnelImporter,
        MaterielInformatiqueImporter $materielImporter,
        VehiculeImporter $vehiculeImporter,
    ) {
        $this->parsers = [$csvFileParser, $xlsxFileParser];
        $this->importers = [
            TypeImport::DIRECTION->value => $directionImporter,
            TypeImport::SERVICE->value => $serviceImporter,
            TypeImport::PERSONNEL->value => $personnelImporter,
            TypeImport::MATERIEL->value => $materielImporter,
            TypeImport::VEHICULE->value => $vehiculeImporter,
        ];
    }

    /**
     * @return ImportColumnDefinition[]
     */
    public function getColumns(TypeImport $type): array
    {
        return $this->importers[$type->value]->getColumns();
    }

    /**
     * @return iterable<int, string[]> en-tête puis une ligne d'exemple
     */
    public function generateTemplateRows(TypeImport $type): iterable
    {
        $columns = $this->getColumns($type);

        yield array_map(static fn (ImportColumnDefinition $c) => $c->key, $columns);
        yield array_map(static fn (ImportColumnDefinition $c) => $c->example ?? '', $columns);
    }

    public function run(TypeImport $type, UploadedFile $file): ImportReport
    {
        $importer = $this->importers[$type->value];

        $parser = null;
        foreach ($this->parsers as $candidat) {
            if ($candidat->supports($file)) {
                $parser = $candidat;
                break;
            }
        }
        if (null === $parser) {
            return new ImportReport(0, 0, 0, [], 'Format de fichier non supporté : utilisez un fichier .csv ou .xlsx.');
        }

        try {
            $rows = iterator_to_array($parser->parse($file));
        } catch (\Throwable $e) {
            return new ImportReport(0, 0, 0, [], 'Impossible de lire le fichier : '.$e->getMessage());
        }

        if ([] === $rows) {
            return new ImportReport(0, 0, 0, [], 'Le fichier ne contient aucune ligne de données.');
        }

        $colonnesManquantes = $this->colonnesManquantes($importer, array_keys(reset($rows)));
        if ([] !== $colonnesManquantes) {
            return new ImportReport(0, 0, 0, [], 'Colonnes manquantes dans le fichier : '.implode(', ', $colonnesManquantes));
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;
        $rowResults = [];

        try {
            foreach ($rows as $lineNumber => $row) {
                $result = $importer->importRow($row, $lineNumber);

                match ($result->status) {
                    ImportRowStatus::CREATED => $created++,
                    ImportRowStatus::SKIPPED_EXISTING => $skipped++,
                    ImportRowStatus::ERROR => $errors++,
                };

                if (ImportRowStatus::CREATED !== $result->status) {
                    $rowResults[] = $result;
                }

                if ($created > 0 && 0 === $created % self::FLUSH_EVERY) {
                    $this->em->flush();
                }
            }

            $this->em->flush();
        } catch (\Throwable $e) {
            return new ImportReport($created, $skipped, $errors, $rowResults, "Erreur inattendue pendant l'import : ".$e->getMessage());
        }

        return new ImportReport($created, $skipped, $errors, $rowResults);
    }

    /**
     * @param string[] $colonnesPresentes
     *
     * @return string[]
     */
    private function colonnesManquantes(EntityImporterInterface $importer, array $colonnesPresentes): array
    {
        $manquantes = [];
        foreach ($importer->getColumns() as $colonne) {
            if ($colonne->required && !in_array($colonne->key, $colonnesPresentes, true)) {
                $manquantes[] = $colonne->key;
            }
        }

        return $manquantes;
    }
}
