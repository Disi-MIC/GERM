<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\TypeConge;
use App\Repository\CongeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Période de congé d'un agent.
 *
 * Exposée en lecture seule côté API (frontend Angular, rôle ROLE_RH_CONGE) :
 * les écritures passent par src/Controller/Api/CongeController.php.
 */
#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\Table(name: 'conge')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/conges', order: ['dateDebut' => 'DESC']),
        new Get(uriTemplate: '/conges/{id}'),
    ],
    security: "is_granted('ROLE_RH_CONGE')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['personnel' => 'exact'])]
class Conge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'conges')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, enumType: TypeConge::class)]
    #[Assert\NotNull(message: 'Le type de congé est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?TypeConge $type = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateDebut', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $motif = null;

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

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getType(): ?TypeConge
    {
        return $this->type;
    }

    public function setType(?TypeConge $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Durée en jours, bornes incluses.
     */
    #[Groups(['api:read'])]
    public function getDuree(): ?int
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return null;
        }

        return $this->dateDebut->diff($this->dateFin)->days + 1;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s - %s)',
            $this->type?->label() ?? '',
            $this->dateDebut?->format('d/m/Y') ?? '',
            $this->dateFin?->format('d/m/Y') ?? ''
        );
    }
}
