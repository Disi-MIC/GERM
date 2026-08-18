<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Entity\DocumentAdministratif;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Notification;
use App\Entity\Personnel;
use App\Entity\User;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\CongeRepository;
use App\Repository\DemandeCarteProRepository;
use App\Repository\DocumentAdministratifRepository;
use App\Repository\DemandeDecisionRepository;
use App\Repository\DemandeJouissanceRepository;
use App\Repository\HistoriqueAffectationRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\NotificationRepository;
use App\Repository\TicketIncidentRepository;
use App\Repository\VehiculeRepository;
use App\Service\FileStorage;
use App\Service\PersonnelPhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Espace libre-service de l'utilisateur connecté ("Mon espace") : profil
 * personnel, carrière, parc informatique/automobile affecté, congés et carte
 * professionnelle — tout en lecture seule, filtré sur sa propre fiche
 * Personnel. Accessible à tout agent (ROLE_AGENT, le rôle de base de tout
 * compte), y compris ceux sans rôle RH. Utilisé aussi juste après la
 * connexion pour savoir vers quelle rubrique se rediriger (/api/me).
 */
#[IsGranted('ROLE_AGENT')]
class MeController extends AbstractController
{
    public function __construct(
        private readonly FileStorage $fileStorage,
        private readonly EntityManagerInterface $em,
        private readonly PersonnelPhotoService $photoService,
    ) {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function moi(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/me/personnel', name: 'api_me_personnel', methods: ['GET'])]
    public function personnel(): JsonResponse
    {
        $personnel = $this->personnelConnecte();

        if (!$personnel) {
            return $this->json(['errors' => ['personnel' => "Aucune fiche personnel n'est liée à votre compte. Merci de contacter le service RH."]], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/personnel/photo', name: 'api_me_personnel_photo', methods: ['GET'])]
    public function personnelPhoto(): StreamedResponse
    {
        $personnel = $this->personnelConnecte();

        if (!$personnel || !$personnel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($personnel) {
            fpassthru($this->fileStorage->readStream($personnel->getPhoto()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($personnel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    #[Route('/api/me/personnel/photo', name: 'api_me_personnel_photo_upload', methods: ['POST'])]
    public function personnelPhotoUpload(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();

        if (!$personnel) {
            return $this->json(['errors' => ['personnel' => "Aucune fiche personnel n'est liée à votre compte. Merci de contacter le service RH."]], JsonResponse::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('photoFichier');

        if ($erreur = $this->photoService->erreurValidation($file)) {
            return $this->json(['errors' => ['photoFichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->photoService->remplacer($personnel, $file);

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * Résumé pour la rubrique "Mon tableau de bord" : compteurs d'activité
     * (carrière/congés/parc affecté) et une vue unifiée des demandes en
     * attente (carte pro/décision/jouissance confondues) avec leur état,
     * plus une répartition globale par statut pour l'affichage interactif
     * côté Angular. Toujours filtré sur la fiche Personnel du compte
     * connecté ; renvoie des compteurs à zéro (pas d'erreur) si aucune
     * fiche n'est liée, pour ne pas casser l'affichage du tableau de bord.
     */
    #[Route('/api/me/dashboard', name: 'api_me_dashboard', methods: ['GET'])]
    public function dashboard(
        HistoriqueAffectationRepository $historiqueRepository,
        CongeRepository $congeRepository,
        MaterielInformatiqueRepository $materielRepository,
        VehiculeRepository $vehiculeRepository,
        CarteProfessionnelleRepository $carteRepository,
        DemandeCarteProRepository $demandeCarteRepository,
        DemandeDecisionRepository $demandeDecisionRepository,
        DemandeJouissanceRepository $demandeJouissanceRepository,
    ): JsonResponse {
        $personnel = $this->personnelConnecte();

        if (!$personnel) {
            return $this->json([
                'carriere' => ['nbMouvements' => 0, 'dernierMouvement' => null],
                'conges' => ['total' => 0, 'totalJours' => 0],
                'materiels' => ['total' => 0],
                'vehicules' => ['total' => 0],
                'carteActive' => null,
                'demandesEnAttente' => [],
                'repartitionDemandes' => ['en_attente' => 0, 'transmise' => 0, 'approuvee' => 0, 'refusee' => 0],
            ]);
        }

        $mouvements = $historiqueRepository->findBy(['personnel' => $personnel], ['dateEffet' => 'DESC']);
        $conges = $congeRepository->findBy(['personnel' => $personnel]);
        $materiels = $materielRepository->findBy(['affecteA' => $personnel]);
        $vehicules = $vehiculeRepository->findBy(['chauffeurAffecte' => $personnel]);
        $cartes = $carteRepository->findBy(['personnel' => $personnel], ['dateDelivrance' => 'DESC']);

        $carteActive = null;
        foreach ($cartes as $carte) {
            if (StatutCarteProfessionnelle::VALIDE === $carte->getStatut()) {
                $carteActive = $carte;
                break;
            }
        }

        $repartition = ['en_attente' => 0, 'transmise' => 0, 'approuvee' => 0, 'refusee' => 0];
        $enAttente = [];

        foreach ($demandeCarteRepository->findBy(['personnel' => $personnel]) as $demande) {
            $statut = $demande->getStatut()->value;
            ++$repartition[$statut];
            if (\in_array($statut, ['en_attente', 'transmise'], true)) {
                $enAttente[] = [
                    'domaine' => 'carte_pro',
                    'id' => $demande->getId(),
                    'libelle' => 'Demande de carte professionnelle',
                    'statut' => $statut,
                    'createdAt' => $demande->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }
        }

        foreach ($demandeDecisionRepository->findBy(['personnel' => $personnel]) as $demande) {
            $statut = $demande->getStatut()->value;
            // DemandeDecision suit un circuit à 5 états (voir StatutDemande) plus
            // fin que les 4 catégories de ce widget, partagées avec DemandeCartePro
            // et DemandeJouissance : les états intermédiaires du circuit papier
            // rejoignent "transmise" (en cours, non résolu), l'état terminal
            // "transmise_agent" rejoint "approuvee" (résolu favorablement).
            $statutRepartition = match ($statut) {
                'validee', 'deposee_courrier', 'retournee' => 'transmise',
                'transmise_agent' => 'approuvee',
                default => $statut,
            };
            ++$repartition[$statutRepartition];
            if ('en_attente' === $statut) {
                $enAttente[] = [
                    'domaine' => 'decision',
                    'id' => $demande->getId(),
                    'libelle' => 'Demande de décision de congé',
                    'statut' => $statut,
                    'createdAt' => $demande->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }
        }

        foreach ($demandeJouissanceRepository->findBy(['personnel' => $personnel]) as $demande) {
            $statut = $demande->getStatut()->value;
            ++$repartition[$statut];
            if ('en_attente' === $statut) {
                $enAttente[] = [
                    'domaine' => 'jouissance',
                    'id' => $demande->getId(),
                    'libelle' => 'Demande de jouissance de congé',
                    'statut' => $statut,
                    'createdAt' => $demande->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }
        }

        usort($enAttente, fn (array $a, array $b) => $b['createdAt'] <=> $a['createdAt']);

        return $this->json([
            'carriere' => [
                'nbMouvements' => \count($mouvements),
                'dernierMouvement' => $mouvements[0] ?? null,
            ],
            'conges' => [
                'total' => \count($conges),
                'totalJours' => array_sum(array_map(static fn ($c) => $c->getDuree() ?? 0, $conges)),
            ],
            'materiels' => ['total' => \count($materiels)],
            'vehicules' => ['total' => \count($vehicules)],
            'carteActive' => $carteActive,
            'demandesEnAttente' => $enAttente,
            'repartitionDemandes' => $repartition,
        ], JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/historique-affectations', name: 'api_me_historique_affectations', methods: ['GET'])]
    public function carriere(HistoriqueAffectationRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $mouvements = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateEffet' => 'DESC']) : [];

        return $this->json($mouvements, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/conges', name: 'api_me_conges', methods: ['GET'])]
    public function conges(CongeRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $conges = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateDebut' => 'DESC']) : [];

        return $this->json($conges, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/cartes-professionnelles', name: 'api_me_cartes_professionnelles', methods: ['GET'])]
    public function cartesProfessionnelles(CarteProfessionnelleRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $cartes = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateDelivrance' => 'DESC']) : [];

        return $this->json($cartes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * Permet à l'agent de consulter/télécharger sa propre carte validée (même
     * routes que côté RH — Api/CarteProfessionnelleController::pdf()/
     * pdfTelecharger() — mais scoping par propriété plutôt que par rôle
     * RH_CARTE_PRO). `pdf` renvoie le PDF en "inline" pour l'aperçu intégré
     * (iframe) ; `pdfTelecharger` force le téléchargement.
     */
    #[Route('/api/me/cartes-professionnelles/{id}/pdf', name: 'api_me_carte_professionnelle_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function cartePdf(CarteProfessionnelle $carte): StreamedResponse
    {
        return $this->streamCartePdf($carte, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/api/me/cartes-professionnelles/{id}/pdf/telecharger', name: 'api_me_carte_professionnelle_pdf_telecharger', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function cartePdfTelecharger(CarteProfessionnelle $carte): StreamedResponse
    {
        return $this->streamCartePdf($carte, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function streamCartePdf(CarteProfessionnelle $carte, string $disposition): StreamedResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel || $carte->getPersonnel() !== $personnel) {
            throw $this->createAccessDeniedException();
        }

        if (!$carte->isValideeParAdminRh()) {
            throw new AccessDeniedHttpException("La carte doit d'abord être validée par le RH Admin pour être consultable.");
        }

        if (!$carte->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Carte professionnelle %s - %s', $carte->getNumero(), $carte->getPersonnel()?->getNomComplet() ?? ''),
            'pdf',
        );
        $response = new StreamedResponse(function () use ($carte) {
            fpassthru($this->fileStorage->readStream($carte->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($carte->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, $nom));

        return $response;
    }

    #[Route('/api/me/demandes-carte-pro', name: 'api_me_demandes_carte_pro', methods: ['GET'])]
    public function demandesCartePro(DemandeCarteProRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $demandes = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($demandes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-decision', name: 'api_me_demandes_decision', methods: ['GET'])]
    public function demandesDecision(DemandeDecisionRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $demandes = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($demandes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-jouissance', name: 'api_me_demandes_jouissance', methods: ['GET'])]
    public function demandesJouissance(DemandeJouissanceRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $demandes = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($demandes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/documents-administratifs', name: 'api_me_documents_administratifs', methods: ['GET'])]
    public function documentsAdministratifs(DocumentAdministratifRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $documents = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($documents, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/tickets-incident', name: 'api_me_tickets_incident', methods: ['GET'])]
    public function ticketsIncident(TicketIncidentRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $tickets = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($tickets, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * Permet à l'agent de télécharger son propre document (même route que côté
     * RH — Api/DocumentAdministratifController::fichier() — mais scoping par
     * propriété plutôt que par rôle RH_PERSONNEL).
     */
    #[Route('/api/me/documents-administratifs/{id}/fichier', name: 'api_me_document_administratif_fichier', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function documentAdministratifFichier(DocumentAdministratif $document): StreamedResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel || $document->getPersonnel() !== $personnel) {
            throw $this->createAccessDeniedException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('%s - %s', $document->getLibelle(), $document->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($document->getCheminFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($document) {
            fpassthru($this->fileStorage->readStream($document->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($document->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    /**
     * Alimente la cloche de notifications (menu déroulant) et les badges des
     * rubriques du menu. `recentes` (plafonné) sert à l'affichage du menu
     * déroulant ; `nonLues` (non plafonné) sert au compteur de la cloche et
     * aux badges par rubrique, calculés côté frontend par préfixe de route
     * sur `lien` — voir ShellComponent.
     */
    #[Route('/api/me/notifications', name: 'api_me_notifications', methods: ['GET'])]
    public function notifications(NotificationRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'recentes' => array_map($this->notificationVersTableau(...), $repository->findRecentes($user)),
            'nonLues' => array_map($this->notificationVersTableau(...), $repository->findNonLues($user)),
        ]);
    }

    #[Route('/api/me/notifications/{id}/lire', name: 'api_me_notification_lire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function marquerNotificationLue(int $id, NotificationRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $notification = $repository->find($id);

        if (!$notification || $notification->getDestinataire() !== $user) {
            throw $this->createNotFoundException();
        }

        $notification->setLu(true);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/me/notifications/tout-lire', name: 'api_me_notifications_tout_lire', methods: ['POST'])]
    public function marquerToutesNotificationsLues(NotificationRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        foreach ($repository->findNonLues($user) as $notification) {
            $notification->setLu(true);
        }
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function notificationVersTableau(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'titre' => $notification->getTitre(),
            'message' => $notification->getMessage(),
            'lien' => $notification->getLien(),
            'lu' => $notification->isLu(),
            'createdAt' => $notification->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }

    #[Route('/api/me/materiels', name: 'api_me_materiels', methods: ['GET'])]
    public function materiels(MaterielInformatiqueRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $materiels = $personnel ? $repository->findBy(['affecteA' => $personnel]) : [];

        return $this->json($materiels, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/vehicules', name: 'api_me_vehicules', methods: ['GET'])]
    public function vehicules(VehiculeRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $vehicules = $personnel ? $repository->findBy(['chauffeurAffecte' => $personnel]) : [];

        return $this->json($vehicules, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    private function personnelConnecte(): ?Personnel
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getPersonnel();
    }
}
