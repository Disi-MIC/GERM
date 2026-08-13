<?php

namespace App\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Construit le classeur .xlsx de modèle pour un type d'import : une ligne d'en-têtes
 * (les clés attendues par ImportRunner::run(), à ne surtout pas traduire ni renommer)
 * suivie d'une ligne d'exemple, avec un commentaire par colonne rappelant le libellé
 * complet et le caractère obligatoire — pour rester lisible sans changer le texte des
 * en-têtes que le parseur doit retrouver tel quel.
 */
class XlsxTemplateGenerator
{
    private const COULEUR_OBLIGATOIRE = 'C0392B';
    private const COULEUR_OPTIONNELLE = '2C3E50';

    /**
     * @param ImportColumnDefinition[] $columns
     */
    public function generate(TypeImport $type, array $columns): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($type->label(), 0, 31));

        foreach (array_values($columns) as $index => $column) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 1);

            $headerCell = $sheet->getCell($colLetter.'1');
            $headerCell->setValue($column->key);
            $headerCell->getStyle()->getFont()->setBold(true);
            $headerCell->getStyle()->getFont()->getColor()->setRGB('FFFFFF');
            $headerCell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($column->required ? self::COULEUR_OBLIGATOIRE : self::COULEUR_OPTIONNELLE);

            $comment = $sheet->getComment($colLetter.'1');
            $comment->getText()->createTextRun($column->label.($column->required ? ' — obligatoire' : ' — optionnel'));
            $comment->setWidth('240pt');
            $comment->setHeight('70pt');

            $exampleCell = $sheet->getCell($colLetter.'2');
            $exampleCell->setValue($column->example ?? '');
            $exampleCell->getStyle()->getFont()->setItalic(true);
            $exampleCell->getStyle()->getFont()->getColor()->setRGB('7F8C8D');

            $sheet->getColumnDimension($colLetter)->setWidth(max(16, mb_strlen($column->key) + 6));
        }

        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);

        return $spreadsheet;
    }
}
