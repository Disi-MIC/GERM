<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Delegation;
use App\Entity\Enum\StatutDelegation;
use App\Form\DelegationType;
use App\Repository\DelegationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Délégation temporaire d'un rôle RH d'un agent à un autre, en cas d'absence.
 * Réservé aux titulaires de ROLE_ADMIN_RH (et ROLE_SUPERADMIN, via la
 * hiérarchie de rôles) : un titulaire d'un seul rôle rubrique ne peut pas
 * déléguer lui-même.
 */
#[IsGranted('ROLE_ADMIN_RH')]
class DelegationController extends AbstractController
{
    #[Route('/admin/delegations', name: 'admin_delegation_index', methods: ['GET'])]
    public function index(DelegationRepository $repository): Response
    {
        return $this->render('admin/delegation/index.html.twig', [
            'delegations' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/delegations/new', name: 'admin_delegation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $delegation = new Delegation();
        $delegation->setDelegant($this->getUser());
        $form = $this->createForm(DelegationType::class, $delegation, ['delegant' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($delegation);
            $em->flush();

            $this->addFlash('success', 'Délégation enregistrée.');

            return $this->redirectToRoute('admin_delegation_index');
        }

        return $this->render('admin/delegation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/delegation/{id}/revoke', name: 'admin_delegation_revoke', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revoke(Delegation $delegation, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('revoke-delegation-'.$delegation->getId(), $request->request->get('_token'))) {
            $delegation->setStatut(StatutDelegation::REVOQUEE);
            $em->flush();
            $this->addFlash('success', 'Délégation révoquée.');
        }

        return $this->redirectToRoute('admin_delegation_index');
    }
}
