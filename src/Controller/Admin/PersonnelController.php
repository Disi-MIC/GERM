<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Personnel;
use App\Form\PersonnelType;
use App\Repository\PersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/personnel', name: 'admin_personnel_')]
#[IsGranted('ROLE_SUPERADMIN')]
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $personnel = new Personnel();
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function edit(Request $request, Personnel $personnel, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
            if (!$personnel->getHistoriqueAffectations()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer cette fiche : elle a un historique de carrière.');
            } else {
                $em->remove($personnel);
                $em->flush();
                $this->addFlash('success', 'Fiche personnel supprimée.');
            }
        }

        return $this->redirectToRoute('admin_personnel_index');
    }
}
