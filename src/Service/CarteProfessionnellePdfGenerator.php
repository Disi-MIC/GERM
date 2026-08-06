<?php

namespace App\Service;

use App\Entity\CarteProfessionnelle;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Génère le PDF de la carte professionnelle d'un agent (format ID-1, mise en
 * page inspirée de la CIN sénégalaise) ainsi que le QR code associé. Ne
 * s'occupe que du rendu ; le stockage des fichiers résultants sur le SFTP est
 * à la charge de l'appelant via FileStorage.
 *
 * Le recto et le verso sont rendus séparément (chacun sur une toile plus
 * grande que la carte, voir carte_professionnelle_face.html.twig) puis
 * recadrés à la taille réelle CR80/ID-1 (85,6×53,98mm) et fusionnés en un
 * PDF à 2 pages via FPDI. Rendre chaque face sur une toile plus grande que
 * la carte évite un bug de dompdf qui insère une page blanche quand un bloc
 * est dimensionné exactement comme la page — en recadrant après coup, la
 * carte imprimée reste malgré tout parfaitement bord-à-bord (pas de marge
 * blanche), ce qui est nécessaire pour une impression sur imprimante à
 * carte PVC/à puce (format ID-1 exact, sans bordure superflue).
 */
class CarteProfessionnellePdfGenerator
{
    private const LARGEUR_PT = 242.65;
    private const HAUTEUR_PT = 153.02;
    private const MARGE_TOILE_PT = 8.0;

    public function __construct(
        private readonly Environment $twig,
        private readonly FileStorage $fileStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{pdf: string, qrCode: string}
     */
    public function generate(CarteProfessionnelle $carte): array
    {
        $personnel = $carte->getPersonnel();

        $photoDataUri = null;
        if ($personnel?->getPhoto()) {
            $stream = $this->fileStorage->readStream($personnel->getPhoto());
            $contenu = stream_get_contents($stream);
            fclose($stream);
            $photoDataUri = 'data:'.$this->fileStorage->mimeType($personnel->getPhoto()).';base64,'.base64_encode($contenu);
        }

        $qrCodeBinary = $this->genererQrCode($carte);

        $donneesCommunes = [
            'carte' => $carte,
            'personnel' => $personnel,
            'photoDataUri' => $photoDataUri,
            'logoMiniDataUri' => $this->imageDataUri('logo-mini.png'),
            'filigraneDataUri' => $this->imageDataUri('logo-mincom.png'),
            'flagDataUri' => $this->imageDataUri('flag.png'),
            'cachetDataUri' => $this->imageDataUri('cachet.png'),
            'stampDataUri' => $this->imageDataUri('stamp.png'),
            'signDataUri' => $this->imageDataUri('sign.png'),
            'qrCodeDataUri' => 'data:image/png;base64,'.base64_encode($qrCodeBinary),
        ];

        $rectoPdf = $this->rendreFace('recto', $donneesCommunes);
        $versoPdf = $this->rendreFace('verso', $donneesCommunes);

        return [
            'pdf' => $this->fusionnerFaces($rectoPdf, $versoPdf),
            'qrCode' => $qrCodeBinary,
        ];
    }

    private function rendreFace(string $face, array $donneesCommunes): string
    {
        $html = $this->twig->render('pdf/carte_professionnelle_face.html.twig', ['face' => $face] + $donneesCommunes);

        $options = new Options();
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper([
            0, 0,
            self::LARGEUR_PT + 2 * self::MARGE_TOILE_PT,
            self::HAUTEUR_PT + 2 * self::MARGE_TOILE_PT,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Recadre chaque face sur la toile rendue par dompdf (qui inclut une
     * marge de sécurité, voir rendreFace()) pour ne garder que le format
     * CR80/ID-1 exact, puis assemble recto+verso en un seul PDF à 2 pages.
     */
    private function fusionnerFaces(string $rectoPdf, string $versoPdf): string
    {
        $fpdi = new Fpdi('L', 'pt', [self::LARGEUR_PT, self::HAUTEUR_PT]);

        foreach ([$rectoPdf, $versoPdf] as $facePdf) {
            $fpdi->setSourceFile(StreamReader::createByString($facePdf));
            $templateId = $fpdi->importPage(1);
            $fpdi->AddPage('L', [self::LARGEUR_PT, self::HAUTEUR_PT]);
            $fpdi->useTemplate($templateId, -self::MARGE_TOILE_PT, -self::MARGE_TOILE_PT);
        }

        return $fpdi->Output('S');
    }

    private function genererQrCode(CarteProfessionnelle $carte): string
    {
        $url = $this->urlGenerator->generate(
            'public_carte_verification',
            ['numero' => $carte->getNumero()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $result = (new Builder(writer: new PngWriter()))->build(data: $url, size: 200, margin: 5);

        return $result->getString();
    }

    private function imageDataUri(string $filename): ?string
    {
        $path = $this->projectDir.'/public/images/'.$filename;

        return is_readable($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    }
}
