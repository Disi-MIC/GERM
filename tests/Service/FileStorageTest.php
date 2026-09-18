<?php

namespace App\Tests\Service;

use App\Service\FileEncryptionService;
use App\Service\FileStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Verrouille erreurValidation() — en particulier le paramètre
 * `$tailleMaxOctets` ajouté pour MaintenanceController (pièces jusqu'à
 * 30 Mo), qui ne doit pas affecter la limite par défaut des autres appelants
 * (photos, documents RH, cartes pro).
 */
class FileStorageTest extends TestCase
{
    /** @var list<string> fichiers temporaires à nettoyer après chaque test */
    private array $fichiersTemporaires = [];

    protected function tearDown(): void
    {
        foreach ($this->fichiersTemporaires as $chemin) {
            @unlink($chemin);
        }
        $this->fichiersTemporaires = [];
    }

    private function fileStorage(): FileStorage
    {
        return new FileStorage(
            $this->createStub(FileEncryptionService::class),
            true,
            sys_get_temp_dir(),
            '',
            0,
            '',
            '',
            '',
            '',
            '',
        );
    }

    /** Image PNG réelle et valide (sniffée par fileinfo), tronquée/étendue à $octets sans en corrupter l'en-tête. */
    private function image(int $octets): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'filestorage_test').'.png';
        $image = imagecreatetruecolor(2, 2);
        imagepng($image, $chemin);

        $handle = fopen($chemin, 'r+');
        ftruncate($handle, $octets);
        fclose($handle);

        $this->fichiersTemporaires[] = $chemin;

        return new UploadedFile($chemin, 'piece.png', 'image/png', null, true);
    }

    public function testFichierDansLaLimiteParDefautEstAccepte(): void
    {
        $fichier = $this->image(1024);

        self::assertNull($this->fileStorage()->erreurValidation($fichier));
    }

    public function testFichierAuDelaDeLaLimiteParDefautEstRejete(): void
    {
        $fichier = $this->image(FileStorage::TAILLE_MAX_OCTETS + 1);

        $erreur = $this->fileStorage()->erreurValidation($fichier);

        self::assertNotNull($erreur);
        self::assertStringContainsString('10 Mo', $erreur);
    }

    /**
     * Cas MaintenanceController : un fichier plus gros que la limite par
     * défaut (10 Mo) mais dans une limite personnalisée plus large (30 Mo)
     * doit être accepté.
     */
    public function testLimitePersonnaliseeAccepteUnFichierPlusGrandQueLaLimiteParDefaut(): void
    {
        $fichier = $this->image(25 * 1024 * 1024);

        self::assertNull($this->fileStorage()->erreurValidation($fichier, 30 * 1024 * 1024));
    }

    public function testLimitePersonnaliseeRejetteAuDelaDeSaPropreLimiteEtLeDitDansLeMessage(): void
    {
        $fichier = $this->image(30 * 1024 * 1024 + 1);

        $erreur = $this->fileStorage()->erreurValidation($fichier, 30 * 1024 * 1024);

        self::assertNotNull($erreur);
        self::assertStringContainsString('30 Mo', $erreur);
    }

    public function testTypeMimeNonAutoriseEstRejete(): void
    {
        $chemin = tempnam(sys_get_temp_dir(), 'filestorage_test').'.txt';
        file_put_contents($chemin, 'contenu texte brut, pas une image ni un PDF');
        $this->fichiersTemporaires[] = $chemin;
        $fichier = new UploadedFile($chemin, 'notes.txt', 'text/plain', null, true);

        $erreur = $this->fileStorage()->erreurValidation($fichier, 30 * 1024 * 1024);

        self::assertNotNull($erreur);
        self::assertStringContainsString('non autorisé', $erreur);
    }

    public function testAucunFichierEstRejete(): void
    {
        self::assertSame('Aucun fichier reçu.', $this->fileStorage()->erreurValidation(null));
    }
}
