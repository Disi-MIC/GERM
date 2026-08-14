<?php

namespace App\Entity;

use App\Import\TypeImport;
use App\Repository\LienGoogleSheetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lien persistant entre une rubrique importable (voir TypeImport) et un
 * classeur Google Sheets : une fois enregistré, un clic sur "Synchroniser"
 * relance l'import depuis ce même classeur sans avoir à recoller l'URL —
 * le fichier existant reste la seule autre façon d'importer, celle-ci
 * s'ajoute en complément (voir ImportController).
 */
#[ORM\Entity(repositoryClass: LienGoogleSheetRepository::class)]
#[ORM\Table(name: 'lien_google_sheet')]
class LienGoogleSheet
{
    #[ORM\Id]
    #[ORM\Column(length: 20, enumType: TypeImport::class)]
    private TypeImport $type;

    #[ORM\Column(length: 500)]
    private string $url;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereSynchronisationAt = null;

    public function __construct(TypeImport $type, string $url)
    {
        $this->type = $type;
        $this->url = $url;
    }

    public function getType(): TypeImport
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDerniereSynchronisationAt(): ?\DateTimeImmutable
    {
        return $this->derniereSynchronisationAt;
    }

    public function marquerSynchronise(): static
    {
        $this->derniereSynchronisationAt = new \DateTimeImmutable();

        return $this;
    }
}
