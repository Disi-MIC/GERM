<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\HistoriqueChangementMaterielRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Journal append-only des changements d'état, de service ou de licence
 * installée (système d'exploitation/suite bureautique/antivirus) sur un
 * matériel informatique — même logique que HistoriqueAffectationMateriel
 * pour l'affectation, qui reste un journal séparé (structure différente :
 * une "période" avec début/fin, pas un simple avant/après horodaté).
 * Alimenté automatiquement par MaterielInformatiqueController::update() et
 * les actions groupées (bulkEtat/bulkAffectation), jamais créé directement
 * côté client. `champ` porte directement le libellé humain ("État",
 * "Service", "Système d'exploitation"...) plutôt qu'un code : purement
 * consultatif, aucun filtre ni logique ne s'appuie dessus.
 */
#[ORM\Entity(repositoryClass: HistoriqueChangementMaterielRepository::class)]
#[ORM\Table(name: 'historique_changement_materiel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/historiques-changement-materiel', order: ['createdAt' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/historiques-changement-materiel/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['materiel' => 'exact'])]
class HistoriqueChangementMateriel
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

    public function getMateriel(): ?MaterielInformatique
    {
        return $this->materiel;
    }

    public function setMateriel(?MaterielInformatique $materiel): static
    {
        $this->materiel = $materiel;

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
