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
}
