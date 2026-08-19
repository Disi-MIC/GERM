<?php

namespace App\Entity;

use App\Entity\Enum\Carburant;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Élément du parc automobile du Ministère.
 *
 * Pas de #[ApiResource] : la gestion complète reste Twig-only (superadmin),
 * mais les champs non sensibles (hors valeur d'acquisition) portent
 * #[Groups(['api:read'])] pour la vue en lecture seule "Mon parc automobile"
 * exposée au chauffeur affecté via src/Controller/Api/MeController.php.
 */
#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicule')]
#[ORM\UniqueConstraint(name: 'UNIQ_VEHICULE_IMMATRICULATION', columns: ['immatriculation'])]
#[UniqueEntity(fields: ['immatriculation'], message: 'Cette immatriculation est déjà utilisée par un autre véhicule.')]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['api:read'])]
    private ?string $immatriculation = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de véhicule est obligatoire.')]
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
    private ?string $numeroChassis = null;

    #[ORM\Column(length: 20, enumType: Carburant::class, nullable: true)]
    #[Groups(['api:read'])]
    private ?Carburant $carburant = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateAcquisition = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $valeurAcquisition = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?int $kilometrage = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $assuranceJusquau = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $visiteTechniqueJusquau = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'état est obligatoire.")]
    #[Groups(['api:read'])]
    private ?ListeValeur $etat = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service/direction est obligatoire.')]
    #[Groups(['api:read'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Personnel $chauffeurAffecte = null;

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

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

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

    public function getNumeroChassis(): ?string
    {
        return $this->numeroChassis;
    }

    public function setNumeroChassis(?string $numeroChassis): static
    {
        $this->numeroChassis = $numeroChassis;

        return $this;
    }

    public function getCarburant(): ?Carburant
    {
        return $this->carburant;
    }

    public function setCarburant(?Carburant $carburant): static
    {
        $this->carburant = $carburant;

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

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(?int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;

        return $this;
    }

    public function getAssuranceJusquau(): ?\DateTimeImmutable
    {
        return $this->assuranceJusquau;
    }

    public function setAssuranceJusquau(?\DateTimeImmutable $assuranceJusquau): static
    {
        $this->assuranceJusquau = $assuranceJusquau;

        return $this;
    }

    public function getVisiteTechniqueJusquau(): ?\DateTimeImmutable
    {
        return $this->visiteTechniqueJusquau;
    }

    public function setVisiteTechniqueJusquau(?\DateTimeImmutable $visiteTechniqueJusquau): static
    {
        $this->visiteTechniqueJusquau = $visiteTechniqueJusquau;

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

    public function getChauffeurAffecte(): ?Personnel
    {
        return $this->chauffeurAffecte;
    }

    public function setChauffeurAffecte(?Personnel $chauffeurAffecte): static
    {
        $this->chauffeurAffecte = $chauffeurAffecte;

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
        return sprintf('%s %s (%s)', $this->marque, $this->modele, $this->immatriculation);
    }
}
