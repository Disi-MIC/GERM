<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DecisionConge;
use App\Entity\DemandeDecision;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\StatutDemande;
use App\Entity\Personnel;
use App\Entity\PieceJustificativeDecision;
use App\Entity\User;
use App\Repository\DecisionCongeRepository;
use App\Repository\ListeValeurRepository;
use App\Repository\ParametresDecisionCongeRepository;
use App\Service\EligibiliteDecisionCongeService;
use App\Service\FileStorage;
use App\Service\NotificationService;
use App\Service\TexteLegalDecisionCongeSubstitutionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les demandes de décision de congé côté frontend Angular. La
 * ressource DemandeDecision est exposée en lecture seule via API Platform :
 * la création simple passe par une action dédiée (pièces jointes en appels
 * séparés, comme pour DemandeCartePro), le traitement en cinq étapes — voir
 * le commentaire de classe de DemandeDecision pour le circuit complet :
 *
 *  - valider() : RH Congé, vérifie les pièces, autorise l'agent à déposer
 *    physiquement au service courrier ;
 *  - rejeter() : RH Congé, motif prédéterminé obligatoire (pièces
 *    incomplètes) ;
 *  - (confirmation du dépôt courrier par l'agent lui-même — voir
 *    Api/MeDemandesController::confirmerDepotCourrierDecision()) ;
 *  - retour() : RH Admin, dépose les documents scannés reçus en retour du
 *    circuit ministériel ;
 *  - genererEtTransmettre() : RH Congé, calcule le nombre de jours, crée la
 *    DecisionConge et transmet à l'agent (physiquement et dans l'application).
 */
