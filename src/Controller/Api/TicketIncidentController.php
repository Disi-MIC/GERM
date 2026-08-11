<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\StatutTicket;
use App\Entity\TicketIncident;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Traitement des tickets d'incident côté frontend Angular. La création
 * simple passe par l'opération Post native d'API Platform (aucun effet de
 * bord) ; le traitement reste une action dédiée, en quatre étapes :
 *
 *  - prendreEnCharge() : un technicien s'assigne le ticket (ouvert → en cours) ;
 *  - resoudre() : le technicien propose une résolution (en cours → résolu,
 *    en attente de validation) ;
 *  - refuser() : possible par un technicien (depuis ouvert/en cours) ou par
 *    le responsable (depuis résolu, filet de sécurité) ;
 *  - valider() : réservé au responsable informatique, clôture un ticket résolu ;
 *  - rouvrir() : réservé au responsable, renvoie un ticket résolu en cours si
 *    la résolution proposée n'est pas satisfaisante.
 */
#[IsGranted('ROLE_IT_TECHNICIEN')]
class TicketIncidentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('/api/tickets-incident/{id}/prendre-en-charge', name: 'api_ticket_incident_prendre_en_charge', methods: ['POST'], requirements: ['id' => '\d+'])]
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
}
