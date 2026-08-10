<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Conge;
use App\Entity\DemandeJouissance;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeConge;
use App\Entity\Personnel;
use App\Entity\PieceJustificativeJouissance;
use App\Form\DemandeJouissanceType;
use App\Repository\DemandeJouissanceRepository;
use App\Repository\PersonnelRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Demandes de jouissance de congé des agents. En attendant un espace agent en
 * libre-service, ces demandes sont saisies et traitées (approuvées/refusées) par
 * le superadmin.
 */
#[IsGranted('ROLE_SUPERADMIN')]
class DemandeJouissanceController extends AbstractController
{
    #[Route('/admin/demandes-jouissance', name: 'admin_demande_jouissance_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeJouissanceRepository $demandeRepository,
        PersonnelRepository $personnelRepository,
    ): Response {
        $filtrePersonnel = $request->query->get('personnel')
            ? $personnelRepository->find($request->query->get('personnel'))
            : null;
        $filtreType = $request->query->get('type')
            ? TypeConge::tryFrom($request->query->get('type'))
            : null;
        $filtreStatut = $request->query->get('statut')
            ? StatutDemande::tryFrom($request->query->get('statut'))
            : null;
        $dateDebut = $request->query->get('date_debut')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_debut')) ?: null
            : null;
        $dateFin = $request->query->get('date_fin')
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $request->query->get('date_fin')) ?: null
            : null;

        return $this->render('admin/demande_jouissance/index.html.twig', [
            'demandes' => $demandeRepository->search($filtrePersonnel, $filtreType, $filtreStatut, $dateDebut, $dateFin),
            'personnels' => $personnelRepository->search(null),
            'types' => TypeConge::cases(),
            'statuts' => StatutDemande::cases(),
            'filtre_personnel' => $filtrePersonnel,
            'filtre_type' => $filtreType,
            'filtre_statut' => $filtreStatut,
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
        ]);
    }

    #[Route('/admin/demandes-jouissance/new', name: 'admin_demande_jouissance_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        $demande = new DemandeJouissance();
        $form = $this->createForm(DemandeJouissanceType::class, $demande, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPieces($form, $demande, $uploader, $em);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de jouissance enregistrée.');

            return $this->redirectToRoute('admin_demande_jouissance_index');
        }

        return $this->render('admin/demande_jouissance/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_demande_jouissance_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/demande-jouissance/new', name: 'admin_personnel_demande_jouissance_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        $demande = new DemandeJouissance();
        $demande->setPersonnel($personnel);
        $form = $this->createForm(DemandeJouissanceType::class, $demande, ['personnel' => $personnel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPieces($form, $demande, $uploader, $em);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de jouissance enregistrée.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/demande_jouissance/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/demande-jouissance/{id}/traiter', name: 'admin_demande_jouissance_traiter', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeJouissance $demande, Request $request, EntityManagerInterface $em): Response
    {
        if (!$demande->isEnAttente()) {
            $this->addFlash('danger', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_jouissance_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('traiter-demande-jouissance-'.$demande->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

                return $this->redirectToRoute('admin_demande_jouissance_traiter', ['id' => $demande->getId()]);
            }

            $decision = $request->request->get('decision');
            $commentaire = $request->request->get('commentaire') ?: null;

            if ('approuver' === $decision) {
                $conge = new Conge();
                $conge->setPersonnel($demande->getPersonnel());
                $conge->setType($demande->getType());
                $conge->setDateDebut($demande->getDateDebut());
                $conge->setDateFin($demande->getDateFin());
                $conge->setMotif($demande->getMotif());
                $em->persist($conge);

                $demande->setConge($conge);
                $demande->setStatut(StatutDemande::APPROUVEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande approuvée, le congé a été enregistré.');

                return $this->redirectToRoute('admin_demande_jouissance_index');
            }

            if ('refuser' === $decision) {
                $demande->setStatut(StatutDemande::REFUSEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande refusée.');

                return $this->redirectToRoute('admin_demande_jouissance_index');
            }
        }

        return $this->render('admin/demande_jouissance/traiter.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/admin/demande-jouissance/{id}/delete', name: 'admin_demande_jouissance_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(DemandeJouissance $demande, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        if ($this->isCsrfTokenValid('delete-demande-jouissance-'.$demande->getId(), $request->request->get('_token'))) {
            if (!$demande->isEnAttente()) {
                $this->addFlash('danger', 'Impossible de supprimer une demande déjà traitée.');
            } else {
                foreach ($demande->getPieces() as $piece) {
                    $uploader->delete($piece->getCheminFichier());
                }
                $em->remove($demande);
                $em->flush();
                $this->addFlash('success', 'Demande supprimée.');
            }
        }

        return $this->redirectToRoute('admin_demande_jouissance_index');
    }

    #[Route('/admin/piece-justificative/jouissance/{id}/download', name: 'admin_piece_jouissance_download', requirements: ['id' => '\d+'])]
    public function downloadPiece(PieceJustificativeJouissance $piece, FileStorage $uploader): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($uploader, $piece) {
            fpassthru($uploader->readStream($piece->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $uploader->mimeType($piece->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $piece->getNomOriginal()));

        return $response;
    }

    private function attacherPieces(FormInterface $form, DemandeJouissance $demande, FileStorage $uploader, EntityManagerInterface $em): void
    {
        foreach (['pieceJustificative1', 'pieceJustificative2'] as $champ) {
            $file = $form->get($champ)->getData();
            if ($file instanceof UploadedFile) {
                $stocke = $uploader->store($file, 'jouissance');
                $piece = new PieceJustificativeJouissance();
                $piece->setDemande($demande);
                $piece->setCheminFichier($stocke['path']);
                $piece->setNomOriginal($stocke['originalName']);
                $em->persist($piece);
            }
        }
    }
}
