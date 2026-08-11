<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Personnel;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le personnel côté frontend Angular. La ressource Personnel
 * est exposée en lecture seule via API Platform (voir l'entité) : la
 * création (avec son mouvement de carrière initial), l'édition, la
 * suppression (avec son garde-fou métier) et la photo passent par ces
 * actions dédiées — même logique que PersonnelController côté Twig.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class PersonnelController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/personnels', name: 'api_personnel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $personnel = $this->serializer->deserialize($request->getContent(), Personnel::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($personnel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($personnel);

        $nomination = new HistoriqueAffectation();
        $nomination->setPersonnel($personnel);
        $nomination->setService($personnel->getService());
        $nomination->setFonction($personnel->getFonction());
        $nomination->setGrade($personnel->getGrade());
        $nomination->setTypeMouvement(TypeMouvementCarriere::NOMINATION);
        $nomination->setDateEffet($personnel->getDateEmbauche() ?? new \DateTimeImmutable());
        $this->em->persist($nomination);

        $this->em->flush();

        return $this->json($personnel, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/personnels/{id}', name: 'api_personnel_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Personnel $personnel, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), Personnel::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $personnel,
        ]);

        $violations = $this->validator->validate($personnel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/personnels/{id}', name: 'api_personnel_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Personnel $personnel): JsonResponse
    {
        if (
            !$personnel->getHistoriqueAffectations()->isEmpty()
            || !$personnel->getConges()->isEmpty()
            || !$personnel->getDemandesJouissance()->isEmpty()
            || !$personnel->getDecisionsConge()->isEmpty()
            || !$personnel->getDemandesDecision()->isEmpty()
            || !$personnel->getCartesProfessionnelles()->isEmpty()
            || !$personnel->getDemandesCartePro()->isEmpty()
        ) {
            return $this->json(['errors' => ['personnel' => 'Impossible de supprimer cette fiche : elle a un historique de carrière, des congés, des décisions, des demandes de congé, des cartes professionnelles ou des demandes de carte professionnelle enregistrées.']], JsonResponse::HTTP_CONFLICT);
        }

        if ($personnel->getUser()) {
            return $this->json(['errors' => ['personnel' => "Impossible de supprimer cette fiche : elle est liée à un compte de connexion. Merci de délier ou supprimer d'abord ce compte."]], JsonResponse::HTTP_CONFLICT);
        }

        $photo = $personnel->getPhoto();

        $this->em->remove($personnel);
        $this->em->flush();

        if ($photo) {
            $this->fileStorage->delete($photo);
        }

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/personnels/{id}/photo', name: 'api_personnel_photo_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadPhoto(Personnel $personnel, Request $request): JsonResponse
    {
        $file = $request->files->get('photoFichier');

        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['photoFichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($personnel->getPhoto()) {
            $this->fileStorage->delete($personnel->getPhoto());
        }

        $png = $this->convertirPhotoEnPng($file);
        $stocke = $this->fileStorage->storeContent($png, 'photo.png', 'png', 'personnel-photos');
        $personnel->setPhoto($stocke['path']);
        $this->em->flush();

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/personnels/{id}/photo', name: 'api_personnel_photo', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function photo(Personnel $personnel): StreamedResponse
    {
        if (!$personnel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($personnel) {
            fpassthru($this->fileStorage->readStream($personnel->getPhoto()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($personnel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    /**
     * Convertie en PNG quel que soit le format d'origine (JPEG, GIF, WEBP,
     * BMP, AVIF...), pour garantir un rendu fiable partout où la photo est
     * affichée ou intégrée (dont le PDF de la carte professionnelle).
     */
    private function convertirPhotoEnPng(UploadedFile $file): string
    {
        $image = imagecreatefromstring(file_get_contents($file->getPathname()));

        if (false === $image) {
            throw new BadRequestHttpException("Ce fichier n'a pas pu être lu comme une image.");
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function violationsResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
