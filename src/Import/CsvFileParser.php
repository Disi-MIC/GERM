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
        yield from $this->parsePath($file->getPathname());
    }

    /**
     * Même analyse que parse(), mais à partir d'un contenu déjà en mémoire
     * (ex. un CSV récupéré depuis Google Sheets) plutôt que d'un fichier
     * uploadé — passe par un fichier temporaire pour réutiliser telle
     * quelle la lecture SplFileObject (gestion correcte des champs entre
     * guillemets contenant des retours à la ligne, notamment).
     *
     * $delimiter par défaut ';' comme parse() (export Excel français), mais
     * l'export CSV de Google Sheets utilise toujours la virgule (RFC 4180,
     * indépendant de la locale du classeur) — l'appelant doit donc préciser
     * ',' pour ce cas.
     *
     * @return iterable<int, array<string, ?string>>
     */
    public function parseContent(string $content, string $delimiter = ';'): iterable
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'germ_import_');
        if (false === $tmpPath) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire pour analyser le CSV.');
        }

        file_put_contents($tmpPath, $content);

        try {
            yield from $this->parsePath($tmpPath, $delimiter);
        } finally {
            unlink($tmpPath);
        }
    }

    /**
     * @return iterable<int, array<string, ?string>>
     */
    private function parsePath(string $path, string $delimiter = ';'): iterable
    {
        $splFile = new \SplFileObject($path);
        $splFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE);
        $splFile->setCsvControl($delimiter, '"', '');

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
