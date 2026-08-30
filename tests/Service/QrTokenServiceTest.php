<?php

namespace App\Tests\Service;

use App\Service\QrTokenService;
use PHPUnit\Framework\TestCase;

class QrTokenServiceTest extends TestCase
{
    /** Le constructeur reçoit la clé déjà décodée — voir env(base64:QR_SECRET_KEY) côté conteneur réel. */
    private function service(): QrTokenService
    {
        return new QrTokenService(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public function testEncoderPuisDecoderRetrouveLIdentifiant(): void
    {
        $service = $this->service();

        $token = $service->encoder(42);

        $this->assertNotSame('42', $token);
        $this->assertSame(42, $service->decoder($token));
    }

    public function testDecoderRejetteUnJetonAltere(): void
    {
        $service = $this->service();
        $token = $service->encoder(42);

        $altere = substr($token, 0, -1).('a' === substr($token, -1, 1) ? 'b' : 'a');

        $this->assertNull($service->decoder($altere));
    }

    public function testDecoderRejetteUnJetonForgeAvecUneAutreCle(): void
    {
        $token = $this->service()->encoder(42);

        $this->assertNull($this->service()->decoder($token));
    }

    public function testDecoderRejetteUneChaineQuelconque(): void
    {
        $this->assertNull($this->service()->decoder('pas-un-jeton-valide'));
    }
}
