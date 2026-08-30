<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\HistoriqueAffectationMateriel;
use App\Entity\HistoriqueChangementMateriel;
use App\Entity\LicenceLogiciel;
use App\Entity\ListeValeur;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\TicketIncident;
use App\Entity\User;
use App\Repository\HistoriqueAffectationMaterielRepository;
use App\Repository\HistoriqueChangementMaterielRepository;
use App\Repository\ListeValeurRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\PersonnelRepository;
use App\Repository\TicketIncidentRepository;
use App\Service\FileStorage;
use App\Service\NotificationService;
use App\Service\QrTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le parc informatique côté frontend Angular. La ressource
 * MaterielInformatique est exposée en lecture seule via API Platform (voir
 * l'entité) : la création, l'édition et la suppression passent par ces
 * actions dédiées — même logique que PersonnelController. Les groupes
 * api:read:rh/api:write:rh (fournisseur) sont demandés en plus des groupes
 * de base ici, contrairement à la vue self-service "Mon parc informatique"
 * (MeController::materiels()) qui ne les demande jamais.
 *
 * `affecteA` reste éditable directement dans ce même formulaire (comme
 * Personnel.service/fonction/grade) plutôt que via une action dédiée : create()
 * et update() détectent un changement d'affectation et alimentent
 * automatiquement le journal HistoriqueAffectationMateriel en dessous — même
 * principe que la synchronisation Personnel/HistoriqueAffectation dans
 * PersonnelController, sans dupliquer le chemin d'écriture.
 */
#[IsGranted('ROLE_IT_STOCK')]
class MaterielInformatiqueController extends AbstractController
{
    private const GROUPES_LECTURE = ['api:read', 'api:read:rh'];
    private const GROUPES_ECRITURE = ['api:write', 'api:write:rh'];

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly HistoriqueAffectationMaterielRepository $historiqueRepository,
        private readonly HistoriqueChangementMaterielRepository $historiqueChangementRepository,
        private readonly MaintenanceRepository $maintenanceRepository,
        private readonly TicketIncidentRepository $ticketIncidentRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
        private readonly PersonnelRepository $personnelRepository,
        private readonly NotificationService $notificationService,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/materiels-informatiques', name: 'api_materiel_informatique_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $materiel = $this->serializer->deserialize($request->getContent(), MaterielInformatique::class, 'json', ['groups' => self::GROUPES_ECRITURE]);

        $violations = $this->validator->validate($materiel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($materiel);

        if ($materiel->getAffecteA()) {
            $this->ouvrirAffectation($materiel, $materiel->getAffecteA());
        }

        $this->em->flush();

        return $this->json($materiel, JsonResponse::HTTP_CREATED, [], ['groups' => self::GROUPES_LECTURE]);
    }

    #[Route('/api/materiels-informatiques/{id}', name: 'api_materiel_informatique_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(MaterielInformatique $materiel, Request $request): JsonResponse
    {
        $ancienAffecteA = $materiel->getAffecteA();
        $avant = $this->snapshotChamps($materiel);

        $this->serializer->deserialize($request->getContent(), MaterielInformatique::class, 'json', [
            'groups' => self::GROUPES_ECRITURE,
            'object_to_populate' => $materiel,
        ]);

        $violations = $this->validator->validate($materiel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $nouvelAffecteA = $materiel->getAffecteA();
        if ($ancienAffecteA !== $nouvelAffecteA) {
            $enCours = $this->historiqueRepository->findEnCoursPourMateriel($materiel);
            $enCours?->setDateFinAffectation(new \DateTimeImmutable());

            if ($nouvelAffecteA) {
                $this->ouvrirAffectation($materiel, $nouvelAffecteA);
            }
        }

        $this->enregistrerChangements($materiel, $avant);

        $this->em->flush();

        return $this->json($materiel, JsonResponse::HTTP_OK, [], ['groups' => self::GROUPES_LECTURE]);
    }

    #[Route('/api/materiels-informatiques/{id}', name: 'api_materiel_informatique_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(MaterielInformatique $materiel): JsonResponse
    {
        // Historique d'affectation, maintenances et tickets référencent tous
        // ce matériel en RESTRICT : sans ce contrôle, la suppression lèverait
        // une erreur SQL brute dès qu'un de ces journaux existe (le cas normal
        // pour tout matériel réellement utilisé — voir l'état "réformé" pour
        // retirer un matériel du service sans perdre son historique).
        $blocages = [];
        if (($n = $this->maintenanceRepository->countPourMateriel($materiel)) > 0) {
            $blocages[] = \sprintf('%d maintenance(s)', $n);
        }
        if (($n = $this->ticketIncidentRepository->countPourMateriel($materiel)) > 0) {
            $blocages[] = \sprintf('%d ticket(s) d\'incident', $n);
        }
        if (($n = $this->historiqueRepository->countPourMateriel($materiel)) > 0) {
            $blocages[] = \sprintf('%d entrée(s) d\'historique d\'affectation', $n);
        }
        if (($n = $this->historiqueChangementRepository->countPourMateriel($materiel)) > 0) {
            $blocages[] = \sprintf('%d entrée(s) d\'historique de changement', $n);
        }

        if ([] !== $blocages) {
            return $this->json([
                'errors' => ['materiel' => \sprintf(
                    'Impossible de supprimer ce matériel : %s y sont encore rattaché(e)s. Utilisez plutôt l\'état "Réformé" pour le retirer du service.',
                    implode(', ', $blocages),
                )],
            ], JsonResponse::HTTP_CONFLICT);
        }

        $photo = $materiel->getPhoto();

        $this->em->remove($materiel);
        $this->em->flush();

        if ($photo) {
            $this->fileStorage->delete($photo);
        }

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/materiels-informatiques/{id}/photo', name: 'api_materiel_informatique_photo_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadPhoto(MaterielInformatique $materiel, Request $request): JsonResponse
    {
        $file = $request->files->get('photoFichier');

        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['photoFichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($materiel->getPhoto()) {
            $this->fileStorage->delete($materiel->getPhoto());
        }

        $png = $this->convertirPhotoEnPng($file);
        $stocke = $this->fileStorage->storeContent($png, 'photo.png', 'png', 'materiel-informatique-photos');
        $materiel->setPhoto($stocke['path']);
        $this->em->flush();

        return $this->json($materiel, JsonResponse::HTTP_OK, [], ['groups' => self::GROUPES_LECTURE]);
    }

    #[Route('/api/materiels-informatiques/{id}/photo', name: 'api_materiel_informatique_photo', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function photo(MaterielInformatique $materiel): StreamedResponse
    {
        if (!$materiel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($materiel) {
            fpassthru($this->fileStorage->readStream($materiel->getPhoto()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($materiel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    /**
     * Export CSV du parc informatique complet — pour les rapports/audits
     * demandés à la hiérarchie, aucun autre moyen d'extraire ces données de
     * l'interface. Point-virgule (Excel FR l'attend par défaut) et BOM UTF-8
     * en tête (sans lui, Excel interprète les caractères accentués en
     * Latin-1 et les affiche mal). `fournisseur` est RH uniquement côté API
     * (groupe api:read:rh) mais reste inclus ici : cette action est déjà
     * réservée à ROLE_IT_STOCK, même périmètre que GROUPES_LECTURE.
     */
    // priority > 0 : sans ça, la route item générée par API Platform (GET /materiels-informatiques/{id})
    // matche en premier avec id="export.csv" et répond 404 avant d'atteindre cette action.
    #[Route('/api/materiels-informatiques/export.csv', name: 'api_materiel_informatique_export_csv', methods: ['GET'], priority: 10)]
    public function exportCsv(MaterielInformatiqueRepository $materielRepository): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($materielRepository) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, [
                'N° inventaire', 'Type', 'Marque', 'Modèle', 'N° série', 'N° de poste',
                'État', 'Service', 'Affecté à', 'Date de mise en service', 'Fournisseur',
                'Niveau de vulnérabilité', 'Observations',
            ], ';');

            foreach ($materielRepository->findAll() as $materiel) {
                $service = $materiel->getService();
                $affecteA = $materiel->getAffecteA();

                fputcsv($sortie, [
                    $materiel->getNumeroInventaire(),
                    $materiel->getType()?->getLibelle() ?? '',
                    $materiel->getMarque(),
                    $materiel->getModele(),
                    $materiel->getNumeroSerie() ?? '',
                    $materiel->getNumeroTelephone() ?? '',
                    $materiel->getEtat()?->getLibelle() ?? '',
                    $service?->getNom() ?? '',
                    $affecteA?->getNomComplet() ?? '',
                    $materiel->getDateMiseEnService()?->format('d/m/Y') ?? '',
                    $materiel->getFournisseur() ?? '',
                    $materiel->getNiveauVulnerabilite()?->getLibelle() ?? '',
                    $materiel->getObservations() ?? '',
                ], ';');
            }

            fclose($sortie);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'parc-informatique.csv'),
        );

        return $response;
    }

    /**
     * Changement d'état groupé — déménagement de service ou réforme groupée,
     * jusqu'ici obligés de passer matériel par matériel. `ids` ignore les
     * identifiants inconnus plutôt que d'échouer intégralement : un import
     * partiellement obsolète (matériel supprimé entre-temps côté client) ne
     * doit pas bloquer les autres.
     */
    #[Route('/api/materiels-informatiques/bulk-etat', name: 'api_materiel_informatique_bulk_etat', methods: ['PATCH'])]
    public function bulkEtat(Request $request, MaterielInformatiqueRepository $materielRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $ids = array_map('intval', $data['ids'] ?? []);
        $etatId = $data['etat'] ?? null;

        if ([] === $ids || !$etatId) {
            return $this->json(['errors' => ['ids' => 'Sélectionnez au moins un matériel et un état.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etat = $this->em->getRepository(ListeValeur::class)->find($etatId);
        if (!$etat) {
            return $this->json(['errors' => ['etat' => 'État introuvable.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $materiels = $materielRepository->findBy(['id' => $ids]);
        foreach ($materiels as $materiel) {
            $ancien = $materiel->getEtat();
            if ($ancien === $etat) {
                continue;
            }
            $materiel->setEtat($etat);
            $this->enregistrerChangement($materiel, 'État', $ancien?->getLibelle(), $etat->getLibelle());
        }
        $this->em->flush();

        return $this->json(['modifies' => \count($materiels)]);
    }

    /**
     * Réaffectation groupée — même déménagement de service que bulkEtat().
     * `affecteA` seul suffit (comme affecter()) : le service se dérive
     * automatiquement de l'agent affecté, voir MaterielInformatique::getService().
     */
    #[Route('/api/materiels-informatiques/bulk-affectation', name: 'api_materiel_informatique_bulk_affectation', methods: ['PATCH'])]
    public function bulkAffectation(Request $request, MaterielInformatiqueRepository $materielRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $ids = array_map('intval', $data['ids'] ?? []);
        $personnelId = $data['affecteA'] ?? null;

        if ([] === $ids) {
            return $this->json(['errors' => ['ids' => 'Sélectionnez au moins un matériel.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $personnel = $personnelId ? $this->personnelRepository->find($personnelId) : null;
        if ($personnelId && !$personnel) {
            return $this->json(['errors' => ['affecteA' => 'Agent introuvable.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $materiels = $materielRepository->findBy(['id' => $ids]);
        foreach ($materiels as $materiel) {
            $ancien = $materiel->getAffecteA();
            if ($ancien === $personnel) {
                continue;
            }

            $enCours = $this->historiqueRepository->findEnCoursPourMateriel($materiel);
            $enCours?->setDateFinAffectation(new \DateTimeImmutable());

            $materiel->setAffecteA($personnel);
            if ($personnel) {
                $this->ouvrirAffectation($materiel, $personnel);
            }
        }
        $this->em->flush();

        return $this->json(['modifies' => \count($materiels)]);
    }

    /**
     * QR code encodant un jeton chiffré (voir QrTokenService), pas l'id en
     * clair ni une URL directement exploitable — un scan hors de l'app GERM
     * (appareil photo classique, autre lecteur QR) ne révèle donc qu'un
     * jeton illisible dans un schéma d'URL personnalisé (germ://), pas de
     * lien http(s) cliquable ni d'information sur le matériel. Seul le
     * scanner intégré à l'app (Angular, via @capacitor-mlkit/barcode-scanning)
     * sait résoudre ce jeton, via resoudreQrcode() ci-dessous — qui reste de
     * toute façon soumis aux mêmes contrôles de rôle que le reste de ce
     * contrôleur. Imprimé sur une étiquette collée sur le poste (voir la vue
     * Étiquette côté Angular).
     */
    #[Route('/api/materiels-informatiques/{id}/qrcode', name: 'api_materiel_informatique_qrcode', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function qrcode(MaterielInformatique $materiel, QrTokenService $qrTokenService): Response
    {
        $data = 'germ://materiel/'.$qrTokenService->encoder($materiel->getId());

        $resultat = (new Builder())->build(data: $data, size: 300, margin: 10);

        $response = new Response($resultat->getString());
        $response->headers->set('Content-Type', $resultat->getMimeType());
        $response->headers->set('Cache-Control', 'private, max-age=86400');

        return $response;
    }

    /**
     * Résolution d'un jeton d'étiquette QR scanné par l'app (voir qrcode()
     * ci-dessus) — appelée par le scanner Angular après capture, jamais par
     * une navigation directe. Jeton invalide/altéré/forgé → 404, comme un
     * matériel introuvable : pas de distinction donnée à l'appelant entre
     * "jeton invalide" et "matériel supprimé depuis", pour ne rien révéler
     * sur la validité du format.
     */
    #[Route('/api/materiels-informatiques/resoudre-qrcode/{token}', name: 'api_materiel_informatique_resoudre_qrcode', methods: ['GET'])]
    public function resoudreQrcode(string $token, QrTokenService $qrTokenService, MaterielInformatiqueRepository $materielRepository): JsonResponse
    {
        $materielId = $qrTokenService->decoder($token);
        $materiel = $materielId ? $materielRepository->find($materielId) : null;

        if (!$materiel) {
            throw $this->createNotFoundException();
        }

        return $this->json(['materielId' => $materiel->getId()]);
    }

    /**
     * Création d'un ticket d'incident sur ce matériel par l'IT (constaté en
     * intervention, ou via l'action "Créer un ticket" de l'étiquette scannée)
     * — pendant de MeDemandesController::creerTicket() côté self-service, mais
     * sans la restriction "matériel affecté à l'agent connecté" puisque c'est
     * ici l'IT qui déclare, pas l'agent lui-même. `personnelId` par défaut
     * l'agent actuellement affecté ; à préciser explicitement pour un
     * matériel en stock (non affecté).
     */
    #[Route('/api/materiels-informatiques/{id}/tickets-incident', name: 'api_materiel_informatique_creer_ticket', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function creerTicket(MaterielInformatique $materiel, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $personnelId = $data['personnelId'] ?? null;
        $personnel = $personnelId ? $this->personnelRepository->find($personnelId) : $materiel->getAffecteA();
        if (!$personnel) {
            return $this->json(['errors' => ['personnelId' => "Ce matériel n'est affecté à personne : précisez l'agent concerné par l'incident."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
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

        $this->notificationService->notifierRole(
            User::ROLE_IT_RESPONSABLE,
            'Nouveau ticket d\'incident',
            '/tickets-informatique',
            \sprintf('%s a signalé un incident sur "%s %s" (%s).', $personnel->getNomComplet(), $materiel->getMarque(), $materiel->getModele(), $materiel->getNumeroInventaire()),
        );

        return $this->json($ticket, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    /**
     * Convertie en PNG quel que soit le format d'origine — même logique que
     * PersonnelController::convertirPhotoEnPng(), dupliquée ici plutôt que
     * partagée : ce n'est pas un service métier, juste une conversion de
     * fichier locale à l'action d'upload.
     */
    private function convertirPhotoEnPng(UploadedFile $file): string
    {
        $image = imagecreatefromstring(file_get_contents($file->getPathname()));

        if (false === $image) {
            throw new BadRequestHttpException("Ce fichier n'a pas pu être lu comme une image.");
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        return $png;
    }

    private function ouvrirAffectation(MaterielInformatique $materiel, Personnel $personnel): void
    {
        $affectation = new HistoriqueAffectationMateriel();
        $affectation->setMateriel($materiel);
        $affectation->setPersonnel($personnel);
        $this->em->persist($affectation);
    }

    /**
     * Libellés "avant" des champs suivis par HistoriqueChangementMateriel,
     * capturés avant que le serializer ne désérialise par-dessus l'entité
     * existante (object_to_populate) — sans cet instantané préalable, il n'y
     * aurait plus moyen de savoir ce qui a changé une fois update() arrivé au
     * flush(). `service` est délibérément absent : dérivé de `affecteA` (voir
     * MaterielInformatique::getService()), déjà couvert par le journal
     * d'affectation existant plutôt que dupliqué ici.
     *
     * @return array<string, ?string>
     */
    private function snapshotChamps(MaterielInformatique $materiel): array
    {
        return [
            'État' => $materiel->getEtat()?->getLibelle(),
            'Système d\'exploitation' => $this->libelleLicence($materiel->getSystemeExploitation()),
            'Suite bureautique' => $this->libelleLicence($materiel->getSuiteBureautique()),
            'Antivirus' => $this->libelleLicence($materiel->getAntivirus()),
        ];
    }

    /** @param array<string, ?string> $avant */
    private function enregistrerChangements(MaterielInformatique $materiel, array $avant): void
    {
        $apres = $this->snapshotChamps($materiel);
        foreach ($avant as $champ => $valeurAvant) {
            if ($valeurAvant !== $apres[$champ]) {
                $this->enregistrerChangement($materiel, $champ, $valeurAvant, $apres[$champ]);
            }
        }
    }

    private function enregistrerChangement(MaterielInformatique $materiel, string $champ, ?string $valeurAvant, ?string $valeurApres): void
    {
        $changement = new HistoriqueChangementMateriel();
        $changement->setMateriel($materiel);
        $changement->setChamp($champ);
        $changement->setValeurAvant($valeurAvant);
        $changement->setValeurApres($valeurApres);
        /** @var User $auteur */
        $auteur = $this->getUser();
        $changement->setEnregistrePar($auteur);
        $this->em->persist($changement);
    }

    private function libelleLicence(?LicenceLogiciel $licence): ?string
    {
        if (!$licence) {
            return null;
        }

        $nomLogiciel = $licence->getLogiciel()?->getLibelle() ?? '';

        return \sprintf('%s — %s', $nomLogiciel, $licence->getNumeroLicence() ?: 'sans n°');
    }
}
