<?php

namespace App\Entity\Enum;

enum StatutDelegation: string
{
    case ACTIVE = 'active';
    case REVOQUEE = 'revoquee';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::REVOQUEE => 'Révoquée',
        };
    }
}
