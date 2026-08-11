<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Compte de connexion d'un agent (accès à l'application GERM).
 * Distinct de la fiche "Personnel" (RH), mais lié à elle : la règle métier
 * (imposée par src/Controller/Admin/UserController.php, seul point d'écriture
 * — voir Personnel::$user pour le côté propriétaire de la relation) impose
 * qu'un compte soit toujours rattaché à une fiche personnel, à l'exception
 * du compte super administrateur (ROLE_SUPERADMIN), qui reste un compte
 * technique optionnellement rattachable.
 *
 * Exposition API strictement minimale (id/email/actif/nomComplet, jamais
 * roles/password) — uniquement pour peupler le sélecteur "délégataire" du
 * module Délégation. Lecture seule, aucune écriture native ou custom.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_EMAIL', columns: ['email'])]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/users', order: ['nom' => 'ASC']),
        new Get(uriTemplate: '/users/{id}'),
    ],
    security: "is_granted('ROLE_ADMIN_RH')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_AGENT = 'ROLE_AGENT';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    public const ROLE_ADMIN_RH = 'ROLE_ADMIN_RH';
    public const ROLE_RH_PERSONNEL = 'ROLE_RH_PERSONNEL';
    public const ROLE_RH_CONGE = 'ROLE_RH_CONGE';
    public const ROLE_RH_CARTE_PRO = 'ROLE_RH_CARTE_PRO';
    public const ROLE_IT_STOCK = 'ROLE_IT_STOCK';
    public const ROLE_IT_TICKETS = 'ROLE_IT_TICKETS';
    public const ROLE_IT_RESPONSABLE = 'ROLE_IT_RESPONSABLE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['api:read'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé.
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $prenom = null;

    #[ORM\Column(nullable: true)]
    private ?bool $actif = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Personnel::class)]
    private ?Personnel $personnel = null;

    #[ORM\OneToMany(mappedBy: 'delegataire', targetEntity: Delegation::class)]
    private Collection $delegationsRecues;

    #[ORM\OneToMany(mappedBy: 'delegant', targetEntity: Delegation::class)]
    private Collection $delegationsAccordees;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->delegationsRecues = new ArrayCollection();
        $this->delegationsAccordees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        foreach ($this->delegationsRecues as $delegation) {
            if ($delegation->estActive()) {
                $roles[] = $delegation->getRoleDelegue()->value;
            }
        }

        // Chaque agent a au minimum le rôle ROLE_AGENT
        $roles[] = self::ROLE_AGENT;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si on stocke des données sensibles temporaires sur l'utilisateur, les effacer ici
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getNomComplet(): string
    {
        return trim(sprintf('%s %s', $this->prenom, $this->nom));
    }

    #[Groups(['api:read'])]
    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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

    public function isSuperAdmin(): bool
    {
        return in_array(self::ROLE_SUPERADMIN, $this->getRoles(), true);
    }

    /**
     * @return Collection<int, Delegation>
     */
    public function getDelegationsRecues(): Collection
    {
        return $this->delegationsRecues;
    }

    /**
     * @return Collection<int, Delegation>
     */
    public function getDelegationsAccordees(): Collection
    {
        return $this->delegationsAccordees;
    }
}
