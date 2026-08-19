<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Service ou Direction du Ministère (ex: Direction des Systèmes d'Information,
 * Direction des Ressources Humaines, Direction de la Communication...).
 *
 * Exposé en lecture seule côté API Platform (liste + détail, pour peupler le
 * sélecteur "Service" des formulaires Personnel et Matériel informatique,
 * deux domaines distincts d'où les deux rôles en plus de ROLE_RH_RESPONSABLE) ;
 * la création, l'édition et la note de service justifiant un responsable
 * passent par src/Controller/Api/ServiceController.php — même choix que
 * Personnel/MaterielInformatique. Twig (Admin/ServiceController) reste
 * accessible au superadmin, en secours.
 */
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
#[UniqueEntity(fields: ['code'], message: 'Ce code est déjà utilisé par un autre service.')]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    security: "is_granted('ROLE_RH_PERSONNEL') or is_granted('ROLE_IT_STOCK') or is_granted('ROLE_RH_RESPONSABLE')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[Assert\Callback(callback: 'validerNoteService')]
class Service
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

    #[ORM\ManyToOne(targetEntity: Direction::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La direction de rattachement est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?Direction $direction = null;

    /**
     * Chef de service / coordonnateur, désigné par le RH Responsable — voir
     * ApercuOrganisationController. readableLink: false — voir
     * Direction::$directeur pour la raison (même cycle Personnel -> service
     * -> responsable -> Personnel).
     */
    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ApiProperty(readableLink: false)]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $responsable = null;

    // Justificatif de la nomination du responsable — voir Direction::$noteServiceNumero
    // pour le même choix (numéro/date obligatoires, fichier facultatif, pas
    // d'historique des responsables précédents).
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

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Personnel::class)]
    private Collection $personnels;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: MaterielInformatique::class)]
    private Collection $materiels;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: HistoriqueAffectation::class)]
    private Collection $historiqueAffectations;

    public function __construct()
    {
        $this->personnels = new ArrayCollection();
        $this->materiels = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
        $this->historiqueAffectations = new ArrayCollection();
    }

    public function validerNoteService(ExecutionContextInterface $context): void
    {
        if (null !== $this->responsable && (null === $this->noteServiceNumero || null === $this->noteServiceDate)) {
            $context->buildViolation('Le numéro et la date de la note de service sont obligatoires pour désigner un responsable.')
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

    public function getDirection(): ?Direction
    {
        return $this->direction;
    }

    public function setDirection(?Direction $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getResponsable(): ?Personnel
    {
        return $this->responsable;
    }

    public function setResponsable(?Personnel $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getResponsableNom(): ?string
    {
        return $this->responsable?->getNomComplet();
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

    /**
     * @return Collection<int, Personnel>
     */
    public function getPersonnels(): Collection
    {
        return $this->personnels;
    }

    /**
     * @return Collection<int, MaterielInformatique>
     */
    public function getMateriels(): Collection
    {
        return $this->materiels;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    /**
     * @return Collection<int, HistoriqueAffectation>
     */
    public function getHistoriqueAffectations(): Collection
    {
        return $this->historiqueAffectations;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
