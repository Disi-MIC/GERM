<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Personnel;
use App\Form\PersonnelType;
use App\Repository\PersonnelRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/personnel', name: 'admin_personnel_')]
#[IsGranted('ROLE_RH_PERSONNEL')]
class PersonnelController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PersonnelRepository $repository): Response
    {
        return $this->render('admin/personnel/index.html.twig', [
            'personnels' => $repository->search($request->query->get('q')),
            'q' => $request->query->get('q'),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileStorage $fileStorage): Response
    {
        $personnel = new Personnel();
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPhoto($form, $personnel, $fileStorage);
            $em->persist($personnel);

            $nomination = new HistoriqueAffectation();
            $nomination->setPersonnel($personnel);
            $nomination->setService($personnel->getService());
            $nomination->setFonction($personnel->getFonction());
            $nomination->setGrade($personnel->getGrade());
            $nomination->setTypeMouvement(TypeMouvementCarriere::NOMINATION);
            $nomination->setDateEffet($personnel->getDateEmbauche() ?? new \DateTimeImmutable());
            $em->persist($nomination);

            $em->flush();

            $this->addFlash('success', 'Fiche personnel créée avec succès.');

            return $this->redirectToRoute('admin_personnel_index');
        }

        return $this->render('admin/personnel/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Personnel $personnel): Response
    {
        return $this->render('admin/personnel/show.html.twig', [
            'personnel' => $personnel,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Personnel $personnel, EntityManagerInterface $em, FileStorage $fileStorage): Response
    {
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPhoto($form, $personnel, $fileStorage);
            $em->flush();

            $this->addFlash('success', 'Fiche personnel mise à jour.');

            return $this->redirectToRoute('admin_personnel_index');
        }

        return $this->render('admin/personnel/edit.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Personnel $personnel, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-personnel-'.$personnel->getId(), $request->request->get('_token'))) {
            if (
                !$personnel->getHistoriqueAffectations()->isEmpty()
                || !$personnel->getConges()->isEmpty()
                || !$personnel->getDemandesJouissance()->isEmpty()
                || !$personnel->getDecisionsConge()->isEmpty()
                || !$personnel->getDemandesDecision()->isEmpty()
                || !$personnel->getCartesProfessionnelles()->isEmpty()
                || !$personnel->getDemandesCartePro()->isEmpty()
            ) {
                $this->addFlash('danger', 'Impossible de supprimer cette fiche : elle a un historique de carrière, des congés, des décisions, des demandes de congé, des cartes professionnelles ou des demandes de carte professionnelle enregistrées.');
            } else {
                $em->remove($personnel);
                $em->flush();
                $this->addFlash('success', 'Fiche personnel supprimée.');
            }
        }

        return $this->redirectToRoute('admin_personnel_index');
    }

    #[Route('/{id}/photo', name: 'photo', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function photo(Personnel $personnel, FileStorage $fileStorage): StreamedResponse
    {
        if (!$personnel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($fileStorage, $personnel) {
            fpassthru($fileStorage->readStream($personnel->getPhoto()));
        });
        $response->headers->set('Content-Type', $fileStorage->mimeType($personnel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    private function attacherPhoto(FormInterface $form, Personnel $personnel, FileStorage $fileStorage): void
    {
        $file = $form->get('photoFichier')->getData();

        if (!$file instanceof UploadedFile) {
            return;
        }

        if ($personnel->getPhoto()) {
            $fileStorage->delete($personnel->getPhoto());
        }

        // Convertie en PNG quel que soit le format d'origine (JPEG, GIF, WEBP,
        // BMP, AVIF...), pour garantir un rendu fiable partout où la photo est
        // affichée ou intégrée (dont le PDF de la carte professionnelle).
        $png = $this->convertirPhotoEnPng($file);
        $stocke = $fileStorage->storeContent($png, 'photo.png', 'png', 'personnel-photos');
        $personnel->setPhoto($stocke['path']);
    }

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
}
