<?php

namespace App\Entity;

use App\Repository\DecisionCongeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Décision de congé (annuel) : autorisation formelle, avec une période de validité,
 * sur laquelle une ou plusieurs demandes de jouissance peuvent s'appuyer tant qu'elle
 * n'a pas expiré. Créée automatiquement à l'approbation d'une DemandeDecision, ou
 * directement pour enregistrer une décision papier antérieure à l'application.
 */
#[ORM\Entity(repositoryClass: DecisionCongeRepository::class)]
#[ORM\Table(name: 'decision_conge')]
class DecisionConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'decisionsConge')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de décision est obligatoire.')]
    private ?string $numeroDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'octroi est obligatoire.")]
    private ?\DateTimeImmutable $dateDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: 'dateDecision', message: "La date d'expiration doit être postérieure à la date d'octroi.")]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\OneToMany(mappedBy: 'decision', targetEntity: DemandeJouissance::class)]
    private Collection $demandesJouissance;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->demandesJouissance = new ArrayCollection();
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

    public function getNumeroDecision(): ?string
    {
        return $this->numeroDecision;
    }

    public function setNumeroDecision(string $numeroDecision): static
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

    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeImmutable $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

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

    /**
     * @return Collection<int, DemandeJouissance>
     */
    public function getDemandesJouissance(): Collection
    {
        return $this->demandesJouissance;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isValide(): bool
    {
        return $this->dateExpiration >= new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->numeroDecision ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
