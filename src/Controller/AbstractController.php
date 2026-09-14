<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * Réponse 422 uniforme pour les violations de contraintes de validation —
     * même format `{errors: {champ: message}}` dans tous les contrôleurs API
     * d'écriture, pour que le frontend puisse toujours l'afficher de la même
     * façon (voir par ex. materiel-informatique-detail.component.ts::submit()).
     */
    protected function violationsResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Neutralise l'injection de formule dans les exports CSV.
     *
     * Un tableur interprète comme une formule toute cellule commençant par
     * =, +, - ou @ : une valeur saisie dans un champ libre de GERM (fonction,
     * marque, observations...) devient alors du code exécuté sur le poste de
     * l'agent qui ouvre l'export — d'autant plus sûrement que nos exports
     * portent un BOM UTF-8, qui fait ouvrir le fichier nativement par Excel.
     * fputcsv() ne protège pas de cela : son échappement concerne le format
     * CSV (guillemets, séparateurs), pas l'interprétation par le tableur.
     *
     * Le préfixe apostrophe est la parade recommandée par l'OWASP : il force
     * le tableur à traiter la cellule comme du texte, et reste invisible à
     * l'affichage. La tabulation et le retour chariot sont inclus car ils
     * permettent de faire précéder le caractère déclencheur.
     */
    protected function protegerCsv(string|int|float|null $valeur): string
    {
        $valeur = (string) ($valeur ?? '');

        if ('' !== $valeur && str_contains("=+-@\t\r", $valeur[0])) {
            return "'".$valeur;
        }

        return $valeur;
    }
}
