<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\DirectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Direction du Ministère (regroupe plusieurs Services).
 *
 * Exposée en lecture seule côté API Platform (liste + détail) ; la création,
 * l'édition et la note de service justifiant un directeur passent par
 * src/Controller/Api/DirectionController.php — même choix que Personnel/
 * MaterielInformatique. Twig (Admin/DirectionController) reste accessible au
 * superadmin, en secours.
 */
#[ORM\Entity(repositoryClass: DirectionRepository::class)]
#[ORM\Table(name: 'direction')]
#[UniqueEntity(fields: ['code'], message: 'Ce code est déjà utilisé par une autre direction.')]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    security: "is_granted('ROLE_RH_RESPONSABLE')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[Assert\Callback(callback: 'validerNoteService')]
class Direction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?bool $actif = true;

    #[ORM\OneToMany(mappedBy: 'direction', targetEntity: Service::class)]
    private Collection $services;

    /** Directeur, désigné par le RH Responsable — voir ApercuOrganisationController. */
    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $directeur = null;

    // Justificatif de la nomination du directeur : un numéro/date sont
    // obligatoires dès qu'un directeur est désigné (voir validerNoteService()),
    // le fichier scanné reste facultatif — remplacé en entier à chaque
    // nouvelle nomination, pas d'historique des directeurs précédents (voir
    // ApercuOrganisationController pour ce choix, déjà fait pour $directeur
    // lui-même).
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $noteServiceNumero = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $noteServiceDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $noteServiceFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $noteServiceNomOriginal = null;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function validerNoteService(ExecutionContextInterface $context): void
    {
        if (null !== $this->directeur && (null === $this->noteServiceNumero || null === $this->noteServiceDate)) {
            $context->buildViolation('Le numéro et la date de la note de service sont obligatoires pour désigner un directeur.')
                ->atPath('noteServiceNumero')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function getDirecteur(): ?Personnel
    {
        return $this->directeur;
    }

    public function setDirecteur(?Personnel $directeur): static
    {
        $this->directeur = $directeur;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getDirecteurNom(): ?string
    {
        return $this->directeur?->getNomComplet();
    }

    public function getNoteServiceNumero(): ?string
    {
        return $this->noteServiceNumero;
    }

    public function setNoteServiceNumero(?string $noteServiceNumero): static
    {
        $this->noteServiceNumero = $noteServiceNumero;

        return $this;
    }

    public function getNoteServiceDate(): ?\DateTimeImmutable
    {
        return $this->noteServiceDate;
    }

    public function setNoteServiceDate(?\DateTimeImmutable $noteServiceDate): static
    {
        $this->noteServiceDate = $noteServiceDate;

        return $this;
    }

    public function getNoteServiceFichier(): ?string
    {
        return $this->noteServiceFichier;
    }

    public function setNoteServiceFichier(?string $noteServiceFichier): static
    {
        $this->noteServiceFichier = $noteServiceFichier;

        return $this;
    }

    public function getNoteServiceNomOriginal(): ?string
    {
        return $this->noteServiceNomOriginal;
    }

    public function setNoteServiceNomOriginal(?string $noteServiceNomOriginal): static
    {
        $this->noteServiceNomOriginal = $noteServiceNomOriginal;

        return $this;
    }

    #[Groups(['api:read'])]
    public function isHasNoteServiceFichier(): bool
    {
        return null !== $this->noteServiceFichier;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
