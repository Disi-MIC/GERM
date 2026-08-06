<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Service ou Direction du Ministère (ex: Direction des Systèmes d'Information,
 * Direction des Ressources Humaines, Direction de la Communication...).
 *
 * Exposé en lecture seule côté API (pilote Angular) : uniquement pour
 * peupler le sélecteur "Service" du formulaire Personnel — la gestion
 * complète des services reste Twig-only pour l'instant.
 */
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    security: "is_granted('ROLE_RH_PERSONNEL')",
    normalizationContext: ['groups' => ['api:read']],
)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Groups(['api:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Groups(['api:read'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $actif = true;

    #[ORM\ManyToOne(targetEntity: Direction::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La direction de rattachement est obligatoire.')]
    private ?Direction $direction = null;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Personnel::class)]
    private Collection $personnels;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: MaterielInformatique::class)]
    private Collection $materiels;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: HistoriqueAffectation::class)]
    private Collection $historiqueAffectations;

    public function __construct()
    {
        $this->personnels = new ArrayCollection();
        $this->materiels = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
        $this->historiqueAffectations = new ArrayCollection();
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

    public function getDirection(): ?Direction
    {
        return $this->direction;
    }

    public function setDirection(?Direction $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * @return Collection<int, Personnel>
     */
    public function getPersonnels(): Collection
    {
        return $this->personnels;
    }

    /**
     * @return Collection<int, MaterielInformatique>
     */
    public function getMateriels(): Collection
    {
        return $this->materiels;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    /**
     * @return Collection<int, HistoriqueAffectation>
     */
    public function getHistoriqueAffectations(): Collection
    {
        return $this->historiqueAffectations;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
