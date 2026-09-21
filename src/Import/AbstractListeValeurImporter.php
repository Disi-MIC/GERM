<?php

namespace App\Import;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Repository\ListeValeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base commune aux quatre importeurs de listes paramétrables du Personnel
 * (Corps, Hiérarchie, Programme, Chapitre budgétaires) : mêmes colonnes,
 * même logique d'import (créer si le code n'existe pas encore dans cette
 * catégorie), une seule catégorie ListeValeur en dur par sous-classe. Les
 * cinq autres importeurs (Direction, Service...) portent chacun une entité
 * différente et ne partagent donc pas de base — celle-ci n'a de sens que
 * parce que ces quatre-là partagent exactement la même entité ListeValeur.
 */
abstract class AbstractListeValeurImporter implements EntityImporterInterface
{
    use ImportParsingTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly ListeValeurRepository $listeValeurRepository,
    ) {
    }

    abstract protected function getCategorie(): CategorieListeValeur;

    public function getColumns(): array
    {
        return [
            new ImportColumnDefinition('code', 'Code (obligatoire, unique dans cette liste)', true, 'exemple_code'),
            new ImportColumnDefinition('libelle', 'Libellé (obligatoire)', true, 'Exemple de libellé'),
            new ImportColumnDefinition('actif', 'Actif : oui/non (par défaut oui)', false, 'oui'),
        ];
    }

    public function importRow(array $row, int $lineNumber): ImportRowResult
    {
        $code = $this->parseString($row['code'] ?? null);
        if (null === $code) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le code est obligatoire.');
        }

        if ($this->listeValeurRepository->findOneByCategorieAndCode($this->getCategorie(), $code)) {
            return new ImportRowResult($lineNumber, ImportRowStatus::SKIPPED_EXISTING, sprintf("La valeur '%s' existe déjà dans cette liste.", $code));
        }

        $libelle = $this->parseString($row['libelle'] ?? null);
        if (null === $libelle) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, 'Le libellé est obligatoire.');
        }

        $valeur = new ListeValeur();
        $valeur->setCategorie($this->getCategorie());
        $valeur->setCode($code);
        $valeur->setLibelle($libelle);
        $valeur->setActif($this->parseBool($row['actif'] ?? null, true));

        $violations = $this->validator->validate($valeur);
        if (count($violations) > 0) {
            return new ImportRowResult($lineNumber, ImportRowStatus::ERROR, $this->violationsToMessage($violations));
        }

        $this->em->persist($valeur);

        return new ImportRowResult($lineNumber, ImportRowStatus::CREATED);
    }
}
