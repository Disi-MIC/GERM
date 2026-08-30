<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\TypeMouvementCarriere;
use App\Repository\HistoriqueAffectationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Événement de carrière d'un agent (nomination, mutation, promotion...), tel que
 * consigné par une décision administrative. Journal append-only : une ligne par
 * mouvement, jamais modifiée après coup.
 *
 * Exposée en lecture seule côté API : la création (qui synchronise
 * Personnel.service/fonction/grade depuis le mouvement le plus récent) et la
 * suppression (bloquée pour le mouvement courant) passent par
 * src/Controller/Api/HistoriqueAffectationController.php. Pas de Put : aucune
 * route d'édition n'existe, même en Twig (journal append-only).
 */
#[ORM\Entity(repositoryClass: HistoriqueAffectationRepository::class)]
#[ORM\Table(name: 'historique_affectation')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/historique-affectations', order: ['dateEffet' => 'DESC']),
        new Get(uriTemplate: '/historique-affectations/{id}'),
    ],
    security: "is_granted('ROLE_RH_PERSONNEL')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['personnel' => 'exact'])]
class HistoriqueAffectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'historiqueAffectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'historiqueAffectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?Service $service = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $fonction = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $grade = null;

    #[ORM\Column(length: 20, enumType: TypeMouvementCarriere::class)]
    #[Assert\NotNull(message: 'Le type de mouvement est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?TypeMouvementCarriere $typeMouvement = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'effet est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateEffet = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroDecision = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDecision = null;

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

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getTypeMouvement(): ?TypeMouvementCarriere
    {
        return $this->typeMouvement;
    }

    public function setTypeMouvement(?TypeMouvementCarriere $typeMouvement): static
    {
        $this->typeMouvement = $typeMouvement;

        return $this;
    }

    public function getDateEffet(): ?\DateTimeImmutable
    {
        return $this->dateEffet;
    }

    public function setDateEffet(?\DateTimeImmutable $dateEffet): static
    {
        $this->dateEffet = $dateEffet;

        return $this;
    }

    public function getNumeroDecision(): ?string
    {
        return $this->numeroDecision;
    }

    public function setNumeroDecision(?string $numeroDecision): static
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

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->typeMouvement?->label() ?? '', $this->dateEffet?->format('d/m/Y') ?? '');
    }
}
