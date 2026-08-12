<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\StatutTicket;
use App\Entity\Personnel;
use App\Entity\TicketEscalade;
use App\Entity\TicketIncident;
use App\Entity\User;
use App\Repository\PersonnelRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Traitement des tickets d'incident côté frontend Angular. La création
 * simple passe par l'opération Post native d'API Platform (aucun effet de
 * bord) ; le traitement reste une action dédiée, en règle générale réservée
 * à ROLE_IT_TICKETS (le Stock peut lire les tickets — voir la sécurité de
 * l'entité — mais ne peut pas les traiter) :
 *
 *  - prendreEnCharge()/assigner() : réservés au responsable, point d'entrée
 *    unique (Service Desk ITIL) — tout nouveau ticket (ou ticket escaladé)
 *    lui est notifié, à lui de le prendre en charge personnellement ou de le
 *    répartir sur un technicien via assigner(). Un technicien ROLE_IT_TICKETS
 *    ne peut donc plus se servir lui-même dans la file — c'est le
 *    changement volontaire par rapport à un modèle "premier arrivé, premier
 *    servi" (voir techniciens() pour la liste utilisée par le sélecteur).
 *  - resoudre() : le technicien assigné propose une résolution (en cours →
 *    résolu, en attente de validation) ;
 *  - refuser() : possible par un agent Tickets (depuis ouvert/en cours) ou
 *    par le responsable (depuis résolu, filet de sécurité) ;
 *  - valider() : réservé au responsable informatique, clôture un ticket résolu ;
 *  - rouvrir() : réservé au responsable, renvoie un ticket résolu en cours si
 *    la résolution proposée n'est pas satisfaisante.
 *  - escalader() : fait passer le ticket au palier de support suivant
 *    (N1→N2→N3, voir NiveauTicket) quand il dépasse la compétence du palier
 *    courant — remet le ticket "ouvert" (non assigné), notifie le
 *    responsable pour une nouvelle répartition, même logique qu'un nouveau
 *    ticket.
 */
#[IsGranted('ROLE_IT_TICKETS')]
class TicketIncidentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificationService,
        private readonly PersonnelRepository $personnelRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Personnel pouvant se voir assigner un ticket (titulaire de
     * ROLE_IT_TICKETS, hiérarchie incluse — donc le responsable aussi) —
     * alimente le sélecteur "Assigner à" côté Angular.
     */
    #[Route('/api/tickets-incident/techniciens', name: 'api_ticket_incident_techniciens', methods: ['GET'])]
    #[IsGranted('ROLE_IT_RESPONSABLE')]
    public function techniciens(): JsonResponse
    {
        $techniciens = [];
        foreach ($this->userRepository->findBy(['actif' => true]) as $user) {
            $personnel = $user->getPersonnel();
            if ($personnel && \in_array(User::ROLE_IT_TICKETS, $user->getRoles(), true)) {
                $techniciens[] = $personnel;
            }
        }

        return $this->json($techniciens, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/prendre-en-charge', name: 'api_ticket_incident_prendre_en_charge', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_IT_RESPONSABLE')]
    public function prendreEnCharge(TicketIncident $ticket): JsonResponse
    {
        if (!$ticket->isOuvert()) {
            return $this->json(['errors' => ['statut' => 'Ce ticket a déjà été pris en charge ou traité.']], JsonResponse::HTTP_CONFLICT);
        }

        /** @var User $technicien */
        $technicien = $this->getUser();

        $ticket->setStatut(StatutTicket::EN_COURS);
        $ticket->setAssigneA($technicien->getPersonnel());
        $ticket->setDatePriseEnCharge(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $ticket->getPersonnel()?->getUser(),
            'Votre ticket a été pris en charge',
            '/mon-espace/tickets',
            \sprintf('%s traite votre ticket "%s".', $technicien->getPersonnel()?->getNomComplet() ?? 'Le service informatique', $ticket->getTitre()),
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/assigner', name: 'api_ticket_incident_assigner', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_IT_RESPONSABLE')]
    public function assigner(TicketIncident $ticket, Request $request): JsonResponse
    {
        if (!$ticket->isOuvert()) {
            return $this->json(['errors' => ['statut' => 'Ce ticket a déjà été pris en charge ou traité.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $technicien = !empty($data['personnelId']) ? $this->personnelRepository->find($data['personnelId']) : null;
        if (!$technicien instanceof Personnel) {
            return $this->json(['errors' => ['personnelId' => 'Merci de sélectionner un technicien.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket->setStatut(StatutTicket::EN_COURS);
        $ticket->setAssigneA($technicien);
        $ticket->setDatePriseEnCharge(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $technicien->getUser(),
            'Un ticket vous a été assigné',
            '/tickets-informatique',
            \sprintf(
                'Le ticket "%s" vous a été assigné. Échéance de traitement : %s.',
                $ticket->getTitre(),
                $ticket->getEcheanceSla()?->format('d/m/Y H:i') ?? 'non définie',
            ),
        );
        $this->notificationService->notifier(
            $ticket->getPersonnel()?->getUser(),
            'Votre ticket a été pris en charge',
            '/mon-espace/tickets',
            \sprintf('%s traite votre ticket "%s".', $technicien->getNomComplet(), $ticket->getTitre()),
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/resoudre', name: 'api_ticket_incident_resoudre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resoudre(TicketIncident $ticket, Request $request): JsonResponse
    {
        if (!$ticket->isEnCours()) {
            return $this->json(['errors' => ['statut' => 'Ce ticket doit être en cours de traitement pour être marqué résolu.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $commentaire = trim((string) ($data['commentaire'] ?? ''));
        if ('' === $commentaire) {
            return $this->json(['errors' => ['commentaire' => 'Merci de décrire la résolution apportée.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket->setStatut(StatutTicket::RESOLU);
        $ticket->setCommentaireResolution($commentaire);
        $ticket->setDateResolution(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_IT_RESPONSABLE,
            'Ticket résolu à valider',
            '/tickets-informatique',
            \sprintf('Une résolution a été proposée pour le ticket "%s".', $ticket->getTitre()),
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/refuser', name: 'api_ticket_incident_refuser', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refuser(TicketIncident $ticket, Request $request): JsonResponse
    {
        if ($ticket->isResolu()) {
            // Une fois résolu, seul le responsable peut encore refuser (filet
            // de sécurité) — le technicien ne peut plus revenir dessus.
            $this->denyAccessUnlessGranted('ROLE_IT_RESPONSABLE');
        } elseif (!$ticket->isOuvert() && !$ticket->isEnCours()) {
            return $this->json(['errors' => ['statut' => 'Ce ticket a déjà été traité.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $commentaire = trim((string) ($data['commentaire'] ?? ''));
        if ('' === $commentaire) {
            return $this->json(['errors' => ['commentaire' => 'Merci de préciser le motif du refus.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket->setStatut(StatutTicket::REFUSE);
        $ticket->setCommentaireResolution($commentaire);
        $this->em->flush();

        $this->notificationService->notifier(
            $ticket->getPersonnel()?->getUser(),
            'Votre ticket a été refusé',
            '/mon-espace/tickets',
            $commentaire,
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/valider', name: 'api_ticket_incident_valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_IT_RESPONSABLE')]
    public function valider(TicketIncident $ticket, Request $request): JsonResponse
    {
        if (!$ticket->isResolu()) {
            return $this->json(['errors' => ['statut' => "Ce ticket doit d'abord être résolu par un technicien avant de pouvoir être validé."]], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $ticket->setStatut(StatutTicket::CLOTURE);
        $ticket->setCommentaireValidation($data['commentaire'] ?? null);
        $ticket->setDateCloture(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $ticket->getPersonnel()?->getUser(),
            'Votre ticket a été clôturé',
            '/mon-espace/tickets',
            \sprintf('Le ticket "%s" a été validé et clôturé.', $ticket->getTitre()),
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/rouvrir', name: 'api_ticket_incident_rouvrir', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_IT_RESPONSABLE')]
    public function rouvrir(TicketIncident $ticket, Request $request): JsonResponse
    {
        if (!$ticket->isResolu()) {
            return $this->json(['errors' => ['statut' => 'Seul un ticket résolu en attente de validation peut être rouvert.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $commentaire = trim((string) ($data['commentaire'] ?? ''));
        if ('' === $commentaire) {
            return $this->json(['errors' => ['commentaire' => 'Merci de préciser pourquoi la résolution proposée ne convient pas.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket->setStatut(StatutTicket::EN_COURS);
        $ticket->setCommentaireValidation($commentaire);
        $this->em->flush();

        $this->notificationService->notifier(
            $ticket->getAssigneA()?->getUser(),
            'Résolution refusée, ticket rouvert',
            '/tickets-informatique',
            $commentaire,
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/tickets-incident/{id}/escalader', name: 'api_ticket_incident_escalader', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function escalader(TicketIncident $ticket, Request $request): JsonResponse
    {
        if (!$ticket->isOuvert() && !$ticket->isEnCours()) {
            return $this->json(['errors' => ['statut' => 'Seul un ticket ouvert ou en cours peut être escaladé.']], JsonResponse::HTTP_CONFLICT);
        }

        $niveauSuivant = $ticket->getNiveau()->suivant();
        if (null === $niveauSuivant) {
            return $this->json(['errors' => ['niveau' => 'Ce ticket est déjà au niveau d\'escalade maximal.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $commentaire = trim((string) ($data['commentaire'] ?? ''));
        if ('' === $commentaire) {
            return $this->json(['errors' => ['commentaire' => "Merci de préciser le motif de l'escalade."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $utilisateur */
        $utilisateur = $this->getUser();

        $escalade = new TicketEscalade();
        $escalade->setTicket($ticket);
        $escalade->setDeNiveau($ticket->getNiveau());
        $escalade->setVersNiveau($niveauSuivant);
        $escalade->setPar($utilisateur->getPersonnel());
        $escalade->setCommentaire($commentaire);
        $this->em->persist($escalade);

        // Remise en file d'attente au nouveau palier plutôt que réassignation
        // directe : le palier suivant doit reprendre la main lui-même (même
        // logique que prendreEnCharge), pas hériter automatiquement du
        // technicien qui n'a pas pu résoudre.
        $ticket->setNiveau($niveauSuivant);
        $ticket->setStatut(StatutTicket::OUVERT);
        $ticket->setAssigneA(null);
        $this->em->flush();

        // Toujours le responsable, jamais le pool ROLE_IT_TICKETS : c'est lui
        // le point d'entrée unique qui répartit, y compris pour un ticket
        // escaladé — même logique qu'une création de ticket.
        $this->notificationService->notifierRole(
            User::ROLE_IT_RESPONSABLE,
            \sprintf('Ticket escaladé au %s', $niveauSuivant->label()),
            '/tickets-informatique',
            \sprintf('Le ticket "%s" a été escaladé : %s', $ticket->getTitre(), $commentaire),
        );

        return $this->json($ticket, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }
}
