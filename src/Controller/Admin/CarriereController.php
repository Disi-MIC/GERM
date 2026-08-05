<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Personnel;
use App\Form\HistoriqueAffectationType;
use App\Repository\HistoriqueAffectationRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des carrières : journal des mouvements (nominations, mutations, promotions)
 * des agents du Ministère.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class CarriereController extends AbstractController
{
    #[Route('/admin/carrieres', name: 'admin_carriere_index', methods: ['GET'])]
    public function index(
        Request $request,
        HistoriqueAffectationRepository $historiqueRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $filtreService = $request->query->get('service')
            ? $serviceRepository->find($request->query->get('service'))
            : null;
        $filtreType = $request->query->get('type')
            ? TypeMouvementCarriere::tryFrom($request->query->get('type'))
            : null;
        $dateDebut = $request->query->get('date_debut')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_debut')) ?: null
            : null;
        $dateFin = $request->query->get('date_fin')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_fin')) ?: null
            : null;

        return $this->render('admin/carriere/index.html.twig', [
            'mouvements' => $historiqueRepository->search($filtreService, $filtreType, $dateDebut, $dateFin),
            'services' => $serviceRepository->findBy([], ['nom' => 'ASC']),
            'types' => TypeMouvementCarriere::cases(),
            'filtre_service' => $filtreService,
            'filtre_type' => $filtreType,
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
        ]);
    }

    #[Route('/admin/personnel/{id}/mouvement/new', name: 'admin_personnel_mouvement_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function mouvementNew(
        Personnel $personnel,
        Request $request,
        EntityManagerInterface $em,
        HistoriqueAffectationRepository $historiqueRepository,
    ): Response {
        $mouvement = new HistoriqueAffectation();
        $mouvement->setPersonnel($personnel);
        $form = $this->createForm(HistoriqueAffectationType::class, $mouvement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($mouvement);
            $em->flush();

            $courant = $historiqueRepository->findCurrentForPersonnel($personnel);
            if ($courant) {
                $personnel->setService($courant->getService());
                $personnel->setFonction($courant->getFonction());
                $personnel->setGrade($courant->getGrade());
                $em->flush();
            }

            $this->addFlash('success', 'Mouvement de carrière enregistré.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/carriere/mouvement_new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
        ]);
    }

    #[Route('/admin/mouvement/{id}/delete', name: 'admin_carriere_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        HistoriqueAffectation $mouvement,
        Request $request,
        EntityManagerInterface $em,
        HistoriqueAffectationRepository $historiqueRepository,
    ): Response {
        $personnel = $mouvement->getPersonnel();

        if ($this->isCsrfTokenValid('delete-mouvement-'.$mouvement->getId(), $request->request->get('_token'))) {
            $courant = $historiqueRepository->findCurrentForPersonnel($personnel);

            if ($courant && $courant->getId() === $mouvement->getId()) {
                $this->addFlash('danger', 'Impossible de supprimer le mouvement en cours : ajoutez un nouveau mouvement, ou supprimez d\'abord les mouvements plus récents.');
            } else {
                $em->remove($mouvement);
                $em->flush();
                $this->addFlash('success', 'Mouvement supprimé.');
            }
        }

        return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
    }
}
