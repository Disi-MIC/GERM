<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service', name: 'admin_service_')]
#[IsGranted('ROLE_SUPERADMIN')]
class ServiceController extends AbstractController
{
    public function __construct(private readonly FileStorage $fileStorage)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ServiceRepository $repository): Response
    {
        return $this->render('admin/service/index.html.twig', [
            'services' => $repository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->stockerNoteServiceFichier($form, $service);
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'Service créé.');

            return $this->redirectToRoute('admin_service_index');
        }

        return $this->render('admin/service/new.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->stockerNoteServiceFichier($form, $service);
            $em->flush();

            $this->addFlash('success', 'Service mis à jour.');

            return $this->redirectToRoute('admin_service_index');
        }

        return $this->render('admin/service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-service-'.$service->getId(), $request->request->get('_token'))) {
            if (!$service->getPersonnels()->isEmpty() || !$service->getMateriels()->isEmpty() || !$service->getVehicules()->isEmpty() || !$service->getHistoriqueAffectations()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer ce service : du personnel, du matériel, des véhicules ou un historique de carrière y sont encore rattachés.');
            } else {
                if ($service->getNoteServiceFichier()) {
                    $this->fileStorage->delete($service->getNoteServiceFichier());
                }
                $em->remove($service);
                $em->flush();
                $this->addFlash('success', 'Service supprimé.');
            }
        }

        return $this->redirectToRoute('admin_service_index');
    }

    /**
     * Champ 'noteServiceFichierUpload' non-mappé (voir ServiceType) : stocké
     * ici manuellement, même logique que ServiceController (Api) mais en un
     * seul aller-retour puisque le formulaire Twig envoie tout ensemble.
     */
    private function stockerNoteServiceFichier(FormInterface $form, Service $service): void
    {
        $fichier = $form->get('noteServiceFichierUpload')->getData();
        if (!$fichier) {
            return;
        }

        if ($service->getNoteServiceFichier()) {
            $this->fileStorage->delete($service->getNoteServiceFichier());
        }

        $stocke = $this->fileStorage->store($fichier, 'note-service-service');
        $service->setNoteServiceFichier($stocke['path']);
        $service->setNoteServiceNomOriginal($stocke['originalName']);
    }
}
