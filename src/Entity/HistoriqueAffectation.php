<?php

namespace App\Entity;

use App\Entity\Enum\TypeMouvementCarriere;
use App\Repository\HistoriqueAffectationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Événement de carrière d'un agent (nomination, mutation, promotion...), tel que
 * consigné par une décision administrative. Journal append-only : une ligne par
 * mouvement, jamais modifiée après coup.
 */
#[ORM\Entity(repositoryClass: HistoriqueAffectationRepository::class)]
#[ORM\Table(name: 'historique_affectation')]
class HistoriqueAffectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'historiqueAffectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'historiqueAffectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service est obligatoire.')]
    private ?Service $service = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $fonction = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(length: 20, enumType: TypeMouvementCarriere::class)]
    #[Assert\NotNull(message: 'Le type de mouvement est obligatoire.')]
    private ?TypeMouvementCarriere $typeMouvement = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'effet est obligatoire.")]
    private ?\DateTimeImmutable $dateEffet = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroDecision = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateDecision = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column]
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

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getTypeMouvement(): ?TypeMouvementCarriere
    {
        return $this->typeMouvement;
    }

    public function setTypeMouvement(?TypeMouvementCarriere $typeMouvement): static
    {
        $this->typeMouvement = $typeMouvement;

        return $this;
    }

    public function getDateEffet(): ?\DateTimeImmutable
    {
        return $this->dateEffet;
    }

    public function setDateEffet(?\DateTimeImmutable $dateEffet): static
    {
        $this->dateEffet = $dateEffet;

        return $this;
    }

    public function getNumeroDecision(): ?string
    {
        return $this->numeroDecision;
    }

    public function setNumeroDecision(?string $numeroDecision): static
    {
        $this->numeroDecision = $numeroDecision;

        return $this;
    }

    public function getDateDecision(): ?\DateTimeImmutable
    {
        return $this->dateDecision;
    }

    public function setDateDecision(?\DateTimeImmutable $dateDecision): static
    {
        $this->dateDecision = $dateDecision;

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

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->typeMouvement?->label() ?? '', $this->dateEffet?->format('d/m/Y') ?? '');
    }
}
