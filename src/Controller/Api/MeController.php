<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\Personnel;
use App\Entity\User;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\CongeRepository;
use App\Repository\DemandeCarteProRepository;
use App\Repository\HistoriqueAffectationRepository;
use App\Repository\MaterielInformatiqueRepository;
use App\Repository\VehiculeRepository;
use App\Service\FileStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Espace libre-service de l'utilisateur connecté ("Mon espace") : profil
 * personnel, carrière, parc informatique/automobile affecté, congés et carte
 * professionnelle — tout en lecture seule, filtré sur sa propre fiche
 * Personnel. Accessible à tout agent (ROLE_AGENT, le rôle de base de tout
 * compte), y compris ceux sans rôle RH. Utilisé aussi juste après la
 * connexion pour savoir vers quelle rubrique se rediriger (/api/me).
 */
#[IsGranted('ROLE_AGENT')]
class MeController extends AbstractController
{
    public function __construct(
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function moi(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/me/personnel', name: 'api_me_personnel', methods: ['GET'])]
    public function personnel(): JsonResponse
    {
        $personnel = $this->personnelConnecte();

        if (!$personnel) {
            return $this->json(['errors' => ['personnel' => "Aucune fiche personnel n'est liée à votre compte. Merci de contacter le service RH."]], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($personnel, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/personnel/photo', name: 'api_me_personnel_photo', methods: ['GET'])]
    public function personnelPhoto(): StreamedResponse
    {
        $personnel = $this->personnelConnecte();

        if (!$personnel || !$personnel->getPhoto()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($personnel) {
            fpassthru($this->fileStorage->readStream($personnel->getPhoto()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($personnel->getPhoto()));
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    #[Route('/api/me/historique-affectations', name: 'api_me_historique_affectations', methods: ['GET'])]
    public function carriere(HistoriqueAffectationRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $mouvements = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateEffet' => 'DESC']) : [];

        return $this->json($mouvements, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/conges', name: 'api_me_conges', methods: ['GET'])]
    public function conges(CongeRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $conges = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateDebut' => 'DESC']) : [];

        return $this->json($conges, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/cartes-professionnelles', name: 'api_me_cartes_professionnelles', methods: ['GET'])]
    public function cartesProfessionnelles(CarteProfessionnelleRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $cartes = $personnel ? $repository->findBy(['personnel' => $personnel], ['dateDelivrance' => 'DESC']) : [];

        return $this->json($cartes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/demandes-carte-pro', name: 'api_me_demandes_carte_pro', methods: ['GET'])]
    public function demandesCartePro(DemandeCarteProRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $demandes = $personnel ? $repository->findBy(['personnel' => $personnel], ['createdAt' => 'DESC']) : [];

        return $this->json($demandes, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/materiels', name: 'api_me_materiels', methods: ['GET'])]
    public function materiels(MaterielInformatiqueRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $materiels = $personnel ? $repository->findBy(['affecteA' => $personnel]) : [];

        return $this->json($materiels, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/me/vehicules', name: 'api_me_vehicules', methods: ['GET'])]
    public function vehicules(VehiculeRepository $repository): JsonResponse
    {
        $personnel = $this->personnelConnecte();
        $vehicules = $personnel ? $repository->findBy(['chauffeurAffecte' => $personnel]) : [];

        return $this->json($vehicules, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    private function personnelConnecte(): ?Personnel
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getPersonnel();
    }
}
