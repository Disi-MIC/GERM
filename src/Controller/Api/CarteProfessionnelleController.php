<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\CarteProfessionnelle;
use App\Repository\CarteProfessionnelleRepository;
use App\Service\CarteProfessionnellePdfStockageService;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les cartes professionnelles côté frontend Angular. La
 * ressource CarteProfessionnelle est exposée en lecture seule via API
 * Platform (voir l'entité) : toute création/modification/suppression passe
 * par ces actions dédiées, qui reprennent la logique déjà validée côté Twig
 * (génération PDF/QR partagée, garde-fou de suppression de la photo de
 * l'agent).
 */
#[IsGranted('ROLE_RH_CARTE_PRO')]
class CarteProfessionnelleController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly CarteProfessionnellePdfStockageService $pdfStockage,
        private readonly FileStorage $fileStorage,
    ) {
    }

    #[Route('/api/cartes-professionnelles', name: 'api_carte_professionnelle_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $carte = $this->serializer->deserialize($request->getContent(), CarteProfessionnelle::class, 'json', ['groups' => ['api:write']]);

        $violations = $this->validator->validate($carte);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->pdfStockage->genererEtStocker($carte);
        $this->em->persist($carte);
        $this->em->flush();

        return $this->json($carte, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    /** Export CSV du registre des cartes professionnelles — même logique que PersonnelController::exportCsv(). */
    // priority > 0 : même raison que PersonnelController::exportCsv().
    #[Route('/api/cartes-professionnelles/export.csv', name: 'api_carte_professionnelle_export_csv', methods: ['GET'], priority: 10)]
    public function exportCsv(CarteProfessionnelleRepository $carteRepository): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($carteRepository) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, [
                'Numéro', 'Agent', 'Date de délivrance', 'Date d\'expiration', 'Statut',
                'Validée par RH Admin', 'Validée le', 'Validée par',
            ], ';');

            foreach ($carteRepository->findAll() as $carte) {
                fputcsv($sortie, [
                    $carte->getNumero(),
                    $carte->getPersonnel()?->getNomComplet() ?? '',
                    $carte->getDateDelivrance()?->format('d/m/Y') ?? '',
                    $carte->getDateExpiration()?->format('d/m/Y') ?? '',
                    $carte->getStatutAffiche()['label'],
                    $carte->isValideeParAdminRh() ? 'Oui' : 'Non',
                    $carte->getValideeLe()?->format('d/m/Y') ?? '',
                    $carte->getValideeParNom() ?? '',
                ], ';');
            }

            fclose($sortie);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'cartes-professionnelles.csv'),
        );

        return $response;
    }

    #[Route('/api/cartes-professionnelles/{id}', name: 'api_carte_professionnelle_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(CarteProfessionnelle $carte, Request $request): JsonResponse
    {
        $this->serializer->deserialize($request->getContent(), CarteProfessionnelle::class, 'json', [
            'groups' => ['api:write'],
            'object_to_populate' => $carte,
        ]);

        $violations = $this->validator->validate($carte);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $this->pdfStockage->genererEtStocker($carte);
        $this->em->flush();

        return $this->json($carte, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/cartes-professionnelles/{id}', name: 'api_carte_professionnelle_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(CarteProfessionnelle $carte): JsonResponse
    {
        $personnel = $carte->getPersonnel();

        if ($carte->getCheminFichier()) {
            $this->fileStorage->delete($carte->getCheminFichier());
        }
        if ($carte->getCheminQrCode()) {
            $this->fileStorage->delete($carte->getCheminQrCode());
        }

        // Si c'est la dernière carte de l'agent, sa photo (partagée entre
        // toutes ses cartes, y compris futures) n'a plus d'usage : on la
        // supprime aussi. Si d'autres cartes subsistent, elle est conservée.
        $derniereCarte = 1 === $personnel->getCartesProfessionnelles()->count();

        $this->em->remove($carte);
        $this->em->flush();

        if ($derniereCarte && $personnel->getPhoto()) {
            $this->fileStorage->delete($personnel->getPhoto());
            $personnel->setPhoto(null);
            $this->em->flush();
        }

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/cartes-professionnelles/{id}/generer', name: 'api_carte_professionnelle_generer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generer(CarteProfessionnelle $carte): JsonResponse
    {
        $this->pdfStockage->genererEtStocker($carte);
        $this->em->flush();

        return $this->json($carte, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/cartes-professionnelles/{id}/valider', name: 'api_carte_professionnelle_valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_RH_RESPONSABLE')]
    public function valider(CarteProfessionnelle $carte): JsonResponse
    {
        /** @var \App\Entity\User $validateur */
        $validateur = $this->getUser();
        $carte->valider($validateur);
        $this->pdfStockage->genererEtStocker($carte);
        $this->em->flush();

        return $this->json($carte, JsonResponse::HTTP_OK, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/cartes-professionnelles/{id}/pdf', name: 'api_carte_professionnelle_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(CarteProfessionnelle $carte): StreamedResponse
    {
        return $this->streamPdf($carte, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/api/cartes-professionnelles/{id}/pdf/telecharger', name: 'api_carte_professionnelle_pdf_telecharger', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdfTelecharger(CarteProfessionnelle $carte): StreamedResponse
    {
        if (!$carte->isValideeParAdminRh()) {
            throw new AccessDeniedHttpException("La carte doit d'abord être validée par le RH Admin pour être téléchargeable.");
        }

        return $this->streamPdf($carte, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function streamPdf(CarteProfessionnelle $carte, string $disposition): StreamedResponse
    {
        if (!$carte->getCheminFichier()) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(function () use ($carte) {
            fpassthru($this->fileStorage->readStream($carte->getCheminFichier()));
        });
        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('Carte professionnelle %s - %s', $carte->getNumero(), $carte->getPersonnel()?->getNomComplet() ?? ''),
            'pdf',
        );
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($carte->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, $nom));

        return $response;
    }
}
