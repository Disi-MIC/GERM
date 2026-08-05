<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\Personnel;
use App\Form\DecisionCongeType;
use App\Repository\DecisionCongeRepository;
use App\Repository\PersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Décisions de congé (annuel) des agents. Créées automatiquement à l'approbation
 * d'une demande de décision, ou directement pour enregistrer une décision papier
 * antérieure à l'application.
 */
#[IsGranted('ROLE_RH_CONGE')]
class DecisionCongeController extends AbstractController
{
    #[Route('/admin/decisions-conge', name: 'admin_decision_conge_index', methods: ['GET'])]
    public function index(DecisionCongeRepository $decisionRepository, PersonnelRepository $personnelRepository): Response
    {
        return $this->render('admin/decision_conge/index.html.twig', [
            'decisions' => $decisionRepository->findBy([], ['dateExpiration' => 'DESC']),
        ]);
    }

    #[Route('/admin/decisions-conge/new', name: 'admin_decision_conge_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em): Response
    {
        $decision = new DecisionConge();
        $form = $this->createForm(DecisionCongeType::class, $decision, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($decision);
            $em->flush();

            $this->addFlash('success', 'Décision de congé enregistrée.');

            return $this->redirectToRoute('admin_decision_conge_index');
        }

        return $this->render('admin/decision_conge/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_decision_conge_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/decision-conge/new', name: 'admin_personnel_decision_conge_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em): Response
    {
        $decision = new DecisionConge();
        $decision->setPersonnel($personnel);
        $form = $this->createForm(DecisionCongeType::class, $decision);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($decision);
            $em->flush();

            $this->addFlash('success', 'Décision de congé enregistrée.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/decision_conge/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/decision-conge/{id}/edit', name: 'admin_decision_conge_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(DecisionConge $decision, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DecisionCongeType::class, $decision);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Décision de congé mise à jour.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $decision->getPersonnel()->getId()]);
        }

        return $this->render('admin/decision_conge/edit.html.twig', [
            'personnel' => $decision->getPersonnel(),
            'decision' => $decision,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $decision->getPersonnel()->getId()]),
        ]);
    }

    #[Route('/admin/decision-conge/{id}/delete', name: 'admin_decision_conge_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(DecisionConge $decision, Request $request, EntityManagerInterface $em): Response
    {
        $personnel = $decision->getPersonnel();

        if ($this->isCsrfTokenValid('delete-decision-conge-'.$decision->getId(), $request->request->get('_token'))) {
            if (!$decision->getDemandesJouissance()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer cette décision : des demandes de jouissance s\'y rattachent.');
            } else {
                $em->remove($decision);
                $em->flush();
                $this->addFlash('success', 'Décision de congé supprimée.');
            }
        }

        return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
    }
}
