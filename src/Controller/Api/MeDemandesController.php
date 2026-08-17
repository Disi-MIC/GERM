<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DemandeCartePro;
use App\Entity\DemandeDecision;
use App\Entity\DemandeJouissance;
use App\Entity\DocumentAdministratif;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Auto-service : tout agent (même sans rôle RH) peut déposer ses propres
 * demandes de carte professionnelle, de décision de congé, de jouissance
 * de congé et certains documents administratifs justificatifs — toujours
 * rattachées à sa propre fiche Personnel, jamais celle
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
    /**
     * Types de document-administratif (ListeValeur, catégorie type-document)
     * qu'un agent peut déposer lui-même : uniquement des pièces justificatives
     * qu'il détient déjà (identité, diplôme, CV...), jamais un type à valeur
     * d'acte administratif produit par le RH (decision_nomination, attestation,
     * contrat) — ceux-là ne s'obtiennent que via Api/DocumentAdministratifController::create(),
     * réservé à ROLE_RH_PERSONNEL, pour garder le dossier officiel fiable.
     */
    private const TYPES_DOCUMENT_AGENT = ['cni', 'passeport', 'acte_naissance', 'diplome', 'certificat_medical', 'casier_judiciaire', 'cv'];

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

    /**
     * Types de document-administratif que l'agent a le droit de déposer
     * lui-même (voir TYPES_DOCUMENT_AGENT) — ListeValeur étant réservée à
     * ROLE_RH_PERSONNEL/ROLE_IT_STOCK, ce sous-ensemble filtré est le seul
     * moyen pour le sélecteur du formulaire "Mes documents" de connaître les
     * id/libellés à proposer.
     */
    #[Route('/api/me/documents-administratifs/types', name: 'api_me_document_administratif_types', methods: ['GET'])]
    public function typesDocumentAdministratif(): JsonResponse
    {
        $types = array_values(array_filter(array_map(
            fn (string $code) => $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::TYPE_DOCUMENT, $code),
            self::TYPES_DOCUMENT_AGENT,
        )));

        return $this->json($types, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * Dépôt direct par l'agent d'une pièce justificative (voir
     * TYPES_DOCUMENT_AGENT) — toujours rattaché à sa propre fiche, marqué
     * soumisParAgent pour que le RH Personnel distingue ces dépôts des
     * documents qu'il archive lui-même (Api/DocumentAdministratifController).
     */
    #[Route('/api/me/documents-administratifs', name: 'api_me_document_administratif_create', methods: ['POST'])]
    public function creerDocumentAdministratif(Request $request): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        if (!$personnel) {
            return $this->reponsePersonnelManquant();
        }

        $codeType = (string) $request->request->get('type');
        if (!\in_array($codeType, self::TYPES_DOCUMENT_AGENT, true)) {
            return $this->json(['errors' => ['type' => "Ce type de document doit être déposé par le service RH."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $this->listeValeurRepository->findOneByCategorieAndCode(CategorieListeValeur::TYPE_DOCUMENT, $codeType);
        if (!$type) {
            return $this->json(['errors' => ['type' => 'Le type de document est invalide.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $document = new DocumentAdministratif();
        $document->setPersonnel($personnel);
        $document->setType($type);
        $libelle = trim((string) $request->request->get('libelle'));
        $document->setLibelle('' !== $libelle ? $libelle : $type->getLibelle());
        $document->setSoumisParAgent(true);

        $violations = $this->validator->validate($document);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $stocke = $this->fileStorage->store($file, 'document-administratif');
        $document->setCheminFichier($stocke['path']);
        $document->setNomOriginal($stocke['originalName']);

        $this->em->persist($document);
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_PERSONNEL,
            'Nouveau document déposé par un agent',
            '/documents-administratifs',
            \sprintf('%s a déposé un document : "%s".', $personnel->getNomComplet(), $document->getLibelle()),
        );

        return $this->json($document, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
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
}
