<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\Personnel;
use App\Form\CarteProfessionnelleType;
use App\Repository\CarteProfessionnelleRepository;
use App\Service\PieceJustificativeUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Cartes professionnelles des agents. Historisées : chaque délivrance ou
 * renouvellement crée une nouvelle ligne, aucune autre ressource n'en dépend
 * donc la suppression ne nécessite pas de garde-fou d'utilisation.
 */
#[IsGranted('ROLE_RH_CARTE_PRO')]
class CarteProfessionnelleController extends AbstractController
{
    #[Route('/admin/cartes-professionnelles', name: 'admin_carte_professionnelle_index', methods: ['GET'])]
    public function index(CarteProfessionnelleRepository $carteRepository): Response
    {
        return $this->render('admin/carte_professionnelle/index.html.twig', [
            'cartes' => $carteRepository->findBy([], ['dateDelivrance' => 'DESC']),
        ]);
    }

    #[Route('/admin/cartes-professionnelles/new', name: 'admin_carte_professionnelle_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $carte = new CarteProfessionnelle();
        $form = $this->createForm(CarteProfessionnelleType::class, $carte, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherFichier($form, $carte, $uploader);
            $em->persist($carte);
            $em->flush();

            $this->addFlash('success', 'Carte professionnelle enregistrée.');

            return $this->redirectToRoute('admin_carte_professionnelle_index');
        }

        return $this->render('admin/carte_professionnelle/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_carte_professionnelle_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/carte-professionnelle/new', name: 'admin_personnel_carte_professionnelle_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $carte = new CarteProfessionnelle();
        $carte->setPersonnel($personnel);
        $form = $this->createForm(CarteProfessionnelleType::class, $carte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherFichier($form, $carte, $uploader);
            $em->persist($carte);
            $em->flush();

            $this->addFlash('success', 'Carte professionnelle enregistrée.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/carte_professionnelle/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/carte-professionnelle/{id}/edit', name: 'admin_carte_professionnelle_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CarteProfessionnelle $carte, Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $form = $this->createForm(CarteProfessionnelleType::class, $carte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherFichier($form, $carte, $uploader);
            $em->flush();

            $this->addFlash('success', 'Carte professionnelle mise à jour.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $carte->getPersonnel()->getId()]);
        }

        return $this->render('admin/carte_professionnelle/edit.html.twig', [
            'personnel' => $carte->getPersonnel(),
            'carte' => $carte,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $carte->getPersonnel()->getId()]),
        ]);
    }

    #[Route('/admin/carte-professionnelle/{id}/delete', name: 'admin_carte_professionnelle_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CarteProfessionnelle $carte, Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $personnel = $carte->getPersonnel();

        if ($this->isCsrfTokenValid('delete-carte-professionnelle-'.$carte->getId(), $request->request->get('_token'))) {
            if ($carte->getCheminFichier()) {
                $uploader->delete($carte->getCheminFichier());
            }
            $em->remove($carte);
            $em->flush();
            $this->addFlash('success', 'Carte professionnelle supprimée.');
        }

        return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
    }

    #[Route('/admin/piece-justificative/carte/{id}/download', name: 'admin_piece_carte_download', requirements: ['id' => '\d+'])]
    public function downloadPiece(CarteProfessionnelle $carte, PieceJustificativeUploader $uploader): BinaryFileResponse
    {
        if (!$carte->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($uploader->absolutePath($carte->getCheminFichier()));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $carte->getNomOriginal() ?? 'carte-professionnelle');

        return $response;
    }

    private function attacherFichier(FormInterface $form, CarteProfessionnelle $carte, PieceJustificativeUploader $uploader): void
    {
        $file = $form->get('fichier')->getData();

        if (!$file instanceof UploadedFile) {
            return;
        }

        if ($carte->getCheminFichier()) {
            $uploader->delete($carte->getCheminFichier());
        }

        $stocke = $uploader->store($file, 'carte-professionnelle');
        $carte->setCheminFichier($stocke['path']);
        $carte->setNomOriginal($stocke['originalName']);
    }
}
