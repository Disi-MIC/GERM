<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;

class CorpsImporter extends AbstractListeValeurImporter
{
    public function getSupportedType(): TypeImport
    {
        return TypeImport::CORPS;
    }

    protected function getCategorie(): CategorieListeValeur
    {
        return CategorieListeValeur::CORPS;
    }
}
