<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DemandeCartePro;
use App\Entity\DemandeDecision;
use App\Entity\DemandeJouissance;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Entity\Personnel;
use App\Entity\PieceJustificativeDecision;
use App\Entity\PieceJustificativeJouissance;
use App\Entity\TicketIncident;
use App\Entity\User;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\DecisionCongeRepository;
use App\Repository\ListeValeurRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Service\FileStorage;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Auto-service : tout agent (même sans rôle RH) peut déposer ses propres
 * demandes de carte professionnelle, de décision de congé et de jouissance
 * de congé — toujours rattachées à sa propre fiche Personnel, jamais celle
 * envoyée par le client (voir personnelConnecte(), appelée après
 * désérialisation pour écraser toute valeur reçue). Même logique de
 * validation que les contrôleurs RH équivalents (Api/DemandeCarteProController,
 * Api/DemandeDecisionController, Api/DemandeJouissanceController), avec en
 * plus des contrôles de propriété absents côté RH (carteReference/decision
 * doivent appartenir à l'agent lui-même — sans quoi rien n'empêcherait de
 * référencer la carte ou la décision d'un collègue) et un scoping des pièces
 * jointes par demande (les routes RH n'en ont pas besoin, seul le rôle RH y
 * accède).
 */
#[IsGranted('ROLE_AGENT')]
class MeDemandesController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly DecisionCongeRepository $decisionCongeRepository,
        private readonly CarteProfessionnelleRepository $carteProfessionnelleRepository,
        private readonly MaterielInformatiqueRepository $materielInformatiqueRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('/api/me/decisions-conge', name: 'api_me_decisions_conge', methods: ['GET'])]
    public function decisionsConge(): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $decisions = $personnel ? $this->decisionCongeRepository->findValides($personnel) : [];

        return $this->json($decisions, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * 'personnel' et 'carteReference' sont volontairement absents du groupe de
     * désérialisation ici : ce sont des IRI vers des ressources dont le Get
     * natif est réservé au RH (ROLE_RH_PERSONNEL / ROLE_RH_CARTE_PRO), donc
     * les résoudre via le sérialiseur API Platform échouerait (403/erreur
     * IRI) pour un agent sans rôle RH — même en référençant sa propre fiche.
     * On désérialise donc seulement les champs scalaires, puis on résout
     * 'carteReferenceId' nous-mêmes via le repository (sans passer par la
     * sécurité de la ressource) avant de vérifier l'appartenance.
     */
    #[Route('/api/me/demandes-carte-pro', name: 'api_me_demande_carte_pro_create', methods: ['POST'])]
    public function creerDemandeCartePro(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel) {
            return $this->reponsePersonnelManquant();
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $demande = new DemandeCartePro();
        $demande->setPersonnel($personnel);
        $demande->setTypeDemande(TypeDemandeCartePro::tryFrom((string) ($data['typeDemande'] ?? '')));
        $demande->setMotif(isset($data['motif']) ? (string) $data['motif'] : null);

        if (!empty($data['carteReferenceId'])) {
            $carte = $this->carteProfessionnelleRepository->find($data['carteReferenceId']);
            if (!$carte || $carte->getPersonnel() !== $personnel) {
                return $this->json(['errors' => ['carteReference' => "Cette carte n'appartient pas à votre fiche agent."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            $demande->setCarteReference($carte);
        }

        $violations = $this->validator->validate($demande);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($demande);
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CARTE_PRO,
            'Nouvelle demande de carte professionnelle',
            '/cartes-professionnelles/demandes',
            \sprintf('%s a soumis une demande de carte professionnelle.', $personnel->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-carte-pro/{id}/piece', name: 'api_me_demande_carte_pro_piece', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pieceDemandeCartePro(DemandeCartePro $demande, Request $request): JsonResponse
    {
        if (!$this->appartientAMoi($demande->getPersonnel())) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($demande->getCheminFichier()) {
            $this->fileStorage->delete($demande->getCheminFichier());
        }

        $stocke = $this->fileStorage->store($file, 'demande-carte-pro');
        $demande->setCheminFichier($stocke['path']);
        $demande->setNomOriginal($stocke['originalName']);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-decision', name: 'api_me_demande_decision_create', methods: ['POST'])]
    public function creerDemandeDecision(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel) {
            return $this->reponsePersonnelManquant();
        }

        /** @var DemandeDecision $demande */
        $demande = $this->serializer->deserialize($request->getContent(), DemandeDecision::class, 'json', ['groups' => ['api:write']]);
        $demande->setPersonnel($personnel);

        $violations = $this->validator->validate($demande);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($demande);
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CONGE,
            'Nouvelle demande de décision de congé',
            '/conges/demandes-decision',
            \sprintf('%s a soumis une demande de décision de congé.', $personnel->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-decision/{id}/piece1', name: 'api_me_demande_decision_piece1', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pieceDecision1(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPieceDecision($demande, $request);
    }

    #[Route('/api/me/demandes-decision/{id}/piece2', name: 'api_me_demande_decision_piece2', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pieceDecision2(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPieceDecision($demande, $request);
    }

    private function attacherPieceDecision(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$this->appartientAMoi($demande->getPersonnel())) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stocke = $this->fileStorage->store($file, 'decision');
        $piece = new PieceJustificativeDecision();
        $piece->setDemande($demande);
        $piece->setCheminFichier($stocke['path']);
        $piece->setNomOriginal($stocke['originalName']);
        $this->em->persist($piece);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * 'personnel' et 'decision' sont volontairement absents du groupe de
     * désérialisation ici, pour la même raison que dans creerDemandeCartePro() :
     * ce sont des IRI vers des ressources RH-gated (Personnel, DecisionConge),
     * que le sérialiseur API Platform ne pourrait pas résoudre pour un agent
     * sans rôle RH. Les champs scalaires (type/dates/motif) passent par le
     * sérialiseur normalement ; 'decisionId' est résolu nous-mêmes.
     */
    #[Route('/api/me/demandes-jouissance', name: 'api_me_demande_jouissance_create', methods: ['POST'])]
    public function creerDemandeJouissance(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel) {
            return $this->reponsePersonnelManquant();
        }

        /** @var DemandeJouissance $demande */
        $demande = $this->serializer->deserialize($request->getContent(), DemandeJouissance::class, 'json', ['groups' => ['api:write']]);
        $demande->setPersonnel($personnel);

        $data = json_decode($request->getContent(), true) ?? [];
        if (!empty($data['decisionId'])) {
            $decision = $this->decisionCongeRepository->find($data['decisionId']);
            if (!$decision || $decision->getPersonnel() !== $personnel) {
                return $this->json(['errors' => ['decision' => "Cette décision de congé n'appartient pas à votre fiche agent."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            if (!$decision->isValide()) {
                return $this->json(['errors' => ['decision' => 'Cette décision de congé est expirée.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            $demande->setDecision($decision);
        }

        $violations = $this->validator->validate($demande);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($demande);
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CONGE,
            'Nouvelle demande de jouissance de congé',
            '/conges/demandes-jouissance',
            \sprintf('%s a soumis une demande de jouissance de congé.', $personnel->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-jouissance/{id}/piece1', name: 'api_me_demande_jouissance_piece1', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pieceJouissance1(DemandeJouissance $demande, Request $request): JsonResponse
    {
        return $this->attacherPieceJouissance($demande, $request);
    }

    #[Route('/api/me/demandes-jouissance/{id}/piece2', name: 'api_me_demande_jouissance_piece2', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pieceJouissance2(DemandeJouissance $demande, Request $request): JsonResponse
    {
        return $this->attacherPieceJouissance($demande, $request);
    }

    private function attacherPieceJouissance(DemandeJouissance $demande, Request $request): JsonResponse
    {
        if (!$this->appartientAMoi($demande->getPersonnel())) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stocke = $this->fileStorage->store($file, 'jouissance');
        $piece = new PieceJustificativeJouissance();
        $piece->setDemande($demande);
        $piece->setCheminFichier($stocke['path']);
        $piece->setNomOriginal($stocke['originalName']);
        $this->em->persist($piece);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * 'materiel' n'est volontairement pas résolu via IRI (MaterielInformatique
     * est réservé au rôle ROLE_IT_STOCK) : identifiant numérique résolu ici
     * même, avec vérification que le matériel est bien affecté à l'agent
     * connecté — même logique que carteReferenceId/decisionId.
     */
    #[Route('/api/me/tickets-incident', name: 'api_me_ticket_incident_create', methods: ['POST'])]
    public function creerTicket(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel) {
            return $this->reponsePersonnelManquant();
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $materiel = !empty($data['materielId']) ? $this->materielInformatiqueRepository->find($data['materielId']) : null;
        if (!$materiel || $materiel->getAffecteA() !== $personnel) {
            return $this->json(['errors' => ['materiel' => "Ce matériel ne vous est pas affecté."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ticket = new TicketIncident();
        $ticket->setPersonnel($personnel);
        $ticket->setMateriel($materiel);
        $ticket->setTitre(isset($data['titre']) ? (string) $data['titre'] : null);
        $ticket->setDescription(isset($data['description']) ? (string) $data['description'] : null);
        $codePriorite = (string) ($data['priorite'] ?? '') ?: 'normale';
        $priorite = $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::PRIORITE_TICKET, $codePriorite)
            ?? $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::PRIORITE_TICKET, 'normale');
        $ticket->setPriorite($priorite);

        $violations = $this->validator->validate($ticket);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($ticket);
        $this->em->flush();

        // Le responsable est le point d'entrée unique (Service Desk) : c'est
        // lui qui répartit ensuite sur un technicien via assigner() — voir
        // TicketIncidentController.
        $this->notificationService->notifierRole(
            User::ROLE_IT_RESPONSABLE,
            'Nouveau ticket d\'incident',
            '/tickets-informatique',
            \sprintf('%s a signalé un incident : "%s".', $personnel->getNomComplet(), $ticket->getTitre()),
        );

        return $this->json($ticket, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    private function personnelConnecte(): ?Personnel
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getPersonnel();
    }

    private function appartientAMoi(?Personnel $personnel): bool
    {
        return null !== $personnel && $personnel === $this->personnelConnecte();
    }

    private function reponsePersonnelManquant(): JsonResponse
    {
        return $this->json(['errors' => ['personnel' => "Aucune fiche personnel n'est liée à votre compte. Merci de contacter le service RH."]], JsonResponse::HTTP_NOT_FOUND);
    }

    private function violationsResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
