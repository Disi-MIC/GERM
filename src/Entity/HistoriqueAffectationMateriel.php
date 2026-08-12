<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HistoriqueAffectationMaterielRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Journal append-only des affectations d'un matériel informatique (qui l'a eu
 * et quand) — même logique que HistoriqueAffectation pour la carrière : jamais
 * modifié après coup, alimenté automatiquement par
 * MaterielInformatiqueController quand `affecteA` change (pas de création
 * directe exposée). Utile pour le contexte d'un ticket d'incident et la
 * traçabilité du parc.
 */
#[ORM\Entity(repositoryClass: HistoriqueAffectationMaterielRepository::class)]
#[ORM\Table(name: 'historique_affectation_materiel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/historique-affectations-materiel', order: ['dateAffectation' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/historique-affectations-materiel/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['materiel' => 'exact'])]
class HistoriqueAffectationMateriel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterielInformatique::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['api:read'])]
    private ?MaterielInformatique $materiel = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateAffectation = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateFinAffectation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $observations = null;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->dateAffectation = new \DateTimeImmutable();
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

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getDateAffectation(): ?\DateTimeImmutable
    {
        return $this->dateAffectation;
    }

    public function setDateAffectation(?\DateTimeImmutable $dateAffectation): static
    {
        $this->dateAffectation = $dateAffectation;

        return $this;
    }

    public function getDateFinAffectation(): ?\DateTimeImmutable
    {
        return $this->dateFinAffectation;
    }

    public function setDateFinAffectation(?\DateTimeImmutable $dateFinAffectation): static
    {
        $this->dateFinAffectation = $dateFinAffectation;

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
