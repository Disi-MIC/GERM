<?php

namespace App\Import;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Aides de parsing communes aux classes d'import (dates, décimaux, booléens, messages
 * de validation). La correspondance colonne -> champ d'entité reste explicite dans
 * chaque importeur ; seules ces briques bas niveau sont partagées.
 */
trait ImportParsingTrait
{
    private function parseString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }
        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function parseBool(?string $value, bool $default): bool
    {
        $value = $this->parseString($value);
        if (null === $value) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'oui', 'true', 'vrai', 'actif'], true);
    }

    /**
     * @throws \InvalidArgumentException si la valeur n'est pas vide mais mal formée
     */
    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        $value = $this->parseString($value);
        if (null === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(sprintf("date invalide '%s' (format attendu : AAAA-MM-JJ)", $value));
        }

        return $date;
    }

    /**
     * @throws \InvalidArgumentException si la valeur n'est pas vide mais mal formée
     */
    private function parseDecimal(?string $value): ?string
    {
        $value = $this->parseString($value);
        if (null === $value) {
            return null;
        }

        $normalise = str_replace(',', '.', $value);
        if (!is_numeric($normalise)) {
            throw new \InvalidArgumentException(sprintf("valeur numérique invalide '%s'", $value));
        }

        return number_format((float) $normalise, 2, '.', '');
    }

    /**
     * @throws \InvalidArgumentException si la valeur n'est pas vide mais mal formée
     */
    private function parseInt(?string $value): ?int
    {
        $value = $this->parseString($value);
        if (null === $value) {
            return null;
        }

        if (!ctype_digit($value)) {
            throw new \InvalidArgumentException(sprintf("nombre entier invalide '%s'", $value));
        }

        return (int) $value;
    }

    private function violationsToMessage(ConstraintViolationListInterface $violations): string
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return implode(' ; ', $messages);
    }
}
