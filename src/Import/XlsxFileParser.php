<?php

namespace App\Import;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Lit un fichier Excel (.xlsx). Les cellules de type date sont converties en chaînes 'Y-m-d'
 * ici même, pour que les classes d'import ne manipulent jamais que des chaînes, quel que
 * soit le format source (CSV ou XLSX).
 */
class XlsxFileParser implements FileParserInterface
{
    public function supports(UploadedFile $file): bool
    {
        return 'xlsx' === strtolower($file->getClientOriginalExtension());
    }

    public function parse(UploadedFile $file): iterable
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        $headers = null;

        foreach ($sheet->getRowIterator() as $row) {
            $lineNumber = $row->getRowIndex();

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $this->lireValeurCellule($cell);
            }

            if ($this->estLigneVide($values)) {
                continue;
            }

            if (null === $headers) {
                $headers = array_map(fn (?string $h) => strtolower(trim($h ?? '')), $values);
                continue;
            }

            $rowData = [];
            foreach ($headers as $i => $header) {
                if ('' === $header) {
                    continue;
                }
                $value = $values[$i] ?? null;
                $rowData[$header] = (null === $value || '' === $value) ? null : $value;
            }

            yield $lineNumber => $rowData;
        }
    }

    private function lireValeurCellule(Cell $cell): ?string
    {
        if (null === $cell->getValue()) {
            return null;
        }

        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
        if (ExcelDate::isDateTimeFormatCode($format)) {
            return ExcelDate::excelToDateTimeObject($cell->getCalculatedValue())->format('Y-m-d');
        }

        $value = $cell->getCalculatedValue();

        if (is_float($value)) {
            return 0.0 === fmod($value, 1.0)
                ? (string) (int) $value
                : rtrim(rtrim(sprintf('%.6f', $value), '0'), '.');
        }

        return trim((string) $value);
    }

    /**
     * @param array<int, ?string> $values
     */
    private function estLigneVide(array $values): bool
    {
        foreach ($values as $value) {
            if (null !== $value && '' !== trim((string) $value)) {
                return false;
            }
        }

        return true;
    }
}
