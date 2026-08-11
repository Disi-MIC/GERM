<?php

namespace App\Entity\Enum;

enum PrioriteTicket: string
{
    case BASSE = 'basse';
    case NORMALE = 'normale';
    case HAUTE = 'haute';
    case CRITIQUE = 'critique';

    public function label(): string
    {
        return match ($this) {
            self::BASSE => 'Basse',
            self::NORMALE => 'Normale',
            self::HAUTE => 'Haute',
            self::CRITIQUE => 'Critique',
        };
    }
}
