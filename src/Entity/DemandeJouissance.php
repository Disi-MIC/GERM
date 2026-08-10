<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeConge;
use App\Repository\DemandeJouissanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
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
 *
 * Exposée en lecture seule côté API : les écritures (dont le traitement
 * approuver/refuser, qui crée le Conge) passent par
 * src/Controller/Api/DemandeJouissanceController.php.
 */
#[ORM\Entity(repositoryClass: DemandeJouissanceRepository::class)]
#[ORM\Table(name: 'demande_jouissance')]
#[Assert\Callback('validateDecisionRequisePourAnnuel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/demandes-jouissance', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/demandes-jouissance/{id}'),
    ],
    security: "is_granted('ROLE_RH_CONGE')",
    normalizationContext: ['groups' => ['api:read']],
)]
class DemandeJouissance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'demandesJouissance')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, enumType: TypeConge::class)]
    #[Assert\NotNull(message: 'Le type de congé est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?TypeConge $type = null;

    #[ORM\ManyToOne(targetEntity: DecisionConge::class, inversedBy: 'demandesJouissance')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?DecisionConge $decision = null;

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

    #[ORM\Column(length: 20, enumType: StatutDemande::class)]
    #[Groups(['api:read'])]
    private StatutDemande $statut = StatutDemande::EN_ATTENTE;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateTraitement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $commentaireTraitement = null;

    #[ORM\ManyToOne(targetEntity: Conge::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read'])]
    private ?Conge $conge = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: PieceJustificativeJouissance::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['api:read'])]
    private Collection $pieces;

    #[ORM\Column]
    #[Groups(['api:read'])]
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

    #[Groups(['api:read'])]
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
