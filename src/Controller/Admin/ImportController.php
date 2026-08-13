<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Import\ImportRunner;
use App\Import\TypeImport;
use App\Import\XlsxTemplateGenerator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
    public function template(TypeImport $type, ImportRunner $runner, XlsxTemplateGenerator $generator): StreamedResponse
    {
        $spreadsheet = $generator->generate($type, $runner->getColumns($type));

        $response = new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="modele-%s.xlsx"', $type->value));

        return $response;
    }
}
