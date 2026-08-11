<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\HistoriqueAffectationMateriel;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Repository\HistoriqueAffectationMaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le parc informatique côté frontend Angular. La ressource
 * MaterielInformatique est exposée en lecture seule via API Platform (voir
 * l'entité) : la création, l'édition et la suppression passent par ces
 * actions dédiées — même logique que PersonnelController. Les groupes
 * api:read:rh/api:write:rh (valeurAcquisition/fournisseur) sont demandés en
 * plus des groupes de base ici, contrairement à la vue self-service "Mon
 * parc informatique" (MeController::materiels()) qui ne les demande jamais.
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
        $this->em->remove($materiel);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function ouvrirAffectation(MaterielInformatique $materiel, Personnel $personnel): void
    {
        $affectation = new HistoriqueAffectationMateriel();
        $affectation->setMateriel($materiel);
        $affectation->setPersonnel($personnel);
        $this->em->persist($affectation);
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
