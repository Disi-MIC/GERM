<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Import\ImportRunner;
use App\Import\TypeImport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/import', name: 'admin_import_')]
#[IsGranted('ROLE_SUPERADMIN')]
class ImportController extends AbstractController
{
    #[Route('/{type}', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(TypeImport $type, Request $request, ImportRunner $runner): Response
    {
        $report = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('import-'.$type->value, $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');
            } else {
                $file = $request->files->get('file');

                if (null === $file) {
                    $this->addFlash('danger', 'Merci de sélectionner un fichier à importer.');
                } else {
                    $report = $runner->run($type, $file);
                }
            }
        }

        return $this->render('admin/import/upload.html.twig', [
            'type' => $type,
            'columns' => $runner->getColumns($type),
            'report' => $report,
        ]);
    }

    #[Route('/{type}/modele', name: 'template', methods: ['GET'])]
    public function template(TypeImport $type, ImportRunner $runner): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($type, $runner) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($runner->generateTemplateRows($type) as $ligne) {
                fputcsv($handle, $ligne, ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="modele-%s.csv"', $type->value));

        return $response;
    }
}
