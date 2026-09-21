<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;

class HierarchieImporter extends AbstractListeValeurImporter
{
    public function getSupportedType(): TypeImport
    {
        return TypeImport::HIERARCHIE;
    }

    protected function getCategorie(): CategorieListeValeur
    {
        return CategorieListeValeur::HIERARCHIE;
    }
}
