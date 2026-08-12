<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\NiveauTicket;
use App\Repository\TicketEscaladeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Journal append-only des escalades d'un ticket (qui a escaladé, de quel
 * niveau à quel niveau, pourquoi) — même logique que
 * HistoriqueAffectationMateriel : jamais modifié après coup, alimenté
 * automatiquement par TicketIncidentController::escalader() (pas de création
 * directe exposée). Distinct des commentaires de résolution/validation
 * (uniques, écrasés à chaque étape) : une escalade par ligne, pour garder
 * l'historique complet si un ticket remonte plusieurs paliers.
 */
#[ORM\Entity(repositoryClass: TicketEscaladeRepository::class)]
#[ORM\Table(name: 'ticket_escalade')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/tickets-escalade', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/tickets-escalade/{id}'),
    ],
    security: "is_granted('ROLE_IT_TICKETS')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['ticket' => 'exact'])]
class TicketEscalade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TicketIncident::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['api:read'])]
    private ?TicketIncident $ticket = null;

    #[ORM\Column(length: 20, enumType: NiveauTicket::class)]
    #[Groups(['api:read'])]
    private ?NiveauTicket $deNiveau = null;

    #[ORM\Column(length: 20, enumType: NiveauTicket::class)]
    #[Groups(['api:read'])]
    private ?NiveauTicket $versNiveau = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?Personnel $par = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['api:read'])]
    private ?string $commentaire = null;

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

    public function getTicket(): ?TicketIncident
    {
        return $this->ticket;
    }

    public function setTicket(?TicketIncident $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getDeNiveau(): ?NiveauTicket
    {
        return $this->deNiveau;
    }

    public function setDeNiveau(?NiveauTicket $deNiveau): static
    {
        $this->deNiveau = $deNiveau;

        return $this;
    }

    public function getVersNiveau(): ?NiveauTicket
    {
        return $this->versNiveau;
    }

    public function setVersNiveau(?NiveauTicket $versNiveau): static
    {
        $this->versNiveau = $versNiveau;

        return $this;
    }

    public function getPar(): ?Personnel
    {
        return $this->par;
    }

    public function setPar(?Personnel $par): static
    {
        $this->par = $par;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
