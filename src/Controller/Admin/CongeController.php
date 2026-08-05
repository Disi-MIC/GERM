<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Conge;
use App\Entity\Enum\TypeConge;
use App\Entity\Personnel;
use App\Form\CongeType;
use App\Repository\CongeRepository;
use App\Repository\PersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des congés du personnel.
 */
#[IsGranted('ROLE_SUPERADMIN')]
class CongeController extends AbstractController
{
    #[Route('/admin/conges', name: 'admin_conge_index', methods: ['GET'])]
    public function index(
        Request $request,
        CongeRepository $congeRepository,
        PersonnelRepository $personnelRepository,
    ): Response {
        $filtrePersonnel = $request->query->get('personnel')
            ? $personnelRepository->find($request->query->get('personnel'))
            : null;
        $filtreType = $request->query->get('type')
            ? TypeConge::tryFrom($request->query->get('type'))
            : null;
        $dateDebut = $request->query->get('date_debut')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_debut')) ?: null
            : null;
        $dateFin = $request->query->get('date_fin')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_fin')) ?: null
            : null;

        return $this->render('admin/conge/index.html.twig', [
            'conges' => $congeRepository->search($filtrePersonnel, $filtreType, $dateDebut, $dateFin),
            'personnels' => $personnelRepository->search(null),
            'types' => TypeConge::cases(),
            'filtre_personnel' => $filtrePersonnel,
            'filtre_type' => $filtreType,
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
        ]);
    }

    #[Route('/admin/conges/new', name: 'admin_conge_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em): Response
    {
        $conge = new Conge();
        $form = $this->createForm(CongeType::class, $conge, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($conge);
            $em->flush();

            $this->addFlash('success', 'Congé enregistré.');

            return $this->redirectToRoute('admin_conge_index');
        }

        return $this->render('admin/conge/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_conge_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/conge/new', name: 'admin_personnel_conge_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em): Response
    {
        $conge = new Conge();
        $conge->setPersonnel($personnel);
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($conge);
            $em->flush();

            $this->addFlash('success', 'Congé enregistré.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/conge/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/conge/{id}/edit', name: 'admin_conge_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Conge $conge, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Congé mis à jour.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $conge->getPersonnel()->getId()]);
        }

        return $this->render('admin/conge/edit.html.twig', [
            'personnel' => $conge->getPersonnel(),
            'conge' => $conge,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $conge->getPersonnel()->getId()]),
        ]);
    }

    #[Route('/admin/conge/{id}/delete', name: 'admin_conge_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Conge $conge, Request $request, EntityManagerInterface $em): Response
    {
        $personnel = $conge->getPersonnel();

        if ($this->isCsrfTokenValid('delete-conge-'.$conge->getId(), $request->request->get('_token'))) {
            $em->remove($conge);
            $em->flush();
            $this->addFlash('success', 'Congé supprimé.');
        }

        return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
    }
}
