<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Service;
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
 * Écritures sur les services côté frontend Angular. La ressource Service est
 * exposée en lecture seule via API Platform (voir l'entité) : la création,
 * l'édition et la note de service justifiant un responsable passent par ces
 * actions dédiées — même logique que Personnel/MaterielInformatique.
 */
#[IsGranted('ROLE_RH_RESPONSABLE')]
class ServiceController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/services', name: 'api_service_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $service = $this->serializer->deserialize($request->getContent(), Service::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($service);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($service);
        $this->em->flush();

        return $this->json($service, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/services/{id}', name: 'api_service_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Service $service, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), Service::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $service,
        ]);

        $violations = $this->validator->validate($service);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($service, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/services/{id}', name: 'api_service_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Service $service): JsonResponse
    {
        if (!$service->getPersonnels()->isEmpty() || !$service->getMateriels()->isEmpty() || !$service->getVehicules()->isEmpty() || !$service->getHistoriqueAffectations()->isEmpty()) {
            return $this->json(['errors' => ['service' => 'Impossible de supprimer ce service : du personnel, du matériel, des véhicules ou un historique de carrière y sont encore rattachés.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($service->getNoteServiceFichier()) {
            $this->fileStorage->delete($service->getNoteServiceFichier());
        }

        $this->em->remove($service);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/services/{id}/note-service', name: 'api_service_note_service_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadNoteService(Service $service, Request $request): JsonResponse
    {
        $file = $request->files->get('fichier');

        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($service->getNoteServiceFichier()) {
            $this->fileStorage->delete($service->getNoteServiceFichier());
        }

        $stocke = $this->fileStorage->store($file, 'note-service-service');
        $service->setNoteServiceFichier($stocke['path']);
        $service->setNoteServiceNomOriginal($stocke['originalName']);
        $this->em->flush();

        return $this->json($service, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/services/{id}/note-service', name: 'api_service_note_service_fichier', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function noteServiceFichier(Service $service): StreamedResponse
    {
        if (!$service->getNoteServiceFichier()) {
            throw $this->createNotFoundException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Note de service - %s', $service->getNom() ?? ''),
            pathinfo($service->getNoteServiceFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($service) {
            fpassthru($this->fileStorage->readStream($service->getNoteServiceFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($service->getNoteServiceFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('inline', $nom));

        return $response;
    }
}
