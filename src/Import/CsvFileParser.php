<?php

namespace App\Import;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Lit un fichier CSV (délimiteur ';', comme les fichiers exportés par Excel en français).
 */
class CsvFileParser implements FileParserInterface
{
    public function supports(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true);
    }

    public function parse(UploadedFile $file): iterable
    {
        $splFile = new \SplFileObject($file->getPathname());
        $splFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE);
        $splFile->setCsvControl(';');

        $headers = null;

        foreach ($splFile as $lineIndex => $fields) {
            if ($this->estLigneVide($fields)) {
                continue;
            }

            if (null === $headers) {
                $headers = array_map(
                    fn (?string $h) => strtolower(trim($this->retirerBom($h ?? ''))),
                    $fields
                );
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                if ('' === $header) {
                    continue;
                }
                $value = $fields[$i] ?? null;
                $row[$header] = (null === $value || '' === trim((string) $value)) ? null : trim((string) $value);
            }

            yield $lineIndex + 1 => $row;
        }
    }

    /**
     * @param mixed[] $fields
     */
    private function estLigneVide(array $fields): bool
    {
        foreach ($fields as $value) {
            if (null !== $value && '' !== trim((string) $value)) {
                return false;
            }
        }

        return true;
    }

    private function retirerBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
