<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HistoriqueVidangeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Vidange réalisée sur un véhicule du parc automobile — simple journal, pas
 * de workflow (même principe que Maintenance pour le parc informatique).
 * Exposée en lecture seule côté API : la création et la suppression passent
 * par App\Controller\Api\HistoriqueVidangeController, qui maintient au
 * passage les champs dénormalisés Vehicule::$derniereVidangeKm/Date (voir ce
 * contrôleur) — évite de recalculer le dernier relevé à chaque lecture de la
 * fiche véhicule.
 */
#[ORM\Entity(repositoryClass: HistoriqueVidangeRepository::class)]
#[ORM\Table(name: 'historique_vidange')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/historique-vidanges', order: ['date' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/historique-vidanges/{id}'),
    ],
    security: "is_granted('ROLE_SUPERADMIN')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['vehicule' => 'exact'])]
class HistoriqueVidange
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

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le kilométrage est obligatoire.')]
    #[Assert\PositiveOrZero]
    #[Groups(['api:read', 'api:write'])]
    private ?int $kilometrage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $cout = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $prestataire = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(?int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;

        return $this;
    }

    public function getCout(): ?string
    {
        return $this->cout;
    }

    public function setCout(?string $cout): static
    {
        $this->cout = $cout;

        return $this;
    }

    public function getPrestataire(): ?string
    {
        return $this->prestataire;
    }

    public function setPrestataire(?string $prestataire): static
    {
        $this->prestataire = $prestataire;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
