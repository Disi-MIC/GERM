<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Opération de maintenance (préventive ou corrective) sur un matériel du parc
 * informatique — simple journal, pas de workflow de validation (contrairement
 * à TicketIncident) : le gestionnaire de stock consigne directement ce qui a
 * été fait (rattaché au cycle de vie du matériel, pas au traitement des
 * tickets). `ticketOrigine` relie la maintenance au ticket qui l'a
 * déclenchée, quand elle fait suite à un incident plutôt qu'à un entretien
 * planifié.
 *
 * Exposée en lecture seule côté API : la création et la suppression passent
 * par App\Controller\Api\MaintenanceController.
 *
 * `type` est une classification (ListeValeur, catégorie type-maintenance)
 * paramétrable par le superadmin — pas de workflow attaché à ce champ,
 * contrairement à TicketIncident.statut.
 */
#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
#[ORM\Table(name: 'maintenance')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/maintenances', order: ['dateRealisation' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/maintenances/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['materiel' => 'exact'])]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterielInformatique::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le matériel concerné est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?MaterielInformatique $materiel = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de maintenance est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $type = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de réalisation est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateRealisation = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $realisePar = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $prestataireExterne = null;

    #[ORM\ManyToOne(targetEntity: TicketIncident::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?TicketIncident $ticketOrigine = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

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

    public function getMateriel(): ?MaterielInformatique
    {
        return $this->materiel;
    }

    public function setMateriel(?MaterielInformatique $materiel): static
    {
        $this->materiel = $materiel;

        return $this;
    }

    public function getType(): ?ListeValeur
    {
        return $this->type;
    }

    public function setType(?ListeValeur $type): static
    {
        $this->type = $type;

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

    public function getDateRealisation(): ?\DateTimeImmutable
    {
        return $this->dateRealisation;
    }

    public function setDateRealisation(?\DateTimeImmutable $dateRealisation): static
    {
        $this->dateRealisation = $dateRealisation;

        return $this;
    }

    public function getRealisePar(): ?Personnel
    {
        return $this->realisePar;
    }

    public function setRealisePar(?Personnel $realisePar): static
    {
        $this->realisePar = $realisePar;

        return $this;
    }

    public function getPrestataireExterne(): ?string
    {
        return $this->prestataireExterne;
    }

    public function setPrestataireExterne(?string $prestataireExterne): static
    {
        $this->prestataireExterne = $prestataireExterne;

        return $this;
    }

    public function getTicketOrigine(): ?TicketIncident
    {
        return $this->ticketOrigine;
    }

    public function setTicketOrigine(?TicketIncident $ticketOrigine): static
    {
        $this->ticketOrigine = $ticketOrigine;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
