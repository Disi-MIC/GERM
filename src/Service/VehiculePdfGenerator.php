<?php

namespace App\Service;

use App\Entity\Vehicule;
use App\Repository\BonEssenceRepository;
use App\Repository\HistoriqueVidangeRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Génère la carte du véhicule : un document A4 récapitulant l'identité, les
 * échéances (assurance, visite technique, vidange) et les derniers
 * mouvements (vidanges, bons d'essence) — pas le même format que
 * CarteProfessionnellePdfGenerator (ID-1/CR80 recto-verso, pensé pour une
 * impression sur carte plastique badge) : un véhicule n'a pas vocation à
 * porter un badge sur lui, une page A4 classique suffit et reste bien plus
 * simple à produire (pas de FPDI, pas de recadrage).
 */
class VehiculePdfGenerator
{
    private const NB_DERNIERES_ENTREES = 8;

    public function __construct(
        private readonly Environment $twig,
        private readonly HistoriqueVidangeRepository $historiqueVidangeRepository,
        private readonly BonEssenceRepository $bonEssenceRepository,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function generate(Vehicule $vehicule): string
    {
        $vidanges = $this->historiqueVidangeRepository->findBy(['vehicule' => $vehicule], ['date' => 'DESC'], self::NB_DERNIERES_ENTREES);
        $bonsEssence = $this->bonEssenceRepository->findBy(['vehicule' => $vehicule], ['date' => 'DESC'], self::NB_DERNIERES_ENTREES);

        $html = $this->twig->render('pdf/carte_vehicule.html.twig', [
            'vehicule' => $vehicule,
            'vidanges' => $vidanges,
            'bonsEssence' => $bonsEssence,
            'logoDataUri' => $this->imageDataUri('logo-mincom.png'),
            'genereLe' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    private function imageDataUri(string $filename): ?string
    {
        $path = $this->projectDir.'/public/images/'.$filename;

        return is_readable($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    }
}
