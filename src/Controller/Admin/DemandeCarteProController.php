<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\DemandeCartePro;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Enum\StatutDemandeCartePro;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Entity\Personnel;
use App\Entity\User;
use App\Form\DemandeCarteProType;
use App\Repository\DemandeCarteProRepository;
use App\Repository\PersonnelRepository;
use App\Service\CarteProfessionnellePdfStockageService;
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
 * Demandes de carte professionnelle des agents : nouvelle carte, renouvellement, ou
 * déclaration de perte/vol. En attendant un espace agent en libre-service, ces
 * demandes sont saisies et traitées (approuvées/refusées) par le superadmin.
 * Approuver crée la CarteProfessionnelle correspondante, sans modifier automatiquement
 * le statut de l'éventuelle ancienne carte référencée (laissé à l'admin).
 *
 * Ce module est désormais géré côté Angular (voir src/Controller/Api/
 * DemandeCarteProController.php) pour ROLE_RH_CARTE_PRO/ROLE_ADMIN_RH ;
 * cette interface Twig reste accessible uniquement au superadmin, en secours.
 */
#[IsGranted('ROLE_SUPERADMIN')]
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
            ? StatutDemandeCartePro::tryFrom($request->query->get('statut'))
            : null;

        return $this->render('admin/demande_carte_pro/index.html.twig', [
            'demandes' => $demandeRepository->search($filtrePersonnel, $filtreStatut),
            'personnels' => $personnelRepository->search(null),
            'statuts' => StatutDemandeCartePro::cases(),
            'filtre_personnel' => $filtrePersonnel,
            'filtre_statut' => $filtreStatut,
        ]);
    }

    #[Route('/admin/demandes-carte-pro/new', name: 'admin_demande_carte_pro_new', methods: ['GET', 'POST'])]
    public function newFromIndex(Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
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
    public function new(Personnel $personnel, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
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

    #[Route('/admin/demande-carte-pro/{id}/traiter', name: 'admin_demande_carte_pro_traiter', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function traiter(DemandeCartePro $demande): Response
    {
        if (!$demande->isEnAttente() && !$demande->isTransmise()) {
            $this->addFlash('danger', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        return $this->render('admin/demande_carte_pro/traiter.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/admin/demande-carte-pro/{id}/transmettre', name: 'admin_demande_carte_pro_transmettre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transmettre(DemandeCartePro $demande, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('traiter-demande-carte-pro-'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
        }

        if (!$demande->isEnAttente()) {
            $this->addFlash('danger', 'Cette demande a déjà été transmise ou traitée.');

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        $demande->setStatut(StatutDemandeCartePro::TRANSMISE);
        $em->flush();

        $this->addFlash('success', 'Demande transmise au RH Admin.');

        return $this->redirectToRoute('admin_demande_carte_pro_index');
    }

    #[Route('/admin/demande-carte-pro/{id}/rejeter', name: 'admin_demande_carte_pro_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(DemandeCartePro $demande, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('traiter-demande-carte-pro-'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
        }

        if ($demande->isTransmise()) {
            // Une fois transmise, seul le RH Admin peut encore rejeter (filet
            // de sécurité) — le RH Carte Pro ne peut plus revenir dessus.
            $this->denyAccessUnlessGranted('ROLE_ADMIN_RH');
        } elseif (!$demande->isEnAttente()) {
            $this->addFlash('danger', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        $demande->setStatut(StatutDemandeCartePro::REFUSEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setCommentaireTraitement($request->request->get('commentaire') ?: null);
        $em->flush();

        $this->addFlash('success', 'Demande refusée.');

        return $this->redirectToRoute('admin_demande_carte_pro_index');
    }

    #[Route('/admin/demande-carte-pro/{id}/approuver', name: 'admin_demande_carte_pro_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN_RH')]
    public function approuver(DemandeCartePro $demande, Request $request, EntityManagerInterface $em, CarteProfessionnellePdfStockageService $pdfStockage): Response
    {
        if (!$this->isCsrfTokenValid('traiter-demande-carte-pro-'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
        }

        if (!$demande->isTransmise()) {
            $this->addFlash('danger', "Cette demande doit d'abord être transmise avant de pouvoir être approuvée.");

            return $this->redirectToRoute('admin_demande_carte_pro_index');
        }

        if (\in_array($demande->getTypeDemande(), [TypeDemandeCartePro::NOUVELLE, TypeDemandeCartePro::RENOUVELLEMENT], true) && !$demande->getCheminFichier()) {
            $this->addFlash('danger', "Merci de joindre la pièce justificative avant d'approuver cette demande.");

            return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
        }

        $numero = trim((string) $request->request->get('numero'));
        $dateDelivrance = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('date_delivrance')) ?: null;

        if ('' === $numero || null === $dateDelivrance) {
            $this->addFlash('danger', 'Merci de renseigner un numéro et une date de délivrance valides pour approuver.');

            return $this->redirectToRoute('admin_demande_carte_pro_traiter', ['id' => $demande->getId()]);
        }

        /** @var User $validateur */
        $validateur = $this->getUser();

        $nouvelleCarte = new CarteProfessionnelle();
        $nouvelleCarte->setPersonnel($demande->getPersonnel());
        $nouvelleCarte->setNumero($numero);
        $nouvelleCarte->setDateDelivrance($dateDelivrance);
        $nouvelleCarte->setStatut(StatutCarteProfessionnelle::VALIDE);
        $nouvelleCarte->valider($validateur);
        $pdfStockage->genererEtStocker($nouvelleCarte);
        $em->persist($nouvelleCarte);

        $demande->setCarteCreee($nouvelleCarte);
        $demande->setStatut(StatutDemandeCartePro::APPROUVEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setCommentaireTraitement($request->request->get('commentaire') ?: null);
        $em->flush();

        $this->addFlash('success', 'Demande approuvée, la carte professionnelle a été créée et validée.');

        return $this->redirectToRoute('admin_demande_carte_pro_index');
    }

    #[Route('/admin/demande-carte-pro/{id}/delete', name: 'admin_demande_carte_pro_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(DemandeCartePro $demande, Request $request, EntityManagerInterface $em, FileStorage $uploader): Response
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
    public function downloadPiece(DemandeCartePro $demande, FileStorage $uploader): StreamedResponse
    {
        if (!$demande->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($uploader, $demande) {
            fpassthru($uploader->readStream($demande->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $uploader->mimeType($demande->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $demande->getNomOriginal() ?? 'piece-justificative'));

        return $response;
    }

    private function attacherFichier(FormInterface $form, DemandeCartePro $demande, FileStorage $uploader): void
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
