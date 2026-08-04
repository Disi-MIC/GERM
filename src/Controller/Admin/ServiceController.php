<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service', name: 'admin_service_')]
#[IsGranted('ROLE_SUPERADMIN')]
class ServiceController extends AbstractController
{
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
                $em->remove($service);
                $em->flush();
                $this->addFlash('success', 'Service supprimé.');
            }
        }

        return $this->redirectToRoute('admin_service_index');
    }
}
