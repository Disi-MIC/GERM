<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\DecisionCongeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Décision de congé (annuel) : autorisation formelle, avec une période de validité,
 * sur laquelle une ou plusieurs demandes de jouissance peuvent s'appuyer tant qu'elle
 * n'a pas expiré. Créée automatiquement quand le RH Congé transmet une DemandeDecision
 * (DemandeDecisionController::transmettre(), $genereePar/$nombreJours renseignés,
 * $valideeParAdminRh=false), ou directement pour enregistrer une décision papier
 * antérieure à l'application (decision-conge-form, superadmin) — dans ce second cas,
 * $valideeParAdminRh reste à sa valeur par défaut (true) : sans circuit de transmission,
 * il n'y a pas de second palier à valider. isValide() reste volontairement basé sur la
 * seule date d'expiration : $valideeParAdminRh est une information d'audit sur l'origine
 * de la décision, pas un filtre d'utilisabilité (une DemandeJouissance ne doit pas se
 * retrouver bloquée par une validation RH Admin qui ne la concerne pas).
 *
 * Exposée en lecture seule côté API : les écritures passent par
 * src/Controller/Api/DecisionCongeController.php (la suppression y est
 * bloquée si des demandes de jouissance s'appuient encore sur la décision —
 * la collection `demandesJouissance` n'est donc volontairement pas exposée,
 * ce garde-fou reste serveur-only).
 */
#[ORM\Entity(repositoryClass: DecisionCongeRepository::class)]
#[ORM\Table(name: 'decision_conge')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/decisions-conge', order: ['dateExpiration' => 'DESC']),
        new Get(uriTemplate: '/decisions-conge/{id}'),
    ],
    security: "is_granted('ROLE_RH_CONGE')",
    normalizationContext: ['groups' => ['api:read']],
)]
class DecisionConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'decisionsConge')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de décision est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'octroi est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: 'dateDecision', message: "La date d'expiration doit être postérieure à la date d'octroi.")]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    /** Nombre de jours de congé accordés par cette décision — renseigné par le RH Congé au moment de la transmission (DemandeDecisionController::transmettre()), absent pour les décisions saisies directement. */
    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?int $nombreJours = null;

    /** L'opérateur RH Congé ayant généré la décision — voir DemandeDecisionController::transmettre(). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $genereePar = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['api:read'])]
    private bool $valideeParAdminRh = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $valideePar = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $valideeLe = null;

    #[ORM\OneToMany(mappedBy: 'decision', targetEntity: DemandeJouissance::class)]
    private Collection $demandesJouissance;

    #[ORM\Column]
    #[Groups(['api:read'])]
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

    public function getNombreJours(): ?int
    {
        return $this->nombreJours;
    }

    public function setNombreJours(?int $nombreJours): static
    {
        $this->nombreJours = $nombreJours;

        return $this;
    }

    public function getGenereePar(): ?User
    {
        return $this->genereePar;
    }

    public function setGenereePar(?User $genereePar): static
    {
        $this->genereePar = $genereePar;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getGenereeParNom(): ?string
    {
        return $this->genereePar?->getNomComplet();
    }

    public function isValideeParAdminRh(): bool
    {
        return $this->valideeParAdminRh;
    }

    /**
     * Volontairement distinct de valider() ci-dessous : n'appelable qu'à la
     * création par DemandeDecisionController::transmettre(), pour repasser
     * la décision en attente de validation RH Admin (défaut true — voir le
     * commentaire de classe) sans passer par un constructeur dédié.
     */
    public function marquerEnAttenteValidationAdminRh(): static
    {
        $this->valideeParAdminRh = false;

        return $this;
    }

    public function getValideePar(): ?User
    {
        return $this->valideePar;
    }

    #[Groups(['api:read'])]
    public function getValideeParNom(): ?string
    {
        return $this->valideePar?->getNomComplet();
    }

    public function getValideeLe(): ?\DateTimeImmutable
    {
        return $this->valideeLe;
    }

    /** Marque la décision (déjà créée par transmettre()) comme validée par le RH Admin — ne crée rien, contrairement à CarteProfessionnelle::valider(). */
    public function valider(User $validateur): void
    {
        $this->valideeParAdminRh = true;
        $this->valideePar = $validateur;
        $this->valideeLe = new \DateTimeImmutable();
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

    #[Groups(['api:read'])]
    public function isValide(): bool
    {
        return $this->dateExpiration >= new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->numeroDecision ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
