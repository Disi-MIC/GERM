<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Chiffre le contenu de chaque fichier stocké par FileStorage (photos, pièces
 * justificatives, cartes professionnelles) avant écriture sur le SFTP, et le
 * déchiffre à la lecture (GERM-025). Le stockage lui-même n'offre aucune
 * garantie de confidentialité au repos : SFTP ne chiffre que le transport,
 * pas le fichier une fois posé sur le disque distant.
 *
 * libsodium (secretbox, XSalsa20-Poly1305), même primitive et même
 * disposition (nonce généré aléatoirement puis préfixé au chiffré) que
 * QrTokenService, avec une clé dédiée (FILE_ENCRYPTION_KEY) — jamais la même
 * que QR_SECRET_KEY : compromettre un usage ne doit pas compromettre l'autre.
 *
 * Format enveloppé et versionné plutôt qu'un chiffrement systématique
 * inconditionnel : les fichiers déjà stockés avant ce correctif sont en
 * clair et le resteront tant qu'ils ne sont pas remplacés — les rechiffrer
 * en masse suppose de parcourir tout le SFTP de production, une opération
 * distincte, plus risquée, qui n'a pas été demandée ici. dechiffrer()
 * reconnaît l'en-tête `self::MAGIC` et renvoie le contenu tel quel s'il est
 * absent, pour rester compatible avec ces fichiers historiques.
 */
class FileEncryptionService
{
    private const MAGIC = 'GRM1';

    public function __construct(
        #[Autowire('%env(base64:FILE_ENCRYPTION_KEY)%')] private readonly string $secretKey,
    ) {
    }

    public function chiffrer(string $contenu): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($contenu, $nonce, $this->secretKey);

        return self::MAGIC.$nonce.$ciphertext;
    }

    /** Renvoie $contenu inchangé si l'en-tête GRM1 est absent (fichier stocké avant GERM-025). */
    public function dechiffrer(string $contenu): string
    {
        $tailleEntete = \strlen(self::MAGIC) + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (\strlen($contenu) < $tailleEntete || self::MAGIC !== substr($contenu, 0, \strlen(self::MAGIC))) {
            return $contenu;
        }

        $nonce = substr($contenu, \strlen(self::MAGIC), SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($contenu, $tailleEntete);
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->secretKey);

        if (false === $plain) {
            throw new \RuntimeException('Fichier chiffré illisible : clé incorrecte ou contenu corrompu.');
        }

        return $plain;
    }
}
