<?php

namespace App\Entity;

use App\Repository\MaterielInformatiqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Élément du parc informatique du Ministère.
 *
 * Pas de #[ApiResource] : la gestion complète reste Twig-only (superadmin),
 * mais les champs non sensibles (hors valeur d'acquisition/fournisseur)
 * portent #[Groups(['api:read'])] pour la vue en lecture seule "Mon parc
 * informatique" exposée à l'agent affecté via src/Controller/Api/MeController.php.
 */
#[ORM\Entity(repositoryClass: MaterielInformatiqueRepository::class)]
#[ORM\Table(name: 'materiel_informatique')]
#[ORM\UniqueConstraint(name: 'UNIQ_MATERIEL_NUM_INVENTAIRE', columns: ['numero_inventaire'])]
class MaterielInformatique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Groups(['api:read'])]
    private ?string $numeroInventaire = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de matériel est obligatoire.')]
    #[Groups(['api:read'])]
    private ?ListeValeur $type = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read'])]
    private ?string $marque = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read'])]
    private ?string $modele = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $numeroSerie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $specifications = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateAcquisition = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $valeurAcquisition = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $fournisseur = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $garantieJusquau = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'état est obligatoire.")]
    #[Groups(['api:read'])]
    private ?ListeValeur $etat = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'materiels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service/direction est obligatoire.')]
    #[Groups(['api:read'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'materiels')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Personnel $affecteA = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $observations = null;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroInventaire(): ?string
    {
        return $this->numeroInventaire;
    }

    public function setNumeroInventaire(string $numeroInventaire): static
    {
        $this->numeroInventaire = $numeroInventaire;

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

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getNumeroSerie(): ?string
    {
        return $this->numeroSerie;
    }

    public function setNumeroSerie(?string $numeroSerie): static
    {
        $this->numeroSerie = $numeroSerie;

        return $this;
    }

    public function getSpecifications(): ?string
    {
        return $this->specifications;
    }

    public function setSpecifications(?string $specifications): static
    {
        $this->specifications = $specifications;

        return $this;
    }

    public function getDateAcquisition(): ?\DateTimeImmutable
    {
        return $this->dateAcquisition;
    }

    public function setDateAcquisition(?\DateTimeImmutable $dateAcquisition): static
    {
        $this->dateAcquisition = $dateAcquisition;

        return $this;
    }

    public function getValeurAcquisition(): ?string
    {
        return $this->valeurAcquisition;
    }

    public function setValeurAcquisition(?string $valeurAcquisition): static
    {
        $this->valeurAcquisition = $valeurAcquisition;

        return $this;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getGarantieJusquau(): ?\DateTimeImmutable
    {
        return $this->garantieJusquau;
    }

    public function setGarantieJusquau(?\DateTimeImmutable $garantieJusquau): static
    {
        $this->garantieJusquau = $garantieJusquau;

        return $this;
    }

    public function getEtat(): ?ListeValeur
    {
        return $this->etat;
    }

    public function setEtat(?ListeValeur $etat): static
    {
        $this->etat = $etat;

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

    public function getAffecteA(): ?Personnel
    {
        return $this->affecteA;
    }

    public function setAffecteA(?Personnel $affecteA): static
    {
        $this->affecteA = $affecteA;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s)', $this->marque, $this->modele, $this->numeroInventaire);
    }
}
