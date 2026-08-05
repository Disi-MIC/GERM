<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\DemandeCartePro;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemande;
use App\Entity\Personnel;
use App\Form\DemandeCarteProType;
use App\Repository\DemandeCarteProRepository;
use App\Repository\PersonnelRepository;
use App\Service\PieceJustificativeUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Demandes de carte professionnelle des agents : nouvelle carte, renouvellement, ou
 * déclaration de perte/vol. En attendant un espace agent en libre-service, ces
 * demandes sont saisies et traitées (approuvées/refusées) par le superadmin.
 * Approuver crée la CarteProfessionnelle correspondante, sans modifier automatiquement
 * le statut de l'éventuelle ancienne carte référencée (laissé à l'admin).
 */
#[IsGranted('ROLE_RH_CARTE_PRO')]
class DemandeCarteProController extends AbstractController
{
    #[Route('/admin/demandes-carte-pro', name: 'admin_demande_carte_pro_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeCarteProRepository $demandeRepository,
        PersonnelRepository $personnelRepository,
    ): Response {
        $filtrePersonnel = $request->query->get('personnel')
            ? $personnelRepository->find($request->query->get('personnel'))
            : null;
        $filtreStatut = $request->query->get('statut')
            ? StatutDemande::tryFrom($request->query->get('statut'))
            : null;

        return $this->render('admin/demande_carte_pro/index.html.twig', [
            'demandes' => $demandeRepository->search($filtrePersonnel, $filtreStatut),
            'personnels' => $personnelRepository->search(null),
            'statuts' => StatutDemande::cases(),
            'filtre_personnel' => $filtrePersonnel,
            'filtre_statut' => $filtreStatut,
        ]);
    }

    #[Route('/admin/demandes-carte-pro/new', name: 'admin_demande_carte_pro_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $demande = new DemandeCartePro();
        $form = $this->createForm(DemandeCarteProType::class, $demande, ['include_personnel' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherFichier($form, $demande, $uploader);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de carte professionnelle enregistrée.');

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        return $this->render('admin/demande_carte_pro/new.html.twig', [
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_demande_carte_pro_index'),
        ]);
    }

    #[Route('/admin/personnel/{id}/demande-carte-pro/new', name: 'admin_personnel_demande_carte_pro_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        $demande = new DemandeCartePro();
        $demande->setPersonnel($personnel);
        $form = $this->createForm(DemandeCarteProType::class, $demande, ['personnel' => $personnel]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->attacherFichier($form, $demande, $uploader);
            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'Demande de carte professionnelle enregistrée.');

            return $this->redirectToRoute('admin_personnel_show', ['id' => $personnel->getId()]);
        }

        return $this->render('admin/demande_carte_pro/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
            'cancel_path' => $this->generateUrl('admin_personnel_show', ['id' => $personnel->getId()]),
        ]);
    }

    #[Route('/admin/demande-carte-pro/{id}/traiter', name: 'admin_demande_carte_pro_traiter', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeCartePro $demande, Request $request, EntityManagerInterface $em): Response
    {
        if (!$demande->isEnAttente()) {
            $this->addFlash('danger', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('traiter-demande-carte-pro-'.$demande->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

                return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
            }

            $decisionAction = $request->request->get('decision');
            $commentaire = $request->request->get('commentaire') ?: null;

            if ('approuver' === $decisionAction) {
                $numero = trim((string) $request->request->get('numero'));
                $dateDelivrance = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date_delivrance')) ?: null;
                $dateExpiration = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date_expiration')) ?: null;

                if ('' === $numero || null === $dateDelivrance || null === $dateExpiration || $dateExpiration <= $dateDelivrance) {
                    $this->addFlash('danger', "Merci de renseigner un numéro et des dates valides (date d'expiration postérieure à la date de délivrance) pour approuver.");

                    return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
                }

                $nouvelleCarte = new CarteProfessionnelle();
                $nouvelleCarte->setPersonnel($demande->getPersonnel());
                $nouvelleCarte->setNumero($numero);
                $nouvelleCarte->setDateDelivrance($dateDelivrance);
                $nouvelleCarte->setDateExpiration($dateExpiration);
                $nouvelleCarte->setStatut(StatutCarteProfessionnelle::VALIDE);
                $em->persist($nouvelleCarte);

                $demande->setCarteCreee($nouvelleCarte);
                $demande->setStatut(StatutDemande::APPROUVEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande approuvée, la carte professionnelle a été enregistrée.');

                return $this->redirectToRoute('admin_demande_carte_pro_index');
            }

            if ('refuser' === $decisionAction) {
                $demande->setStatut(StatutDemande::REFUSEE);
                $demande->setDateTraitement(new \DateTimeImmutable());
                $demande->setCommentaireTraitement($commentaire);
                $em->flush();

                $this->addFlash('success', 'Demande refusée.');

                return $this->redirectToRoute('admin_demande_carte_pro_index');
            }
        }

        return $this->render('admin/demande_carte_pro/traiter.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/admin/demande-carte-pro/{id}/delete', name: 'admin_demande_carte_pro_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(DemandeCartePro $demande, Request $request, EntityManagerInterface $em, PieceJustificativeUploader $uploader): Response
    {
        if ($this->isCsrfTokenValid('delete-demande-carte-pro-'.$demande->getId(), $request->request->get('_token'))) {
            if (!$demande->isEnAttente()) {
                $this->addFlash('danger', 'Impossible de supprimer une demande déjà traitée.');
            } else {
                if ($demande->getCheminFichier()) {
                    $uploader->delete($demande->getCheminFichier());
                }
                $em->remove($demande);
                $em->flush();
                $this->addFlash('success', 'Demande supprimée.');
            }
        }

        return $this->redirectToRoute('admin_demande_carte_pro_index');
    }

    #[Route('/admin/piece-justificative/carte-pro/{id}/download', name: 'admin_piece_carte_pro_download', requirements: ['id' => '\d+'])]
    public function downloadPiece(DemandeCartePro $demande, PieceJustificativeUploader $uploader): BinaryFileResponse
    {
        if (!$demande->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($uploader->absolutePath($demande->getCheminFichier()));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $demande->getNomOriginal() ?? 'piece-justificative');

        return $response;
    }

    private function attacherFichier(FormInterface $form, DemandeCartePro $demande, PieceJustificativeUploader $uploader): void
    {
        $file = $form->get('fichier')->getData();

        if (!$file instanceof UploadedFile) {
            return;
        }

        $stocke = $uploader->store($file, 'demande-carte-pro');
        $demande->setCheminFichier($stocke['path']);
        $demande->setNomOriginal($stocke['originalName']);
    }
}
