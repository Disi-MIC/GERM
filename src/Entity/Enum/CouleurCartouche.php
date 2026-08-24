<?php

namespace App\Entity\Enum;

/** Couleurs de cartouche/toner (CMJN) — couvre aussi bien le laser mono (NOIR) que le couleur. */
enum CouleurCartouche: string
{
    case NOIR = 'noir';
    case CYAN = 'cyan';
    case MAGENTA = 'magenta';
    case JAUNE = 'jaune';

    public function label(): string
    {
        return match ($this) {
            self::NOIR => 'Noir',
            self::CYAN => 'Cyan',
            self::MAGENTA => 'Magenta',
            self::JAUNE => 'Jaune',
        };
    }
}
