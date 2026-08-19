<?php

namespace App\Entity;

use App\Repository\DirectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Direction du Ministère (regroupe plusieurs Services).
 */
#[ORM\Entity(repositoryClass: DirectionRepository::class)]
#[ORM\Table(name: 'direction')]
#[UniqueEntity(fields: ['code'], message: 'Ce code est déjà utilisé par une autre direction.')]
class Direction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $actif = true;

    #[ORM\OneToMany(mappedBy: 'direction', targetEntity: Service::class)]
    private Collection $services;

    /** Directeur, désigné par le RH Admin — voir ApercuOrganisationController. */
    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    private ?Personnel $directeur = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function getDirecteur(): ?Personnel
    {
        return $this->directeur;
    }

    public function setDirecteur(?Personnel $directeur): static
    {
        $this->directeur = $directeur;

        return $this;
    }

    public function getDirecteurNom(): ?string
    {
        return $this->directeur?->getNomComplet();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
