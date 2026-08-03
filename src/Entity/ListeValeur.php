<?php

namespace App\Entity;

use App\Entity\Enum\CategorieListeValeur;
use App\Repository\ListeValeurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Valeur d'une liste paramétrable (type de matériel, état, type de contrat...),
 * gérable depuis l'admin sans modification du code.
 */
#[ORM\Entity(repositoryClass: ListeValeurRepository::class)]
#[ORM\Table(name: 'liste_valeur')]
#[ORM\UniqueConstraint(name: 'UNIQ_LISTE_VALEUR_CAT_CODE', columns: ['categorie', 'code'])]
class ListeValeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, enumType: CategorieListeValeur::class)]
    #[Assert\NotNull]
    private ?CategorieListeValeur $categorie = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    private ?bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?CategorieListeValeur
    {
        return $this->categorie;
    }

    public function setCategorie(CategorieListeValeur $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
