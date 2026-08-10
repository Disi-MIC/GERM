<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\StatutDemande;
use App\Entity\Personnel;
use App\Entity\PieceJustificativeDecision;
use App\Form\DemandeDecisionType;
use App\Repository\DemandeDecisionRepository;
use App\Repository\PersonnelRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Demandes de décision de congé des agents. En attendant un espace agent en
 * libre-service, ces demandes sont saisies et traitées (approuvées/refusées) par
 * le superadmin. Approuver crée la DecisionConge correspondante.
 */
#[IsGranted('ROLE_SUPERADMIN')]
class DemandeDecisionController extends AbstractController
{
    #[Route('/admin/demandes-decision', name: 'admin_demande_decision_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeDecisionRepository $demandeRepository,
        PersonnelRepository $personnelRepository,
    ): Response {
        $filtrePersonnel = $request->query->get('personnel')
            ? $personnelRepository->find($request->query->get('personnel'))
            : null;
        $filtreStatut = $request->query->get('statut')
            ? StatutDemande::tryFrom($request->query->get('statut'))
            : null;

        return $this->render('admin/demande_decision/index.html.twig', [
            'demandes' => $demandeRepository->search($filtrePersonnel, $filtreStatut),
            'personnels' => $personnelRepository->search(null),
            'statuts' => StatutDemande::cases(),
            'filtre_personnel' => $filtrePersonnel,
            'filtre_statut' => $filtreStatut,
        ]);
    }

    #[Route('/admin/demandes-decision/new', name: 'admin_demande_decision_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        $demande = new DemandeDecision();
        $form = $this->createForm(DemandeDecisionType::class, $demande, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPieces($form, $demande, $uploader, $em);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de décision enregistrée.');

            return $this->redirectToRoute('admin_demande_decision_index');
        }

        return $this->render('admin/demande_decision/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_demande_decision_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/demande-decision/new', name: 'admin_personnel_demande_decision_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        $demande = new DemandeDecision();
        $demande->setPersonnel($personnel);
        $form = $this->createForm(DemandeDecisionType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherPieces($form, $demande, $uploader, $em);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de décision enregistrée.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/demande_decision/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/demande-decision/{id}/traiter', name: 'admin_demande_decision_traiter', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeDecision $demande, Request $request, EntityManagerInterface $em): Response
    {
        if (!$demande->isEnAttente()) {
            $this->addFlash('danger', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_decision_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('traiter-demande-decision-'.$demande->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

                return $this->redirectToRoute('admin_demande_decision_traiter', ['id' => $demande->getId()]);
            }

            $decisionAction = $request->request->get('decision');
            $commentaire = $request->request->get('commentaire') ?: null;

            if ('approuver' === $decisionAction) {
                $numero = trim((string) $request->request->get('numero_decision'));
                $dateDecision = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date_decision')) ?: null;
                $dateExpiration = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date_expiration')) ?: null;

                if ('' === $numero || null === $dateDecision || null === $dateExpiration || $dateExpiration <= $dateDecision) {
                    $this->addFlash('danger', "Merci de renseigner un numéro et des dates valides (date d'expiration postérieure à la date d'octroi) pour approuver.");

                    return $this->redirectToRoute('admin_demande_decision_traiter', ['id' => $demande->getId()]);
                }

                $nouvelleDecision = new DecisionConge();
                $nouvelleDecision->setPersonnel($demande->getPersonnel());
                $nouvelleDecision->setNumeroDecision($numero);
                $nouvelleDecision->setDateDecision($dateDecision);
                $nouvelleDecision->setDateExpiration($dateExpiration);
                $em->persist($nouvelleDecision);

                $demande->setDecisionCreee($nouvelleDecision);
                $demande->setStatut(StatutDemande::APPROUVEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande approuvée, la décision a été enregistrée.');

                return $this->redirectToRoute('admin_demande_decision_index');
            }

            if ('refuser' === $decisionAction) {
                $demande->setStatut(StatutDemande::REFUSEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande refusée.');

                return $this->redirectToRoute('admin_demande_decision_index');
            }
        }

        return $this->render('admin/demande_decision/traiter.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/admin/demande-decision/{id}/delete', name: 'admin_demande_decision_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(DemandeDecision $demande, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
    {
        if ($this->isCsrfTokenValid('delete-demande-decision-'.$demande->getId(), $request->request->get('_token'))) {
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

        return $this->redirectToRoute('admin_demande_decision_index');
    }

    #[Route('/admin/piece-justificative/decision/{id}/download', name: 'admin_piece_decision_download', requirements: ['id' => '\d+'])]
    public function downloadPiece(PieceJustificativeDecision $piece, FileStorage $uploader): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($uploader, $piece) {
            fpassthru($uploader->readStream($piece->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $uploader->mimeType($piece->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $piece->getNomOriginal()));

        return $response;
    }

    private function attacherPieces(FormInterface $form, DemandeDecision $demande, FileStorage $uploader, EntityManagerInterface $em): void
    {
        foreach (['pieceJustificative1', 'pieceJustificative2'] as $champ) {
            $file = $form->get($champ)->getData();
            if ($file instanceof UploadedFile) {
                $stocke = $uploader->store($file, 'decision');
                $piece = new PieceJustificativeDecision();
                $piece->setDemande($demande);
                $piece->setCheminFichier($stocke['path']);
                $piece->setNomOriginal($stocke['originalName']);
                $em->persist($piece);
            }
        }
    }
}
