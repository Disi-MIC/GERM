<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Vehicule;
use App\Repository\BonEssenceRepository;
use App\Repository\HistoriqueVidangeRepository;
use App\Service\VehiculePdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le parc automobile côté frontend Angular. Comme
 * MaterielInformatiqueController : la ressource Vehicule reste exposée en
 * lecture seule via API Platform (voir l'entité), création/édition/
 * suppression passent par ces actions dédiées. Réservé au superadmin
 * (ROLE_SUPERADMIN) — aucun rôle dédié à la gestion du parc automobile
 * n'existe encore, contrairement au parc informatique/RH ; même périmètre
 * que l'admin Twig historique (Controller/Admin/VehiculeController), qui
 * reste en place à côté.
 */
#[IsGranted('ROLE_SUPERADMIN')]
class VehiculeController extends AbstractController
{
    private const GROUPES_LECTURE = ['api:read', 'api:read:admin'];
    private const GROUPES_ECRITURE = ['api:write'];

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly HistoriqueVidangeRepository $historiqueVidangeRepository,
        private readonly BonEssenceRepository $bonEssenceRepository,
    ) {
    }

    #[Route('/api/vehicules', name: 'api_vehicule_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $vehicule = $this->serializer->deserialize($request->getContent(), Vehicule::class, 'json', ['groups' => self::GROUPES_ECRITURE]);

        $violations = $this->validator->validate($vehicule);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($vehicule);
        $this->em->flush();

        return $this->json($vehicule, JsonResponse::HTTP_CREATED, [], ['groups' => self::GROUPES_LECTURE]);
    }

    #[Route('/api/vehicules/{id}', name: 'api_vehicule_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Vehicule $vehicule, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), Vehicule::class, 'json', [
            'groups' => self::GROUPES_ECRITURE,
            'object_to_populate' => $vehicule,
        ]);

        $violations = $this->validator->validate($vehicule);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->flush();

        return $this->json($vehicule, JsonResponse::HTTP_OK, [], ['groups' => self::GROUPES_LECTURE]);
    }

    #[Route('/api/vehicules/{id}', name: 'api_vehicule_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Vehicule $vehicule): JsonResponse
    {
        // Vidanges et bons d'essence référencent ce véhicule sans cascade :
        // même garde-fou que MaterielInformatiqueController pour la
        // maintenance/les tickets — sans lui, la suppression lèverait une
        // erreur SQL brute dès qu'un journal existe.
        $blocages = [];
        if (($n = $this->historiqueVidangeRepository->countPourVehicule($vehicule)) > 0) {
            $blocages[] = \sprintf('%d vidange(s)', $n);
        }
        if (($n = $this->bonEssenceRepository->countPourVehicule($vehicule)) > 0) {
            $blocages[] = \sprintf('%d bon(s) d\'essence', $n);
        }

        if ([] !== $blocages) {
            return $this->json([
                'errors' => ['vehicule' => \sprintf(
                    'Impossible de supprimer ce véhicule : %s y sont encore rattaché(e)s. Utilisez plutôt l\'état "Réformé" pour le retirer du service.',
                    implode(', ', $blocages),
                )],
            ], JsonResponse::HTTP_CONFLICT);
        }

        $this->em->remove($vehicule);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Carte du véhicule — document PDF généré à la volée (pas de stockage,
     * contrairement à la carte professionnelle du personnel qui suit un
     * workflow de validation) : régénérée à chaque consultation, toujours à
     * jour, sans champ de suivi de version ni action de régénération à
     * exposer côté frontend.
     */
    #[Route('/api/vehicules/{id}/carte', name: 'api_vehicule_carte', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function carte(Vehicule $vehicule, VehiculePdfGenerator $pdfGenerator): Response
    {
        $response = new Response($pdfGenerator->generate($vehicule));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
