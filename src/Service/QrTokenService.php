<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Chiffre l'identifiant d'un matériel informatique pour l'étiquette QR
 * (voir MaterielInformatiqueController::qrcode()) : jamais l'id en clair ni
 * une URL directement exploitable dans le QR lui-même, pour qu'un scan hors
 * de l'app GERM (appareil photo classique, autre lecteur QR) ne révèle
 * qu'un jeton illisible plutôt que d'exposer la structure de
 * l'application ou l'identifiant du matériel. Seul le scanner intégré à
 * l'app (voir le composant Angular dédié) sait résoudre ce jeton, via
 * MaterielInformatiqueController::resoudreQrcode() — qui reste de toute
 * façon soumis aux mêmes contrôles de rôle que le reste du contrôleur.
 *
 * libsodium (secretbox, XSalsa20-Poly1305) plutôt qu'un simple hash ou une
 * signature : chiffrement authentifié — un jeton altéré ou généré sans la
 * clé secrète échoue au déchiffrement, pas seulement à une vérification de
 * signature séparée.
 */
class QrTokenService
{
    public function __construct(
        #[Autowire('%env(base64:QR_SECRET_KEY)%')] private readonly string $secretKey,
    ) {
    }

    public function encoder(int $materielId): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox((string) $materielId, $nonce, $this->secretKey);

        return $this->base64UrlEncode($nonce.$ciphertext);
    }

    /** @return ?int L'identifiant du matériel, ou null si le jeton est invalide, altéré ou expiré. */
    public function decoder(string $token): ?int
    {
        $decoded = $this->base64UrlDecode($token);
        if (false === $decoded || \strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->secretKey);

        if (false === $plain || !ctype_digit($plain)) {
            return null;
        }

        return (int) $plain;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
