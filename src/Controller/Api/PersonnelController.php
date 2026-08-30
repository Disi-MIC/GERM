<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\HistoriqueChangementPersonnel;
use App\Entity\Personnel;
use App\Entity\User;
use App\Repository\HistoriqueChangementPersonnelRepository;
use App\Repository\PersonnelRepository;
use App\Service\FileStorage;
use App\Service\PersonnelPhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le personnel côté frontend Angular. La ressource Personnel
 * est exposée en lecture seule via API Platform (voir l'entité) : la
 * création (avec son mouvement de carrière initial), l'édition, la
 * suppression (avec son garde-fou métier) et la photo passent par ces
 * actions dédiées — même logique que PersonnelController côté Twig.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class PersonnelController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly PersonnelPhotoService $photoService,
        private readonly HistoriqueChangementPersonnelRepository $historiqueChangementRepository,
    ) {
    }

    #[Route('/api/personnels', name: 'api_personnel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $personnel = $this->serializer->deserialize($request->getContent(), Personnel::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($personnel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($personnel);

        $nomination = new HistoriqueAffectation();
        $nomination->setPersonnel($personnel);
        $nomination->setService($personnel->getService());
        $nomination->setFonction($personnel->getFonction());
        $nomination->setGrade($personnel->getGrade());
        $nomination->setTypeMouvement(TypeMouvementCarriere::NOMINATION);
        $nomination->setDateEffet($personnel->getDateEmbauche() ?? new \DateTimeImmutable());
        $this->em->persist($nomination);

        $this->em->flush();

        return $this->json($personnel, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    /**
     * Export CSV des effectifs — pour les rapports demandés à la hiérarchie,
     * même logique que MaterielInformatiqueController::exportCsv().
     */
    // priority > 0 : sans ça, la route item générée par API Platform (GET /personnels/{id})
    // matche en premier avec id="export.csv" et répond 404 avant d'atteindre cette action.
    #[Route('/api/personnels/export.csv', name: 'api_personnel_export_csv', methods: ['GET'], priority: 10)]
    public function exportCsv(PersonnelRepository $personnelRepository): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($personnelRepository) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, [
                'Matricule', 'Nom', 'Prénom', 'Sexe', 'Date de naissance', 'Fonction', 'Grade',
                'Type de contrat', 'Date d\'embauche', 'Statut', 'Direction', 'Service',
                'Téléphone', 'Email',
            ], ';');

            foreach ($personnelRepository->findAll() as $personnel) {
                $service = $personnel->getService();

                fputcsv($sortie, [
                    $personnel->getMatricule() ?? '',
                    $personnel->getNom(),
                    $personnel->getPrenom(),
                    $personnel->getSexe()?->value ?? '',
                    $personnel->getDateNaissance()?->format('d/m/Y') ?? '',
                    $personnel->getFonction(),
                    $personnel->getGrade() ?? '',
                    $personnel->getTypeContrat()?->getLibelle() ?? '',
                    $personnel->getDateEmbauche()?->format('d/m/Y') ?? '',
                    $personnel->getStatut()?->label() ?? '',
                    $service?->getDirection()?->getNom() ?? '',
                    $service?->getNom() ?? '',
                    $personnel->getTelephone() ?? '',
                    $personnel->getEmail() ?? '',
                ], ';');
            }

            fclose($sortie);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'effectifs.csv'),
        );

        return $response;
    }

    #[Route('/api/personnels/{id}', name: 'api_personnel_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Personnel $personnel, Request $request): JsonResponse
    {
        $avant = $this->snapshotChamps($personnel);

        $this->serializer->deserialize($request->getContent(), Personnel::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $personnel,
        ]);

        $violations = $this->validator->validate($personnel);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->enregistrerChangements($personnel, $avant);

        $this->em->flush();

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/personnels/{id}', name: 'api_personnel_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Personnel $personnel): JsonResponse
    {
        if (
            !$personnel->getHistoriqueAffectations()->isEmpty()
            || !$personnel->getConges()->isEmpty()
            || !$personnel->getDemandesJouissance()->isEmpty()
            || !$personnel->getDecisionsConge()->isEmpty()
            || !$personnel->getDemandesDecision()->isEmpty()
            || !$personnel->getCartesProfessionnelles()->isEmpty()
            || !$personnel->getDemandesCartePro()->isEmpty()
            || $this->historiqueChangementRepository->countPourPersonnel($personnel) > 0
        ) {
            return $this->json(['errors' => ['personnel' => 'Impossible de supprimer cette fiche : elle a un historique de carrière, des congés, des décisions, des demandes de congé, des cartes professionnelles, des demandes de carte professionnelle ou un historique de changements enregistrés.']], JsonResponse::HTTP_CONFLICT);
        }

        if ($personnel->getUser()) {
            return $this->json(['errors' => ['personnel' => "Impossible de supprimer cette fiche : elle est liée à un compte de connexion. Merci de délier ou supprimer d'abord ce compte."]], JsonResponse::HTTP_CONFLICT);
        }

        $photo = $personnel->getPhoto();

        $this->em->remove($personnel);
        $this->em->flush();

        if ($photo) {
            $this->fileStorage->delete($photo);
        }

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/personnels/{id}/photo', name: 'api_personnel_photo_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadPhoto(Personnel $personnel, Request $request): JsonResponse
    {
        $file = $request->files->get('photoFichier');

        if ($erreur = $this->photoService->erreurValidation($file)) {
            return $this->json(['errors' => ['photoFichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->photoService->remplacer($personnel, $file);

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/personnels/{id}/photo', name: 'api_personnel_photo', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function photo(Personnel $personnel): StreamedResponse
    {
        if (!$personnel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($personnel) {
            fpassthru($this->fileStorage->readStream($personnel->getPhoto()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($personnel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    /**
     * Libellés "avant" des champs suivis par HistoriqueChangementPersonnel,
     * capturés avant que le serializer ne désérialise par-dessus l'entité
     * existante (object_to_populate) — même principe que
     * MaterielInformatiqueController::snapshotChamps(). `service` est
     * volontairement absent : les mouvements de service passent par
     * HistoriqueAffectation (mouvement de carrière formel), déjà tracés là.
     *
     * @return array<string, ?string>
     */
    private function snapshotChamps(Personnel $personnel): array
    {
        return [
            'Nom' => $personnel->getNom(),
            'Prénom' => $personnel->getPrenom(),
            'Matricule' => $personnel->getMatricule(),
            'Statut' => $personnel->getStatut()?->label(),
            'Fonction' => $personnel->getFonction(),
            'Grade' => $personnel->getGrade(),
            'Type de contrat' => $personnel->getTypeContrat()?->getLibelle(),
        ];
    }

    /** @param array<string, ?string> $avant */
    private function enregistrerChangements(Personnel $personnel, array $avant): void
    {
        $apres = $this->snapshotChamps($personnel);
        foreach ($avant as $champ => $valeurAvant) {
            if ($valeurAvant !== $apres[$champ]) {
                $changement = new HistoriqueChangementPersonnel();
                $changement->setPersonnel($personnel);
                $changement->setChamp($champ);
                $changement->setValeurAvant($valeurAvant);
                $changement->setValeurApres($apres[$champ]);
                /** @var User $auteur */
                $auteur = $this->getUser();
                $changement->setEnregistrePar($auteur);
                $this->em->persist($changement);
            }
        }
    }
}
