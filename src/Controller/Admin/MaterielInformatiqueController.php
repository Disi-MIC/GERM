<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\MaterielInformatique;
use App\Form\MaterielInformatiqueType;
use App\Repository\ListeValeurRepository;
use App\Repository\MaterielInformatiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/materiel', name: 'admin_materiel_')]
#[IsGranted('ROLE_SUPERADMIN')]
class MaterielInformatiqueController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, MaterielInformatiqueRepository $repository): Response
    {
        return $this->render('admin/materiel/index.html.twig', [
            'materiels' => $repository->search($request->query->get('q')),
            'q' => $request->query->get('q'),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ListeValeurRepository $listeValeurRepository): Response
    {
        $materiel = new MaterielInformatique();
        $materiel->setEtat($listeValeurRepository->findOneBy([
            'categorie' => CategorieListeValeur::ETAT_MATERIEL,
            'code' => 'en_stock',
        ]));
        $form = $this->createForm(MaterielInformatiqueType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($materiel);
            $em->flush();

            $this->addFlash('success', 'Matériel ajouté au parc informatique.');

            return $this->redirectToRoute('admin_materiel_index');
        }

        return $this->render('admin/materiel/new.html.twig', [
            'materiel' => $materiel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(MaterielInformatique $materiel): Response
    {
        return $this->render('admin/materiel/show.html.twig', [
            'materiel' => $materiel,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, MaterielInformatique $materiel, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MaterielInformatiqueType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Matériel mis à jour.');

            return $this->redirectToRoute('admin_materiel_index');
        }

        return $this->render('admin/materiel/edit.html.twig', [
            'materiel' => $materiel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, MaterielInformatique $materiel, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-materiel-'.$materiel->getId(), $request->request->get('_token'))) {
            $em->remove($materiel);
            $em->flush();
            $this->addFlash('success', 'Matériel supprimé.');
        }

        return $this->redirectToRoute('admin_materiel_index');
    }
}
