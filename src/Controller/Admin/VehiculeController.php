<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Repository\ListeValeurRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vehicule', name: 'admin_vehicule_')]
#[IsGranted('ROLE_SUPERADMIN')]
class VehiculeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, VehiculeRepository $repository): Response
    {
        return $this->render('admin/vehicule/index.html.twig', [
            'vehicules' => $repository->search($request->query->get('q')),
            'q' => $request->query->get('q'),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ListeValeurRepository $listeValeurRepository): Response
    {
        $vehicule = new Vehicule();
        $vehicule->setEtat($listeValeurRepository->findOneBy([
            'categorie' => CategorieListeValeur::ETAT_VEHICULE,
            'code' => 'en_service',
        ]));
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicule);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté au parc automobile.');

            return $this->redirectToRoute('admin_vehicule_index');
        }

        return $this->render('admin/vehicule/new.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Vehicule $vehicule): Response
    {
        return $this->render('admin/vehicule/show.html.twig', [
            'vehicule' => $vehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Vehicule $vehicule, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Véhicule mis à jour.');

            return $this->redirectToRoute('admin_vehicule_index');
        }

        return $this->render('admin/vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-vehicule-'.$vehicule->getId(), $request->request->get('_token'))) {
            $em->remove($vehicule);
            $em->flush();
            $this->addFlash('success', 'Véhicule supprimé.');
        }

        return $this->redirectToRoute('admin_vehicule_index');
    }
}
