<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HistoriqueChangementPersonnelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Journal append-only des changements directs sur la fiche Personnel (nom,
 * prénom, matricule, statut, fonction, grade, type de contrat) — même
 * logique que HistoriqueChangementMateriel pour MaterielInformatique.
 * Distinct de HistoriqueAffectation : celui-ci ne trace que les mouvements
 * de carrière formels (nomination, mutation, promotion...), pas une simple
 * correction faite directement sur la fiche via
 * PersonnelController::update() — d'où ce journal séparé, sans lequel une
 * telle correction (y compris de fonction/grade en dehors d'un mouvement de
 * carrière) ne laissait jusqu'ici aucune trace.
 */
#[ORM\Entity(repositoryClass: HistoriqueChangementPersonnelRepository::class)]
#[ORM\Table(name: 'historique_changement_personnel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/historiques-changement-personnel', order: ['createdAt' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/historiques-changement-personnel/{id}'),
    ],
    security: "is_granted('ROLE_RH_PERSONNEL')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['personnel' => 'exact'])]
class HistoriqueChangementPersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['api:read'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Groups(['api:read'])]
    private ?string $champ = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $valeurAvant = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $valeurApres = null;

    /** Toujours déduit de l'utilisateur connecté par le contrôleur — jamais reçu du client. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?User $enregistrePar = null;

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

    public function getChamp(): ?string
    {
        return $this->champ;
    }

    public function setChamp(?string $champ): static
    {
        $this->champ = $champ;

        return $this;
    }

    public function getValeurAvant(): ?string
    {
        return $this->valeurAvant;
    }

    public function setValeurAvant(?string $valeurAvant): static
    {
        $this->valeurAvant = $valeurAvant;

        return $this;
    }

    public function getValeurApres(): ?string
    {
        return $this->valeurApres;
    }

    public function setValeurApres(?string $valeurApres): static
    {
        $this->valeurApres = $valeurApres;

        return $this;
    }

    public function getEnregistrePar(): ?User
    {
        return $this->enregistrePar;
    }

    public function setEnregistrePar(?User $enregistrePar): static
    {
        $this->enregistrePar = $enregistrePar;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getEnregistreParNom(): ?string
    {
        return $this->enregistrePar?->getNomComplet();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
