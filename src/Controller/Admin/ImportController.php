<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\LienGoogleSheet;
use App\Import\GoogleSheetUrlResolver;
use App\Import\ImportRunner;
use App\Import\TypeImport;
use App\Import\XlsxTemplateGenerator;
use App\Repository\LienGoogleSheetRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function upload(TypeImport $type, Request $request, ImportRunner $runner, LienGoogleSheetRepository $lienRepository): Response
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
            'lien' => $lienRepository->findOneByType($type),
        ]);
    }

    /**
     * Enregistre (ou remplace) le lien vers un classeur Google Sheets pour
     * cette rubrique — n'importe rien tout de suite, juste la liaison ;
     * voir synchroniserGoogleSheet() pour l'import proprement dit.
     */
    #[Route('/{type}/google-sheet', name: 'google_sheet_lier', methods: ['POST'])]
    public function lierGoogleSheet(
        TypeImport $type,
        Request $request,
        EntityManagerInterface $em,
        LienGoogleSheetRepository $lienRepository,
        GoogleSheetUrlResolver $resolver,
    ): Response {
        if (!$this->isCsrfTokenValid('import-google-sheet-'.$type->value, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
        }

        $url = trim((string) $request->request->get('url'));
        if ('' === $url) {
            $this->addFlash('danger', 'Merci de coller le lien de partage du classeur Google Sheets.');

            return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
        }

        try {
            // Valide le format tout de suite plutôt que d'attendre la première synchronisation.
            $resolver->resoudreUrlExportCsv($url);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
        }

        $lien = $lienRepository->findOneByType($type);
        if (null === $lien) {
            $lien = new LienGoogleSheet($type, $url);
            $em->persist($lien);
        } else {
            $lien->setUrl($url);
        }
        $em->flush();

        $this->addFlash('success', 'Classeur Google Sheets lié. Cliquez sur "Synchroniser maintenant" pour importer les lignes.');

        return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
    }

    /**
     * Relance l'import depuis le classeur déjà lié, sans avoir à recoller l'URL.
     */
    #[Route('/{type}/google-sheet/synchroniser', name: 'google_sheet_synchroniser', methods: ['POST'])]
    public function synchroniserGoogleSheet(
        TypeImport $type,
        Request $request,
        ImportRunner $runner,
        EntityManagerInterface $em,
        LienGoogleSheetRepository $lienRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('import-google-sheet-sync-'.$type->value, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
        }

        $lien = $lienRepository->findOneByType($type);
        if (null === $lien) {
            $this->addFlash('danger', "Aucun classeur Google Sheets n'est lié à cette rubrique.");

            return $this->redirectToRoute('admin_import_upload', ['type' => $type->value]);
        }

        $report = $runner->runFromGoogleSheet($type, $lien->getUrl());
        if (null === $report->globalError) {
            $lien->marquerSynchronise();
            $em->flush();
        }

        return $this->render('admin/import/upload.html.twig', [
            'type' => $type,
            'columns' => $runner->getColumns($type),
            'report' => $report,
            'lien' => $lien,
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
