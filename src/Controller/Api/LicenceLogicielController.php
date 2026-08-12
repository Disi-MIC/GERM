<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\LicenceLogiciel;
use App\Repository\MaterielInformatiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur le registre des licences logicielles côté frontend Angular.
 * La ressource LicenceLogiciel est exposée en lecture seule via API Platform
 * (journal, pas d'édition — même logique que Maintenance) : la création et
 * la suppression passent par ces actions dédiées.
 */
#[IsGranted('ROLE_IT_STOCK')]
class LicenceLogicielController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly MaterielInformatiqueRepository $materielInformatiqueRepository,
    ) {
    }

    #[Route('/api/licences-logicielles', name: 'api_licence_logiciel_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $licence = $this->serializer->deserialize($request->getContent(), LicenceLogiciel::class, 'json', ['groups' => ['api:write']]);

        // dateExpiration n'est jamais acceptée du client (pas de groupe
        // api:write dessus, voir l'entité) : calculée ici à partir de
        // dateDebut + dureeMois, pour éviter toute incohérence entre les deux.
        if ($licence->getDateDebut() && $licence->getDureeMois()) {
            $licence->setDateExpiration($licence->getDateDebut()->modify(\sprintf('+%d months', $licence->getDureeMois())));
        }

        $violations = $this->validator->validate($licence);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->em->persist($licence);
        $this->em->flush();

        // Contourne l'API Platform native (LicenceLogicielProvider) puisque
        // cette création passe par une action dédiée : même calcul que le
        // provider, pour renvoyer une réponse cohérente avec les lectures.
        // Toujours 0 en pratique (aucun matériel ne peut encore pointer vers
        // une licence qui vient d'être créée), mais garde le même calcul que
        // les lectures plutôt qu'une valeur codée en dur.
        $licence->setNombrePostes($this->materielInformatiqueRepository->countParLicence($licence));

        return $this->json($licence, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/licences-logicielles/{id}', name: 'api_licence_logiciel_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(LicenceLogiciel $licence): JsonResponse
    {
        // Un matériel peut pointer directement vers cette licence (voir
        // MaterielInformatique::$systemeExploitation/$suiteBureautique/$antivirus,
        // en RESTRICT) : sans ce contrôle, la suppression lèverait une erreur
        // SQL brute au lieu d'un message exploitable côté formulaire.
        $nombrePostes = $this->materielInformatiqueRepository->countParLicence($licence);
        if ($nombrePostes > 0) {
            return $this->json([
                'errors' => ['licence' => \sprintf(
                    'Impossible de supprimer cette licence : %d matériel(s) y sont encore rattachés. Retirez-les d\'abord du matériel concerné.',
                    $nombrePostes,
                )],
            ], JsonResponse::HTTP_CONFLICT);
        }

        $this->em->remove($licence);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
