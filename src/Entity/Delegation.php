<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\RoleDelegable;
use App\Entity\Enum\StatutDelegation;
use App\Repository\DelegationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Délégation temporaire d'un rôle RH d'un agent (le délégant) vers un autre
 * (le délégataire), en cas d'absence. Le rôle délégué s'ajoute aux rôles du
 * délégataire pour la durée indiquée, sans retirer quoi que ce soit au
 * délégant ni à qui que ce soit d'autre.
 *
 * Exposée en lecture seule côté API : la création force `delegant` à
 * l'utilisateur connecté côté serveur (jamais depuis le payload), et la
 * révocation passe par une action dédiée — voir
 * src/Controller/Api/DelegationController.php.
 */
#[ORM\Entity(repositoryClass: DelegationRepository::class)]
#[ORM\Table(name: 'delegation')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/delegations', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/delegations/{id}'),
    ],
    security: "is_granted('ROLE_ADMIN_RH')",
    normalizationContext: ['groups' => ['api:read']],
)]
class Delegation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'delegationsAccordees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read'])]
    private ?User $delegant = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'delegationsRecues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?User $delegataire = null;

    #[ORM\Column(length: 30, enumType: RoleDelegable::class)]
    #[Assert\NotNull(message: 'Le rôle à déléguer est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?RoleDelegable $roleDelegue = null;

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

    #[ORM\Column(length: 20, enumType: StatutDelegation::class)]
    #[Groups(['api:read'])]
    private StatutDelegation $statut = StatutDelegation::ACTIVE;

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

    public function getDelegant(): ?User
    {
        return $this->delegant;
    }

    public function setDelegant(?User $delegant): static
    {
        $this->delegant = $delegant;

        return $this;
    }

    public function getDelegataire(): ?User
    {
        return $this->delegataire;
    }

    public function setDelegataire(?User $delegataire): static
    {
        $this->delegataire = $delegataire;

        return $this;
    }

    public function getRoleDelegue(): ?RoleDelegable
    {
        return $this->roleDelegue;
    }

    public function setRoleDelegue(?RoleDelegable $roleDelegue): static
    {
        $this->roleDelegue = $roleDelegue;

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

    public function getStatut(): StatutDelegation
    {
        return $this->statut;
    }

    public function setStatut(StatutDelegation $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function estActive(): bool
    {
        $today = new \DateTimeImmutable('today');

        return StatutDelegation::ACTIVE === $this->statut
            && null !== $this->dateDebut && $this->dateDebut <= $today
            && null !== $this->dateFin && $this->dateFin >= $today;
    }

    /**
     * Alias exposé côté API : le Serializer de Symfony n'accepte que les
     * méthodes préfixées get/is/has/can/set comme source de propriété
     * sérialisée, `estActive()` ne convient pas telle quelle.
     */
    #[Groups(['api:read'])]
    public function isActive(): bool
    {
        return $this->estActive();
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    #[Groups(['api:read'])]
    public function getStatutAffiche(): array
    {
        if (StatutDelegation::REVOQUEE === $this->statut) {
            return ['label' => 'Révoquée', 'badgeClass' => 'secondary'];
        }

        $today = new \DateTimeImmutable('today');

        if (null !== $this->dateDebut && $this->dateDebut > $today) {
            return ['label' => 'À venir', 'badgeClass' => 'info'];
        }

        if (null !== $this->dateFin && $this->dateFin < $today) {
            return ['label' => 'Expirée', 'badgeClass' => 'secondary'];
        }

        return ['label' => 'Active', 'badgeClass' => 'success'];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s → %s (%s)',
            $this->delegant?->getNomComplet() ?? '',
            $this->delegataire?->getNomComplet() ?? '',
            $this->roleDelegue?->label() ?? ''
        );
    }
}
