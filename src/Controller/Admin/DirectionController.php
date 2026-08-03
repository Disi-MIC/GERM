<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Direction;
use App\Form\DirectionType;
use App\Repository\DirectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/direction', name: 'admin_direction_')]
#[IsGranted('ROLE_SUPERADMIN')]
class DirectionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(DirectionRepository $repository): Response
    {
        return $this->render('admin/direction/index.html.twig', [
            'directions' => $repository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $direction = new Direction();
        $form = $this->createForm(DirectionType::class, $direction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($direction);
            $em->flush();

            $this->addFlash('success', 'Direction créée.');

            return $this->redirectToRoute('admin_direction_index');
        }

        return $this->render('admin/direction/new.html.twig', [
            'direction' => $direction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Direction $direction, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DirectionType::class, $direction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Direction mise à jour.');

            return $this->redirectToRoute('admin_direction_index');
        }

        return $this->render('admin/direction/edit.html.twig', [
            'direction' => $direction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Direction $direction, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-direction-'.$direction->getId(), $request->request->get('_token'))) {
            if (!$direction->getServices()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer cette direction : des services y sont encore rattachés.');
            } else {
                $em->remove($direction);
                $em->flush();
                $this->addFlash('success', 'Direction supprimée.');
            }
        }

        return $this->redirectToRoute('admin_direction_index');
    }
}
