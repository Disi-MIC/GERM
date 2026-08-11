<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Enum\PrioriteTicket;
use App\Entity\Enum\StatutTicket;
use App\Repository\TicketIncidentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ticket d'incident sur un matériel du parc informatique. Workflow à deux
 * niveaux (voir StatutTicket) : ROLE_IT_TICKETS ne peut que prendre en
 * charge, résoudre ou refuser ; seul le responsable informatique valide
 * (clôture) ou rouvre un ticket résolu — même séparation des tâches que
 * DemandeCartePro (RH Carte Pro/RH Admin). L'action de traiter reste
 * réservée à ROLE_IT_TICKETS (voir TicketIncidentController) mais la lecture
 * est aussi ouverte à ROLE_IT_STOCK : le formulaire de maintenance liste les
 * tickets pour renseigner `ticketOrigine`, domaine Stock.
 *
 * Création en self-service par l'agent déclarant (App\Controller\Api\MeDemandesController,
 * matériel devant lui être affecté) OU par le RH/IT pour son compte (Post natif
 * ici, avec IRI personnel/matériel — même parité que DemandeCartePro). Le
 * traitement (prendre en charge/résoudre/refuser/valider/rouvrir) passe par
 * App\Controller\Api\TicketIncidentController.
 */
#[ORM\Entity(repositoryClass: TicketIncidentRepository::class)]
#[ORM\Table(name: 'ticket_incident')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/tickets-incident', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/tickets-incident/{id}'),
        new Post(uriTemplate: '/tickets-incident'),
    ],
    security: "is_granted('ROLE_IT_TICKETS') or is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
    denormalizationContext: ['groups' => ['api:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['personnel' => 'exact', 'materiel' => 'exact'])]
#[ApiFilter(BackedEnumFilter::class, properties: ['statut'])]
class TicketIncident
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(targetEntity: MaterielInformatique::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le matériel concerné est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?MaterielInformatique $materiel = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "L'objet du ticket est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: PrioriteTicket::class)]
    #[Assert\NotNull(message: 'La priorité est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?PrioriteTicket $priorite = PrioriteTicket::NORMALE;

    #[ORM\Column(length: 20, enumType: StatutTicket::class)]
    #[Groups(['api:read'])]
    private StatutTicket $statut = StatutTicket::OUVERT;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?Personnel $assigneA = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $commentaireResolution = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $commentaireValidation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $datePriseEnCharge = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateResolution = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateCloture = null;

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

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getMateriel(): ?MaterielInformatique
    {
        return $this->materiel;
    }

    public function setMateriel(?MaterielInformatique $materiel): static
    {
        $this->materiel = $materiel;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

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

    public function getPriorite(): ?PrioriteTicket
    {
        return $this->priorite;
    }

    public function setPriorite(?PrioriteTicket $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): StatutTicket
    {
        return $this->statut;
    }

    public function setStatut(StatutTicket $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAssigneA(): ?Personnel
    {
        return $this->assigneA;
    }

    public function setAssigneA(?Personnel $assigneA): static
    {
        $this->assigneA = $assigneA;

        return $this;
    }

    public function getCommentaireResolution(): ?string
    {
        return $this->commentaireResolution;
    }

    public function setCommentaireResolution(?string $commentaireResolution): static
    {
        $this->commentaireResolution = $commentaireResolution;

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaireValidation;
    }

    public function setCommentaireValidation(?string $commentaireValidation): static
    {
        $this->commentaireValidation = $commentaireValidation;

        return $this;
    }

    public function getDatePriseEnCharge(): ?\DateTimeImmutable
    {
        return $this->datePriseEnCharge;
    }

    public function setDatePriseEnCharge(?\DateTimeImmutable $datePriseEnCharge): static
    {
        $this->datePriseEnCharge = $datePriseEnCharge;

        return $this;
    }

    public function getDateResolution(): ?\DateTimeImmutable
    {
        return $this->dateResolution;
    }

    public function setDateResolution(?\DateTimeImmutable $dateResolution): static
    {
        $this->dateResolution = $dateResolution;

        return $this;
    }

    public function getDateCloture(): ?\DateTimeImmutable
    {
        return $this->dateCloture;
    }

    public function setDateCloture(?\DateTimeImmutable $dateCloture): static
    {
        $this->dateCloture = $dateCloture;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['api:read'])]
    public function isOuvert(): bool
    {
        return StatutTicket::OUVERT === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isEnCours(): bool
    {
        return StatutTicket::EN_COURS === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isResolu(): bool
    {
        return StatutTicket::RESOLU === $this->statut;
    }

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->titre ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
