<?php

namespace App\Controller\Api;

use App\Entity\DocumentAdministratif;
use App\Service\DocumentAiSummaryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RH_PERSONNEL')]
class DocumentAdministratifAiController
{
    #[Route('/api/documents-administratifs/{id}/resume-ia', name: 'api_document_administratif_resume_ia', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resume(DocumentAdministratif $document, DocumentAiSummaryService $ai): JsonResponse
    {
        try {
            return new JsonResponse([
                'summary' => $ai->resumePdf($document),
                'documentId' => $document->getId(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_GATEWAY);
        } catch (\Throwable) {
            return new JsonResponse(
                ['message' => "Le service d'analyse IA est indisponible. Réessayez plus tard."],
                JsonResponse::HTTP_BAD_GATEWAY,
            );
        }
    }
}
