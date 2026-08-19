<?php

namespace App\Service;

use App\Entity\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Remplace la photo d'un agent — partagé entre l'upload RH (Api\PersonnelController,
 * fiche de n'importe quel agent) et l'upload en libre-service (Api\MeController,
 * sa propre fiche uniquement) : même validation, même conversion, même stockage.
 */
class PersonnelPhotoService
{
    public function __construct(
        private readonly FileStorage $fileStorage,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return string|null Message d'erreur de validation, ou null si le fichier est accepté. */
    public function erreurValidation(?UploadedFile $file): ?string
    {
        return $this->fileStorage->erreurValidation($file);
    }

    public function remplacer(Personnel $personnel, UploadedFile $file): void
    {
        if ($personnel->getPhoto()) {
            $this->fileStorage->delete($personnel->getPhoto());
        }

        $png = $this->convertirEnPng($file);
        $stocke = $this->fileStorage->storeContent($png, 'photo.png', 'png', 'personnel-photos');
        $personnel->setPhoto($stocke['path']);
        $this->em->flush();
    }

    /**
     * Convertie en PNG quel que soit le format d'origine (JPEG, GIF, WEBP,
     * BMP, AVIF...), pour garantir un rendu fiable partout où la photo est
     * affichée ou intégrée (dont le PDF de la carte professionnelle).
     */
    private function convertirEnPng(UploadedFile $file): string
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

        return $png;
    }
}
