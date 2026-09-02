<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\ComposantMateriel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les composants matériels (RAM, disque dur, carte graphique...)
 * d'un MaterielInformatique. La ressource ComposantMateriel est exposée en
 * lecture seule via API Platform (même logique que LicenceLogiciel) : la
 * création, l'édition et la suppression passent par ces actions dédiées.
 * Contrairement à LicenceLogiciel/HistoriqueAffectationMateriel (simples
 * journaux append-only), un composant reste modifiable après coup — d'où
 * l'action update() en plus de create()/delete().
 */
#[IsGranted('ROLE_IT_STOCK')]
class ComposantMaterielController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/composants-materiel', name: 'api_composant_materiel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $composant = $this->serializer->deserialize($request->getContent(), ComposantMateriel::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($composant);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($composant);
        $this->em->flush();

        return $this->json($composant, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/composants-materiel/{id}', name: 'api_composant_materiel_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(ComposantMateriel $composant, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), ComposantMateriel::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $composant,
        ]);

        $violations = $this->validator->validate($composant);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($composant, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/composants-materiel/{id}', name: 'api_composant_materiel_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(ComposantMateriel $composant): JsonResponse
    {
        $this->em->remove($composant);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
