<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;

class ChapitreImporter extends AbstractListeValeurImporter
{
    public function getSupportedType(): TypeImport
    {
        return TypeImport::CHAPITRE;
    }

    protected function getCategorie(): CategorieListeValeur
    {
        return CategorieListeValeur::CHAPITRE;
    }
}
