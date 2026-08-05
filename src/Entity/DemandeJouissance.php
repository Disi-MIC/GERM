<?php

namespace App\Entity;

use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeConge;
use App\Repository\DemandeJouissanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Demande de jouissance de congé : l'agent souhaite effectivement prendre des jours.
 * Pour un congé annuel, s'appuie obligatoirement sur une DecisionConge encore valide
 * (une même décision peut être consommée par plusieurs jouissances successives) ;
 * pour les autres types (maladie, maternité/paternité, sans solde, autre), aucune
 * décision n'est nécessaire. En attendant un espace agent en libre-service, ces
 * demandes sont saisies et traitées par le superadmin. Approuver crée le Conge
 * effectif correspondant ; refuser la laisse comme trace, sans congé.
 */
#[ORM\Entity(repositoryClass: DemandeJouissanceRepository::class)]
#[ORM\Table(name: 'demande_jouissance')]
#[Assert\Callback('validateDecisionRequisePourAnnuel')]
class DemandeJouissance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'demandesJouissance')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, enumType: TypeConge::class)]
    #[Assert\NotNull(message: 'Le type de congé est obligatoire.')]
    private ?TypeConge $type = null;

    #[ORM\ManyToOne(targetEntity: DecisionConge::class, inversedBy: 'demandesJouissance')]
    #[ORM\JoinColumn(nullable: true)]
    private ?DecisionConge $decision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateDebut', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(length: 20, enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::EN_ATTENTE;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateTraitement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaireTraitement = null;

    #[ORM\ManyToOne(targetEntity: Conge::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Conge $conge = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: PieceJustificativeJouissance::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $pieces;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->pieces = new ArrayCollection();
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

    public function getDecision(): ?DecisionConge
    {
        return $this->decision;
    }

    public function setDecision(?DecisionConge $decision): static
    {
        $this->decision = $decision;

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

    public function getStatut(): StatutDemande
    {
        return $this->statut;
    }

    public function setStatut(StatutDemande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateTraitement(): ?\DateTimeImmutable
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTimeImmutable $dateTraitement): static
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    public function getCommentaireTraitement(): ?string
    {
        return $this->commentaireTraitement;
    }

    public function setCommentaireTraitement(?string $commentaireTraitement): static
    {
        $this->commentaireTraitement = $commentaireTraitement;

        return $this;
    }

    public function getConge(): ?Conge
    {
        return $this->conge;
    }

    public function setConge(?Conge $conge): static
    {
        $this->conge = $conge;

        return $this;
    }

    /**
     * @return Collection<int, PieceJustificativeJouissance>
     */
    public function getPieces(): Collection
    {
        return $this->pieces;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isEnAttente(): bool
    {
        return StatutDemande::EN_ATTENTE === $this->statut;
    }

    public function validateDecisionRequisePourAnnuel(ExecutionContextInterface $context): void
    {
        if (TypeConge::ANNUEL === $this->type && null === $this->decision) {
            $context->buildViolation('Une décision de congé valide est requise pour un congé annuel.')
                ->atPath('decision')
                ->addViolation();
        }
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
