<?php

namespace App\Controller\Api;

use App\Controller\AbstractController;
use App\Entity\DocumentAdministratif;
use App\Repository\ListeValeurRepository;
use App\Repository\PersonnelRepository;
use App\Service\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Écritures sur les documents administratifs (DocumentAdministratif) côté
 * frontend Angular. La ressource est exposée en lecture seule via API
 * Platform : contrairement aux pièces jointes des demandes (facultatives, donc
 * envoyées en un second appel), un document a toujours un fichier dès sa
 * création — la création se fait donc ici en un seul envoi multipart
 * (métadonnées + fichier), pas de désérialisation JSON classique.
 */
#[IsGranted('ROLE_RH_PERSONNEL')]
class DocumentAdministratifController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly FileStorage $fileStorage,
        private readonly PersonnelRepository $personnelRepository,
        private readonly ListeValeurRepository $listeValeurRepository,
    ) {
    }

    #[Route('/api/documents-administratifs', name: 'api_document_administratif_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $personnel = $this->personnelRepository->find((int) $request->request->get('personnel'));
        if (!$personnel) {
            return $this->json(['errors' => ['personnel' => "L'agent est obligatoire."]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $this->listeValeurRepository->find((int) $request->request->get('type'));
        if (!$type) {
            return $this->json(['errors' => ['type' => 'Le type de document est obligatoire.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->files->get('fichier');
        if ($erreur = $this->fileStorage->erreurValidation($file)) {
            return $this->json(['errors' => ['fichier' => $erreur]], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $document = new DocumentAdministratif();
        $document->setPersonnel($personnel);
        $document->setType($type);
        $document->setLibelle((string) $request->request->get('libelle'));
        $document->setDateDocument($this->parseDate($request->request->get('dateDocument')));
        $document->setDateExpiration($this->parseDate($request->request->get('dateExpiration')));
        $document->setObservations($request->request->get('observations') ?: null);

        $violations = $this->validator->validate($document);
        if (\count($violations) > 0) {
            return $this->violationsResponse($violations);
        }

        $stocke = $this->fileStorage->store($file, 'document-administratif');
        $document->setCheminFichier($stocke['path']);
        $document->setNomOriginal($stocke['originalName']);

        $this->em->persist($document);
        $this->em->flush();

        return $this->json($document, JsonResponse::HTTP_CREATED, [], ['groups' => ['api:read']]);
    }

    #[Route('/api/documents-administratifs/{id}', name: 'api_document_administratif_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(DocumentAdministratif $document): JsonResponse
    {
        $this->fileStorage->delete($document->getCheminFichier());
        $this->em->remove($document);
        $this->em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/documents-administratifs/{id}/fichier', name: 'api_document_administratif_fichier', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function fichier(DocumentAdministratif $document): StreamedResponse
    {
        $nom = $this->fileStorage->nomTelechargement(
            \sprintf('%s - %s', $document->getLibelle(), $document->getPersonnel()?->getNomComplet() ?? ''),
            pathinfo($document->getCheminFichier(), \PATHINFO_EXTENSION),
        );
        $response = new StreamedResponse(function () use ($document) {
            fpassthru($this->fileStorage->readStream($document->getCheminFichier()));
        });
        $response->headers->set('Content-Type', $this->fileStorage->mimeType($document->getCheminFichier()));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom));

        return $response;
    }

    private function parseDate(?string $date): ?\DateTimeImmutable
    {
        if (!$date) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: null;
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
