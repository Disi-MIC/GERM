<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\HistoriqueAffectationMateriel;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Repository\HistoriqueAffectationMaterielRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\TicketIncidentRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly MaintenanceRepository $maintenanceRepository,
        private readonly TicketIncidentRepository $ticketIncidentRepository,
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
}
