<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ComposantMaterielRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Composant matériel (RAM, disque dur HDD/SSD, carte graphique...) rattaché à
 * un MaterielInformatique — `type` référence la liste paramétrable ListeValeur
 * (catégorie TYPE_COMPOSANT), `specification` est un texte libre pour le détail
 * technique (ex. "16 Go DDR4 3200MHz", "512 Go SSD NVMe").
 *
 * Exposée en lecture seule côté API Platform (même logique que LicenceLogiciel) :
 * la création, l'édition et la suppression passent par
 * App\Controller\Api\ComposantMaterielController. Embarquée automatiquement
 * dans les lectures de MaterielInformatique (voir MaterielInformatique::$composants,
 * groupe api:read partagé) pour apparaître sans appel supplémentaire aussi bien
 * dans le formulaire IT (ROLE_IT_STOCK) que dans la vue self-service "Mon parc
 * informatique".
 */
#[ORM\Entity(repositoryClass: ComposantMaterielRepository::class)]
#[ORM\Table(name: 'composant_materiel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/composants-materiel', order: ['createdAt' => 'ASC'], paginationEnabled: false),
        new Get(uriTemplate: '/composants-materiel/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['materiel' => 'exact'])]
class ComposantMateriel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaterielInformatique::class, inversedBy: 'composants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le matériel est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?MaterielInformatique $materiel = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de composant est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La spécification est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $specification = null;

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

    public function getSpecification(): ?string
    {
        return $this->specification;
    }

    public function setSpecification(?string $specification): static
    {
        $this->specification = $specification;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
