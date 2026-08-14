<?php

namespace App\Import;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Orchestration commune à tous les imports : choix du parseur (CSV/XLSX) ou
 * récupération depuis Google Sheets, choix de l'importeur selon le type,
 * vérification des colonnes obligatoires, boucle sur les lignes avec flush
 * par lot, et confinement de toute exception dans le rapport plutôt que de
 * faire planter la page.
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
        private readonly CsvFileParser $csvFileParser,
        XlsxFileParser $xlsxFileParser,
        private readonly HttpClientInterface $httpClient,
        private readonly GoogleSheetUrlResolver $googleSheetUrlResolver,
        DirectionImporter $directionImporter,
        ServiceImporter $serviceImporter,
        PersonnelImporter $personnelImporter,
        MaterielInformatiqueImporter $materielImporter,
        VehiculeImporter $vehiculeImporter,
    ) {
        $this->parsers = [$this->csvFileParser, $xlsxFileParser];
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

    public function run(TypeImport $type, UploadedFile $file): ImportReport
    {
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

        return $this->traiterLignes($type, $rows);
    }

    /**
     * Récupère l'export CSV public du classeur Google Sheets et le fait
     * suivre au même pipeline que l'import par fichier — aucune écriture
     * vers Google, lecture seule. $urlSaisie accepte tel quel un lien de
     * partage copié depuis le navigateur (voir GoogleSheetUrlResolver).
     */
    public function runFromGoogleSheet(TypeImport $type, string $urlSaisie): ImportReport
    {
        try {
            $urlExport = $this->googleSheetUrlResolver->resoudreUrlExportCsv($urlSaisie);
        } catch (\InvalidArgumentException $e) {
            return new ImportReport(0, 0, 0, [], $e->getMessage());
        }

        try {
            $response = $this->httpClient->request('GET', $urlExport, ['timeout' => 15]);
            $statusCode = $response->getStatusCode();
            $contenu = $response->getContent(false);
        } catch (\Throwable $e) {
            return new ImportReport(0, 0, 0, [], 'Impossible de contacter Google Sheets : '.$e->getMessage());
        }

        if (400 === $statusCode) {
            return new ImportReport(0, 0, 0, [], 'Google Sheets a répondu avec le code 400 — l\'onglet visé par ce lien n\'existe plus (ex. supprimé ou réorganisé depuis). Réouvrez le classeur, copiez le lien de partage à jour depuis l\'onglet voulu, puis relancez la liaison.');
        }

        if (200 !== $statusCode) {
            return new ImportReport(0, 0, 0, [], sprintf(
                'Google Sheets a répondu avec le code %d — vérifiez que le classeur est partagé en lecture ("Toute personne disposant du lien").',
                $statusCode,
            ));
        }

        // Classeur non partagé publiquement : Google renvoie une page de connexion HTML plutôt qu'un CSV.
        if (str_starts_with(ltrim($contenu), '<')) {
            return new ImportReport(0, 0, 0, [], 'Ce classeur n\'est pas accessible publiquement. Partagez-le avec l\'accès "Toute personne disposant du lien — Lecteur", puis réessayez.');
        }

        try {
            $rows = iterator_to_array($this->csvFileParser->parseContent($contenu, ','));
        } catch (\Throwable $e) {
            return new ImportReport(0, 0, 0, [], 'Impossible de lire le contenu du classeur : '.$e->getMessage());
        }

        return $this->traiterLignes($type, $rows);
    }

    /**
     * @param array<int, array<string, ?string>> $rows
     */
    private function traiterLignes(TypeImport $type, array $rows): ImportReport
    {
        $importer = $this->importers[$type->value];

        if ([] === $rows) {
            return new ImportReport(0, 0, 0, [], 'Aucune ligne de données trouvée.');
        }

        $colonnesManquantes = $this->colonnesManquantes($importer, array_keys(reset($rows)));
        if ([] !== $colonnesManquantes) {
            return new ImportReport(0, 0, 0, [], 'Colonnes manquantes : '.implode(', ', $colonnesManquantes));
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
