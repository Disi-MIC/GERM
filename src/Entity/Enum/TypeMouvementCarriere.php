<?php

namespace App\Entity\Enum;

enum TypeMouvementCarriere: string
{
    case NOMINATION = 'nomination';
    case MUTATION = 'mutation';
    case PROMOTION = 'promotion';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::NOMINATION => 'Nomination',
            self::MUTATION => 'Mutation',
            self::PROMOTION => 'Promotion',
            self::AUTRE => 'Autre',
        };
    }
}
