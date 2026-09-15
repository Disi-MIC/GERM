<?php

namespace App\Tests\Service;

use App\Service\FileEncryptionService;
use PHPUnit\Framework\TestCase;

class FileEncryptionServiceTest extends TestCase
{
    /** Le constructeur reçoit la clé déjà décodée — voir env(base64:FILE_ENCRYPTION_KEY) côté conteneur réel. */
    private function service(): FileEncryptionService
    {
        return new FileEncryptionService(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public function testChiffrerPuisDechiffrerRetrouveLeContenuOriginal(): void
    {
        $service = $this->service();
        $original = random_bytes(1024);

        $chiffre = $service->chiffrer($original);

        $this->assertNotSame($original, $chiffre);
        $this->assertSame($original, $service->dechiffrer($chiffre));
    }

    public function testDechiffrerRenvoieInchangeUnContenuSansEnTeteGrm1(): void
    {
        $service = $this->service();

        $legacyPlainContent = "\x89PNG\x0d\x0a\x1a\x0afausse-image-non-chiffree";

        $this->assertSame($legacyPlainContent, $service->dechiffrer($legacyPlainContent));
    }

    public function testDechiffrerRejetteUnContenuChiffreCorrompu(): void
    {
        $service = $this->service();
        $chiffre = $service->chiffrer('contenu original');

        $corrompu = substr($chiffre, 0, -1).('a' === substr($chiffre, -1, 1) ? 'b' : 'a');

        $this->expectException(\RuntimeException::class);
        $service->dechiffrer($corrompu);
    }

    public function testDechiffrerRejetteUnContenuChiffreAvecUneAutreCle(): void
    {
        $chiffre = $this->service()->chiffrer('contenu original');

        $this->expectException(\RuntimeException::class);
        $this->service()->dechiffrer($chiffre);
    }

    public function testChiffrerProduitUnResultatDifferentAChaqueAppel(): void
    {
        $service = $this->service();
        $original = 'même contenu';

        $this->assertNotSame($service->chiffrer($original), $service->chiffrer($original));
    }
}
