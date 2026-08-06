<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\Personnel;
use App\Form\CarteProfessionnelleType;
use App\Repository\CarteProfessionnelleRepository;
use App\Service\CarteProfessionnellePdfGenerator;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function index(Request $request, CarteProfessionnelleRepository $carteRepository): Response
    {
        $cartes = $carteRepository->findBy([], ['dateDelivrance' => 'DESC']);

        $filtre = $request->query->get('statut_affiche');
        $cartesAffichees = $filtre
            ? array_values(array_filter($cartes, fn (CarteProfessionnelle $c) => $c->getStatutAffiche()['label'] === $filtre))
            : $cartes;

        $compteurs = ['Valide' => 0, 'Expire bientôt' => 0, 'Expirée' => 0, 'Perdue' => 0, 'Volée' => 0, 'Annulée' => 0];
        foreach ($cartes as $c) {
            $label = $c->getStatutAffiche()['label'];
            $compteurs[$label] = ($compteurs[$label] ?? 0) + 1;
        }

        return $this->render('admin/carte_professionnelle/index.html.twig', [
            'cartes' => $cartesAffichees,
            'compteurs' => $compteurs,
            'filtre_statut_affiche' => $filtre,
        ]);
    }

    #[Route('/admin/cartes-professionnelles/new', name: 'admin_carte_professionnelle_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, CarteProfessionnellePdfGenerator $pdfGenerator, FileStorage $uploader): Response
    {
        $carte = new CarteProfessionnelle();
        $form = $this->createForm(CarteProfessionnelleType::class, $carte, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->genererEtStockerPdf($carte, $pdfGenerator, $uploader);
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
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, CarteProfessionnellePdfGenerator $pdfGenerator, FileStorage $uploader): Response
    {
        $carte = new CarteProfessionnelle();
        $carte->setPersonnel($personnel);
        $form = $this->createForm(CarteProfessionnelleType::class, $carte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->genererEtStockerPdf($carte, $pdfGenerator, $uploader);
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
    public function edit(CarteProfessionnelle $carte, Request $request, EntityManagerInterface $em, CarteProfessionnellePdfGenerator $pdfGenerator, FileStorage $uploader): Response
    {
        $form = $this->createForm(CarteProfessionnelleType::class, $carte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->genererEtStockerPdf($carte, $pdfGenerator, $uploader);
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
    public function delete(CarteProfessionnelle $carte, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        $personnel = $carte->getPersonnel();

        if ($this->isCsrfTokenValid('delete-carte-professionnelle-'.$carte->getId(), $request->request->get('_token'))) {
            if ($carte->getCheminFichier()) {
                $uploader->delete($carte->getCheminFichier());
            }
            if ($carte->getCheminQrCode()) {
                $uploader->delete($carte->getCheminQrCode());
            }
            $em->remove($carte);
            $em->flush();
            $this->addFlash('success', 'Carte professionnelle supprimée.');
        }

        return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
    }

    #[Route('/admin/piece-justificative/carte/{id}/download', name: 'admin_piece_carte_download', requirements: ['id' => '\d+'])]
    public function downloadPiece(CarteProfessionnelle $carte, FileStorage $uploader): StreamedResponse
    {
        if (!$carte->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($uploader, $carte) {
            fpassthru($uploader->readStream($carte->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $uploader->mimeType($carte->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $carte->getNomOriginal() ?? 'carte-professionnelle'));

        return $response;
    }

    #[Route('/admin/carte-professionnelle/{id}/generer', name: 'admin_carte_professionnelle_generer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generer(CarteProfessionnelle $carte, Request $request, EntityManagerInterface $em, CarteProfessionnellePdfGenerator $pdfGenerator, FileStorage $uploader): Response
    {
        if ($this->isCsrfTokenValid('generer-carte-professionnelle-'.$carte->getId(), $request->request->get('_token'))) {
            $this->genererEtStockerPdf($carte, $pdfGenerator, $uploader);
            $em->flush();
            $this->addFlash('success', 'PDF régénéré.');
        }

        return $this->redirectToRoute('admin_carte_professionnelle_index');
    }

    private function genererEtStockerPdf(CarteProfessionnelle $carte, CarteProfessionnellePdfGenerator $pdfGenerator, FileStorage $uploader): void
    {
        if ($carte->getCheminFichier()) {
            $uploader->delete($carte->getCheminFichier());
        }
        if ($carte->getCheminQrCode()) {
            $uploader->delete($carte->getCheminQrCode());
        }

        $resultat = $pdfGenerator->generate($carte);

        $stockePdf = $uploader->storeContent($resultat['pdf'], 'carte-'.$carte->getNumero().'.pdf', 'pdf', 'carte-professionnelle');
        $carte->setCheminFichier($stockePdf['path']);
        $carte->setNomOriginal($stockePdf['originalName']);

        $stockeQr = $uploader->storeContent($resultat['qrCode'], 'qr-'.$carte->getNumero().'.png', 'png', 'qr-codes');
        $carte->setCheminQrCode($stockeQr['path']);
    }
}
