<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;

class ProgrammeImporter extends AbstractListeValeurImporter
{
    public function getSupportedType(): TypeImport
    {
        return TypeImport::PROGRAMME;
    }

    protected function getCategorie(): CategorieListeValeur
    {
        return CategorieListeValeur::PROGRAMME;
    }
}
