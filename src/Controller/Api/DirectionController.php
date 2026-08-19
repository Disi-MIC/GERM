<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Direction;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les directions côté frontend Angular. La ressource Direction
 * est exposée en lecture seule via API Platform (voir l'entité) : la
 * création, l'édition et la note de service justifiant un directeur passent
 * par ces actions dédiées — même logique que Personnel/MaterielInformatique.
 */
#[IsGranted('ROLE_RH_RESPONSABLE')]
class DirectionController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/directions', name: 'api_direction_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $direction = $this->serializer->deserialize($request->getContent(), Direction::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($direction);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($direction);
        $this->em->flush();

        return $this->json($direction, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/directions/{id}', name: 'api_direction_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Direction $direction, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), Direction::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $direction,
        ]);

        $violations = $this->validator->validate($direction);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($direction, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/directions/{id}', name: 'api_direction_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Direction $direction): JsonResponse
    {
        if (!$direction->getServices()->isEmpty()) {
            return $this->json(['errors' => ['direction' => 'Impossible de supprimer cette direction : des services y sont encore rattachés.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($direction->getNoteServiceFichier()) {
            $this->fileStorage->delete($direction->getNoteServiceFichier());
        }

        $this->em->remove($direction);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/directions/{id}/note-service', name: 'api_direction_note_service_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadNoteService(Direction $direction, Request $request): JsonResponse
    {
        $file = $request->files->get('fichier');

        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($direction->getNoteServiceFichier()) {
            $this->fileStorage->delete($direction->getNoteServiceFichier());
        }

        $stocke = $this->fileStorage->store($file, 'note-service-direction');
        $direction->setNoteServiceFichier($stocke['path']);
        $direction->setNoteServiceNomOriginal($stocke['originalName']);
        $this->em->flush();

        return $this->json($direction, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/directions/{id}/note-service', name: 'api_direction_note_service_fichier', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function noteServiceFichier(Direction $direction): StreamedResponse
    {
        if (!$direction->getNoteServiceFichier()) {
            throw $this->createNotFoundException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Note de service - %s', $direction->getNom() ?? ''),
            pathinfo($direction->getNoteServiceFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($direction) {
            fpassthru($this->fileStorage->readStream($direction->getNoteServiceFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($direction->getNoteServiceFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('inline', $nom));

        return $response;
    }
}
