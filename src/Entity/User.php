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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
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
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée par un autre compte.')]
// Pas d'order:['nom' => 'ASC'] ici : ce champ n'est renseigné que pour les
// comptes sans fiche agent liée (voir getNom()), un simple ORDER BY sur la
// colonne serait donc trié n'importe comment pour la plupart des comptes.
// Le seul consommateur (sélecteur délégataire, DelegationFormComponent)
// trie côté client sur nomComplet, qui lui reste toujours correct.
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/users'),
        new Get(uriTemplate: '/users/{id}'),
    ],
    security: "is_granted('ROLE_RH_RESPONSABLE')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public const ROLE_AGENT = 'ROLE_AGENT';
    public const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    public const ROLE_RH_RESPONSABLE = 'ROLE_RH_RESPONSABLE';
    public const ROLE_RH_PERSONNEL = 'ROLE_RH_PERSONNEL';
    public const ROLE_RH_CONGE = 'ROLE_RH_CONGE';
    public const ROLE_RH_CARTE_PRO = 'ROLE_RH_CARTE_PRO';
    public const ROLE_IT_STOCK = 'ROLE_IT_STOCK';
    public const ROLE_IT_TICKETS = 'ROLE_IT_TICKETS';
    public const ROLE_IT_RESPONSABLE = 'ROLE_IT_RESPONSABLE';
    /** Chefs de service — voir aussi Service::$responsable, qui pilote l'accès à "Aperçu de mon service" indépendamment de ce rôle. */
    public const ROLE_RESPONSABLE_SERVICE = 'ROLE_RESPONSABLE_SERVICE';
    /** Directeurs — voir aussi Direction::$directeur, qui pilote l'accès à "Aperçu de ma direction" indépendamment de ce rôle. */
    public const ROLE_DIRECTION_MINISTERIELLE = 'ROLE_DIRECTION_MINISTERIELLE';
    /** SG, DC, Ministre — hiérarchie au-dessus des directeurs ; aperçu global du Ministère (agents, services, directions, matériel), voir ApercuOrganisationController. */
    public const ROLE_AUTORITE = 'ROLE_AUTORITE';

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

    // Nullable : sert uniquement de repli pour les comptes sans fiche agent
    // liée (le compte super administrateur — voir Personnel::$user pour la
    // règle métier). Un compte lié à une fiche agent n'a plus sa propre
    // copie : getNom()/getPrenom() délèguent alors à $personnel, seule
    // source de vérité, pour ne jamais laisser les deux diverger (ex. après
    // un changement de nom). Le caractère obligatoire "sauf compte lié" est
    // appliqué par UserController::lierPersonnelOuRefuser(), pas ici.
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
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
        // Défense en profondeur : un compte désactivé ne porte plus aucun
        // rôle, donc plus aucun accès, même si un pare-feu venait à être
        // déclaré sans user_checker (voir config/packages/security.yaml).
        // Le UserChecker reste la barrière principale, celle qui produit un
        // message d'erreur explicite à la connexion.
        if (!$this->actif) {
            return [];
        }

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

    /**
     * Invalide les sessions déjà ouvertes dès que le compte change d'état.
     *
     * Symfony compare à chaque requête l'utilisateur en session à celui
     * rechargé en base (ContextListener::hasUserChanged) : il détecte
     * nativement un changement de mot de passe ou de rôles, mais pas un
     * changement de `actif`. Sans cette comparaison, désactiver un compte ne
     * prenait effet qu'à l'expiration de sa session — l'agent révoqué gardait
     * l'accès jusque-là.
     *
     * ATTENTION en cas de modification : dès qu'EquatableInterface est
     * implémentée, Symfony s'appuie EXCLUSIVEMENT sur cette méthode et
     * abandonne ses propres contrôles. Les comparaisons du mot de passe, de
     * l'identifiant et des rôles doivent donc être reproduites ici, sous
     * peine de rouvrir ce que le comportement natif fermait déjà.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $user->isActif() === $this->actif
            && $user->getPassword() === $this->password
            && $user->getUserIdentifier() === $this->getUserIdentifier()
            && $user->getRoles() === $this->getRoles();
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

    public function getNom(): ?string
    {
        return $this->personnel?->getNom() ?? $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->personnel?->getPrenom() ?? $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getNomComplet(): string
    {
        return trim(sprintf('%s %s', $this->getPrenom(), $this->getNom()));
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