#[IsGranted('ROLE_RH_CONGE')]
class DemandeDecisionController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly NotificationService $notificationService,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly DecisionCongeRepository $decisionCongeRepository,
        private readonly ParametresDecisionCongeRepository $parametresDecisionCongeRepository,
        private readonly EligibiliteDecisionCongeService $eligibiliteService,
        private readonly TexteLegalDecisionCongeSubstitutionService $substitutionService,
    ) {
    }

    #[Route('/api/demandes-decision', name: 'api_demande_decision_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $demande = $this->serializer->deserialize($request->getContent(), DemandeDecision::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($demande);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        if ($erreur = $this->erreurDecisionEnCours($demande->getPersonnel(), 'Cet agent dispose déjà')) {
            return $erreur;
        }

        // Vérifié dès la création — voir MeDemandesController::creerDemandeDecision()
        // pour la même règle et sa justification (éviter tout le circuit
        // papier pour un agent pas encore éligible).
        if ($demande->getPersonnel()) {
            $eligibilite = $this->eligibiliteService->calculer($demande->getPersonnel(), $demande, new \DateTimeImmutable('today'));
            if ($eligibilite && !$eligibilite->eligible) {
                return $this->json(['errors' => ['eligibilite' => $eligibilite->message]], JsonResponse::HTTP_CONFLICT);
            }
        }

        $this->em->persist($demande);
        $this->em->flush();

        return $this->json($demande, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    /**
     * Une demande de décision n'a de sens que si l'agent n'a plus de congé
     * annuel disponible : tant qu'une DecisionConge valide (non expirée)
     * existe déjà pour lui, inutile — et source de doublons pour le RH Congé
     * — d'en déposer une nouvelle. Utilisé à la création (create() côté RH,
     * MeDemandesController::creerDemandeDecision() côté agent) ainsi que par
     * decisionValide(), qui permet au formulaire RH de vérifier par avance,
     * dès la sélection de l'agent, avant même la soumission.
     */
    private function erreurDecisionEnCours(?Personnel $personnel, string $sujet): ?JsonResponse
    {
        if (!$personnel) {
            return null;
        }

        $decisionsValides = $this->decisionCongeRepository->findValides($personnel);
        if (!$decisionsValides) {
            return null;
        }

        $decision = $decisionsValides[0];

        return $this->json(['errors' => ['decision' => \sprintf(
            "%s d'une décision de congé valide (%s), jusqu'au %s. Une nouvelle demande ne peut être déposée qu'après son expiration.",
            $sujet,
            $decision->getNumeroDecision(),
            $decision->getDateExpiration()?->format('d/m/Y'),
        )]], JsonResponse::HTTP_CONFLICT);
    }

    /**
     * Initiales de l'opérateur RH Congé qui traite la demande — dernier
     * segment du numéro de décision ("MIC/DAGE/RH/cd"), jamais saisi
     * librement (voir genererEtTransmettre()).
     */
    private function initialesOperateur(User $operateur): string
    {
        $prenom = mb_substr((string) $operateur->getPrenom(), 0, 1);
        $nom = mb_substr((string) $operateur->getNom(), 0, 1);

        return mb_strtolower($prenom.$nom);
    }

    /**
     * Permet au formulaire RH de vérifier, dès la sélection de l'agent et
     * avant toute soumission, si celui-ci dispose déjà d'une décision de
     * congé valide — voir erreurDecisionEnCours().
     */
    #[Route('/api/personnels/{id}/decision-valide', name: 'api_personnel_decision_valide', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function decisionValide(Personnel $personnel): JsonResponse
    {
        $decisions = $this->decisionCongeRepository->findValides($personnel);

        return $this->json($decisions[0] ?? null, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/piece1', name: 'api_demande_decision_piece1', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function piece1(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPiece($demande, $request);
    }

    #[Route('/api/demandes-decision/{id}/piece2', name: 'api_demande_decision_piece2', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function piece2(DemandeDecision $demande, Request $request): JsonResponse
    {
        return $this->attacherPiece($demande, $request);
    }

    private function attacherPiece(DemandeDecision $demande, Request $request): JsonResponse
    {
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

    #[Route('/api/pieces-decision/{id}', name: 'api_piece_decision_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPiece(PieceJustificativeDecision $piece): StreamedResponse
    {
        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Piece justificative decision - %s', $piece->getDemande()?->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($piece->getCheminFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($piece) {
            fpassthru($this->fileStorage->readStream($piece->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($piece->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    #[Route('/api/demandes-decision/{id}/valider', name: 'api_demande_decision_valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        if ($demande->getPieces()->count() < 2) {
            return $this->json(['errors' => ['pieces' => \sprintf(
                'Pièces incomplètes : 2 attendues (%s), %d fournie(s).',
                $demande->isNouvellementAffecte() ? 'prise de service + acte d\'engagement' : 'prise de service + ancienne décision',
                $demande->getPieces()->count(),
            )]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $demande->setStatut(StatutDemande::VALIDEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre demande de décision de congé est validée',
            '/mon-espace/conges',
            'Pièces vérifiées : vous pouvez déposer votre dossier au service courrier, puis confirmer le dépôt dans l\'application.',
        );
        $this->notificationService->notifierRole(
            User::ROLE_RH_RESPONSABLE,
            'Décision de congé validée par le RH Congé',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a validé la demande de %s : pièces complètes, en attente du dépôt au service courrier.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/rejeter', name: 'api_demande_decision_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Cette demande a déjà été traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $motif = !empty($data['motifRejet']) ? $this->listeValeurRepository->find($data['motifRejet']) : null;
        if (!$motif || CategorieListeValeur::MOTIF_REJET_DECISION_CONGE !== $motif->getCategorie()) {
            return $this->json(['errors' => ['motifRejet' => 'Merci de sélectionner un motif de rejet.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $demande->setStatut(StatutDemande::REFUSEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $demande->setMotifRejet($motif);
        $demande->setCommentaireTraitement($data['commentaire'] ?? null);
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre demande de décision de congé a été refusée',
            '/mon-espace/conges',
            $motif->getLibelle().($demande->getCommentaireTraitement() ? ' — '.$demande->getCommentaireTraitement() : ''),
        );
        $this->notificationService->notifierRole(
            User::ROLE_RH_RESPONSABLE,
            'Décision de congé rejetée par le RH Congé',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a rejeté la demande de %s (%s).', $demande->getPersonnel()?->getNomComplet(), $motif->getLibelle()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    /**
     * RH Admin uniquement : le circuit ministériel (courrier → SG → Ministre →
     * DAGE) se déroule entièrement hors application, entre le dépôt courrier
     * de l'agent et cette étape — voir le commentaire de classe de
     * DemandeDecision. Dépose les documents scannés du retour et rend la
     * demande visible au RH Congé pour traitement final.
     */
    #[Route('/api/demandes-decision/{id}/retour', name: 'api_demande_decision_retour', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_RH_RESPONSABLE')]
    public function retour(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isDeposeeCourrier()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord être déposée au service courrier par l'agent."]], JsonResponse::HTTP_CONFLICT);
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stocke = $this->fileStorage->store($file, 'decision');
        $demande->setCheminDocumentRetour($stocke['path']);
        $demande->setNomOriginalDocumentRetour($stocke['originalName']);
        $demande->setStatut(StatutDemande::RETOURNEE);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifierRole(
            User::ROLE_RH_CONGE,
            'Décision de congé revenue du circuit, à finaliser',
            '/conges/demandes-decision',
            \sprintf('Le RH Admin a déposé les documents de retour pour %s : calculez le nombre de jours et transmettez à l\'agent.', $demande->getPersonnel()?->getNomComplet()),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}/document-retour', name: 'api_demande_decision_document_retour_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadDocumentRetour(DemandeDecision $demande): StreamedResponse
    {
        if (!$demande->getCheminDocumentRetour()) {
            throw $this->createNotFoundException();
        }

        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Retour circuit decision - %s', $demande->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($demande->getCheminDocumentRetour(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($demande) {
            fpassthru($this->fileStorage->readStream($demande->getCheminDocumentRetour()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($demande->getCheminDocumentRetour()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    /**
     * Éligibilité de l'agent à une nouvelle décision et nombre de jours à lui
     * octroyer, calculés par EligibiliteDecisionCongeService — jamais par
     * Angular, qui ne fait qu'afficher ce résultat (voir
     * demande-decision-traiter.component.ts). Consultée dès que le RH Congé
     * ouvre l'étape finale du traitement ; genererEtTransmettre() refait le
     * même calcul de façon autoritaire au moment de la génération, cet
     * endpoint ne sert qu'à l'affichage préalable.
     */
    #[Route('/api/demandes-decision/{id}/eligibilite', name: 'api_demande_decision_eligibilite', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function eligibilite(DemandeDecision $demande): JsonResponse
    {
        $personnel = $demande->getPersonnel();
        if (!$personnel) {
            return $this->json(['errors' => ['personnel' => 'Aucun agent rattaché à cette demande.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resultat = $this->eligibiliteService->calculer($personnel, $demande, new \DateTimeImmutable('today'));
        if (!$resultat) {
            return $this->json(['errors' => ['eligibilite' => "Aucune date de référence disponible (ni décision antérieure, ni date de prise de service/dernière décision renseignée sur la demande, ni date d'embauche) pour calculer l'éligibilité."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($resultat->toArray());
    }

    /**
     * Dernière étape, RH Congé : recalcule l'éligibilité et le nombre de
     * jours de façon autoritaire (EligibiliteDecisionCongeService — jamais
     * une valeur reçue du client, voir eligibilite() ci-dessus pour la même
     * consultation en amont, purement informative), crée la DecisionConge et
     * clôt la demande. Un seul appel plutôt qu'un "générer" puis
     * "transmettre" séparés — même simplification que les autres étapes de
     * ce circuit (une vérification/action humaine = un clic). Date
     * d'octroi/d'expiration ne sont plus saisies : la première est toujours
     * la date de génération, la seconde toujours octroi + 3 ans — aucune
     * marge d'appréciation RH sur ces deux dates.
     */
    #[Route('/api/demandes-decision/{id}/generer-et-transmettre', name: 'api_demande_decision_generer_et_transmettre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function genererEtTransmettre(DemandeDecision $demande, Request $request): JsonResponse
    {
        if (!$demande->isRetournee()) {
            return $this->json(['errors' => ['statut' => "Cette demande doit d'abord revenir du circuit papier (RH Admin) avant de pouvoir être générée."]], JsonResponse::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        // Date d'octroi = date de délivrance de la décision (aujourd'hui, à
        // cette étape) ; date d'expiration = octroi + 3 ans — plus de saisie
        // manuelle, ces deux dates n'ont rien de discrétionnaire.
        $dateDecision = new \DateTimeImmutable('today');
        $dateExpiration = $dateDecision->modify('+3 years');
        $dateDerniereDecision = isset($data['dateDerniereDecision'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateDerniereDecision']) ?: null
            : null;
        $numeroAttestationNonJouissance = isset($data['numeroAttestationNonJouissance'])
            ? trim((string) $data['numeroAttestationNonJouissance']) ?: null
            : null;
        $dateAttestationNonJouissance = isset($data['dateAttestationNonJouissance'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $data['dateAttestationNonJouissance']) ?: null
            : null;

        // L'attestation de non jouissance, comme la décision de congé
        // antérieure, n'a de sens que pour un agent qui en a déjà une : un
        // nouvel agent n'a jamais pu jouir d'un congé qu'il n'a jamais eu.
        if (!$demande->isNouvellementAffecte() && (null === $numeroAttestationNonJouissance || null === $dateAttestationNonJouissance)) {
            return $this->json(['errors' => ['numeroAttestationNonJouissance' => "Merci de renseigner le numéro et la date de l'attestation de non jouissance de congé."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($dateDerniereDecision) {
            $demande->setDateDerniereDecision($dateDerniereDecision);
        }

        // Éligibilité et nombre de jours : jamais une valeur reçue du
        // client (voir eligibilite() ci-dessus, purement informatif côté
        // Angular) — recalculés ici de façon autoritaire, seule source qui
        // compte pour créer réellement la DecisionConge.
        $eligibilite = $this->eligibiliteService->calculer($demande->getPersonnel(), $demande, $dateDecision);
        if (!$eligibilite) {
            return $this->json(['errors' => ['eligibilite' => "Aucune date de référence disponible pour calculer l'éligibilité de l'agent."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$eligibilite->eligible) {
            return $this->json(['errors' => ['eligibilite' => $eligibilite->message]], JsonResponse::HTTP_CONFLICT);
        }
        $nombreJours = $eligibilite->joursAccordables;

        /** @var User $operateur */
        $operateur = $this->getUser();
        // Le dernier segment du numéro ("MIC/DAGE/RH/cd") n'est pas saisi :
        // ce sont les initiales de l'opérateur RH Congé qui traite la
        // demande, jamais un texte libre — voir initialesOperateur().
        $numero = \sprintf('MIC/DAGE/RH/%s', $this->initialesOperateur($operateur));

        // Texte légal par défaut (visas des décrets, articles 2 et 3) et
        // début de la période de service ouvrant droit à ce congé, copiés
        // ici une fois pour toutes plutôt que référencés dynamiquement — une
        // modification ultérieure des réglages ne doit pas changer le
        // contenu d'une décision déjà délivrée (voir ParametresDecisionConge).
        // Un jeu de réglages par catégorie (fonctionnaire/non-fonctionnaire),
        // même critère que pour le calcul d'éligibilité.
        $categorie = $this->eligibiliteService->categorieDe($demande->getPersonnel());
        $parametres = $this->parametresDecisionCongeRepository->recupererOuCreer($categorie);

        // Tant que le RH Admin n'a pas renseigné le texte légal de cette
        // catégorie (Api/ParametresDecisionCongeController), la ligne existe
        // mais ses champs valent null (voir recupererOuCreer()) — mieux vaut
        // bloquer ici que délivrer une décision avec des visas/articles
        // manquants.
        if (null === $parametres->getVisasDecrets() || null === $parametres->getArticle2() || null === $parametres->getArticle3()) {
            return $this->json(['errors' => ['parametres' => \sprintf(
                "Le texte légal de la décision (visas, articles 2 et 3) n'a pas encore été configuré pour la catégorie %s. Merci de le renseigner dans les réglages RH Admin avant de générer une décision.",
                $categorie->value,
            )]], JsonResponse::HTTP_CONFLICT);
        }

        // Champs propres à la décision affectés en premier : le texte légal
        // (ci-dessous) peut référencer certains d'entre eux via des
        // emplacements ("{{decision.periodeDebut}}", etc. — voir
        // TexteLegalDecisionCongeSubstitutionService), la substitution a donc
        // besoin qu'ils soient déjà renseignés sur $nouvelleDecision.
        $nouvelleDecision = new DecisionConge();
        $nouvelleDecision->setPersonnel($demande->getPersonnel());
        $nouvelleDecision->setNumeroDecision($numero);
        $nouvelleDecision->setDateDecision($dateDecision);
        $nouvelleDecision->setDateExpiration($dateExpiration);
        $nouvelleDecision->setNombreJours($nombreJours);
        $nouvelleDecision->setPeriodeDebut($demande->getDateDerniereDecision() ?? $demande->getDatePriseDeService());
        $nouvelleDecision->setNumeroDerniereDecisionReferencee($demande->getNumeroDerniereDecision());
        $nouvelleDecision->setNumeroAttestationNonJouissance($numeroAttestationNonJouissance);
        $nouvelleDecision->setDateAttestationNonJouissance($dateAttestationNonJouissance);
        $nouvelleDecision->setGenereePar($operateur);

        // Texte légal RH Admin substitué (emplacements "{{agent...}}" /
        // "{{decision...}}" remplacés par les valeurs de cette décision) puis
        // figé sur la DecisionConge — jamais recalculé ensuite.
        $nouvelleDecision->setVisasDecrets($this->substitutionService->substituer($parametres->getVisasDecrets(), $demande, $nouvelleDecision));
        $nouvelleDecision->setArticle2($this->substitutionService->substituer($parametres->getArticle2(), $demande, $nouvelleDecision));
        $nouvelleDecision->setArticle3($this->substitutionService->substituer($parametres->getArticle3(), $demande, $nouvelleDecision));
        $nouvelleDecision->setAmpliations($this->substitutionService->substituer($parametres->getAmpliations(), $demande, $nouvelleDecision));
        $this->em->persist($nouvelleDecision);

        $demande->setDecisionCreee($nouvelleDecision);
        $demande->setStatut(StatutDemande::TRANSMISE_AGENT);
        $demande->setDateTraitement(new \DateTimeImmutable());
        $this->em->flush();

        $this->notificationService->notifier(
            $demande->getPersonnel()?->getUser(),
            'Votre décision de congé est disponible',
            '/mon-espace/conges',
            \sprintf('Votre décision de congé (%d jours) vous a été remise.', $nombreJours),
        );
        $this->notificationService->notifierRole(
            User::ROLE_RH_RESPONSABLE,
            'Décision de congé générée et transmise',
            '/conges/demandes-decision',
            \sprintf('Le RH Congé a généré la décision de %s (%d jours) et l\'a transmise à l\'agent.', $demande->getPersonnel()?->getNomComplet(), $nombreJours),
        );

        return $this->json($demande, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/demandes-decision/{id}', name: 'api_demande_decision_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(DemandeDecision $demande): JsonResponse
    {
        if (!$demande->isEnAttente()) {
            return $this->json(['errors' => ['statut' => 'Impossible de supprimer une demande déjà traitée.']], JsonResponse::HTTP_CONFLICT);
        }

        foreach ($demande->getPieces() as $piece) {
            $this->fileStorage->delete($piece->getCheminFichier());
        }

        $this->em->remove($demande);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
