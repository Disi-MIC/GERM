<?php

namespace App\Tests\Service;

use App\Entity\CarteProfessionnelle;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Personnel;
use App\Repository\CarteProfessionnelleRepository;
use App\Service\CarteProfessionnellePdfStockageService;
use App\Service\FileStorage;
use App\Service\PersonnelPhotoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PersonnelPhotoServiceTest extends TestCase
{
    private function uploadedPng(): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'photo').'.png';
        $image = imagecreatetruecolor(2, 2);
        imagepng($image, $chemin);

        return new UploadedFile($chemin, 'photo.png', 'image/png', null, true);
    }

    private function carte(Personnel $personnel, StatutCarteProfessionnelle $statut, ?string $cheminFichier): CarteProfessionnelle
    {
        $carte = new CarteProfessionnelle();
        $carte->setPersonnel($personnel);
        $carte->setStatut($statut);
        $carte->setCheminFichier($cheminFichier);

        return $carte;
    }

    public function testRemplacerRegenereLesCartesValidesDejaDelivrees(): void
    {
        $personnel = new Personnel();
        $carteValide = $this->carte($personnel, StatutCarteProfessionnelle::VALIDE, 'carte-42.pdf');

        $fileStorage = $this->createStub(FileStorage::class);
        $fileStorage->method('storeContent')->willReturn(['path' => 'personnel-photos/photo.png', 'originalName' => 'photo.png']);

        $em = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createMock(CarteProfessionnelleRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['personnel' => $personnel, 'statut' => StatutCarteProfessionnelle::VALIDE])
            ->willReturn([$carteValide]);

        $pdfStockage = $this->createMock(CarteProfessionnellePdfStockageService::class);
        $pdfStockage->expects($this->once())->method('genererEtStocker')->with($carteValide);

        $service = new PersonnelPhotoService($fileStorage, $em, $repository, $pdfStockage);
        $service->remplacer($personnel, $this->uploadedPng());
    }

    public function testRemplacerIgnoreLesCartesSansFichierEtNonValides(): void
    {
        $personnel = new Personnel();
        $carteSansFichier = $this->carte($personnel, StatutCarteProfessionnelle::VALIDE, null);

        $fileStorage = $this->createStub(FileStorage::class);
        $fileStorage->method('storeContent')->willReturn(['path' => 'personnel-photos/photo.png', 'originalName' => 'photo.png']);

        $em = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createStub(CarteProfessionnelleRepository::class);
        $repository->method('findBy')->willReturn([$carteSansFichier]);

        $pdfStockage = $this->createMock(CarteProfessionnellePdfStockageService::class);
        $pdfStockage->expects($this->never())->method('genererEtStocker');

        $service = new PersonnelPhotoService($fileStorage, $em, $repository, $pdfStockage);
        $service->remplacer($personnel, $this->uploadedPng());
    }
}
