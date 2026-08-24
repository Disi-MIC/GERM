<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\CouleurCartouche;
use App\Repository\ChangementCartoucheRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Changement d'une cartouche/toner sur une imprimante (un `MaterielInformatique`
 * comme un autre, via son `type`) — simple journal, comme Maintenance : la
 * demande de cartouche se fait par appel téléphonique, hors application ; c'est
 * l'IT qui consigne ici, au moment où il effectue réellement le changement.
 * Aucun catalogue cartouche↔imprimante séparé : c'est cet historique lui-même
 * qui, par matériel et par couleur, permet de calculer la durée d'écoulement
 * entre deux changements (voir CartouchesInformatiqueListComponent côté Angular).
 *
 * Exposée en lecture seule côté API : la création et la suppression passent par
 * App\Controller\Api\ChangementCartoucheController — `enregistrePar` n'y est
 * jamais reçu du client, toujours déduit de l'utilisateur connecté.
 */
#[ORM\Entity(repositoryClass: ChangementCartoucheRepository::class)]
#[ORM\Table(name: 'changement_cartouche')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/changements-cartouche', order: ['dateChangement' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/changements-cartouche/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['materiel' => 'exact'])]
class ChangementCartouche
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterielInformatique::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'imprimante concernée est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?MaterielInformatique $materiel = null;

    #[ORM\Column(length: 20, enumType: CouleurCartouche::class)]
    #[Assert\NotNull(message: 'La couleur de la cartouche est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?CouleurCartouche $couleur = null;

    /** Référence de la cartouche (ex. "912XL"), si connue au moment du changement. */
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $reference = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date du changement est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateChangement = null;

    /** Toujours déduit de l'utilisateur connecté par le contrôleur — jamais dans le groupe api:write. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?User $enregistrePar = null;

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

    public function getCouleur(): ?CouleurCartouche
    {
        return $this->couleur;
    }

    public function setCouleur(?CouleurCartouche $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDateChangement(): ?\DateTimeImmutable
    {
        return $this->dateChangement;
    }

    public function setDateChangement(?\DateTimeImmutable $dateChangement): static
    {
        $this->dateChangement = $dateChangement;

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
