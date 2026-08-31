<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\BonEssenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bon d'essence délivré pour un véhicule du parc automobile — simple
 * journal, pas de workflow (même principe que HistoriqueVidange/
 * Maintenance). Exposée en lecture seule côté API : la création et la
 * suppression passent par App\Controller\Api\BonEssenceController.
 */
#[ORM\Entity(repositoryClass: BonEssenceRepository::class)]
#[ORM\Table(name: 'bon_essence')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/bons-essence', order: ['date' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/bons-essence/{id}'),
    ],
    security: "is_granted('ROLE_SUPERADMIN')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['vehicule' => 'exact'])]
class BonEssence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le véhicule est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?Vehicule $vehicule = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numero = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $quantiteLitres = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $montant = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?int $kilometrageReleve = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $chauffeur = null;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getQuantiteLitres(): ?string
    {
        return $this->quantiteLitres;
    }

    public function setQuantiteLitres(?string $quantiteLitres): static
    {
        $this->quantiteLitres = $quantiteLitres;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getKilometrageReleve(): ?int
    {
        return $this->kilometrageReleve;
    }

    public function setKilometrageReleve(?int $kilometrageReleve): static
    {
        $this->kilometrageReleve = $kilometrageReleve;

        return $this;
    }

    public function getChauffeur(): ?Personnel
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Personnel $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
