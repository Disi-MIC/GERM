<?php

namespace App\Entity;

use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Repository\CarteProfessionnelleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Carte professionnelle d'un agent. Historisée : chaque délivrance ou
 * renouvellement crée une nouvelle ligne, l'historique complet est conservé
 * (utile en cas de perte, vol ou renouvellement).
 */
#[ORM\Entity(repositoryClass: CarteProfessionnelleRepository::class)]
#[ORM\Table(name: 'carte_professionnelle')]
class CarteProfessionnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'cartesProfessionnelles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de carte est obligatoire.')]
    private ?string $numero = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de délivrance est obligatoire.')]
    private ?\DateTimeImmutable $dateDelivrance = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(length: 20, enumType: StatutCarteProfessionnelle::class)]
    private StatutCarteProfessionnelle $statut = StatutCarteProfessionnelle::VALIDE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomOriginal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminQrCode = null;

    #[ORM\Column]
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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDateDelivrance(): ?\DateTimeImmutable
    {
        return $this->dateDelivrance;
    }

    public function setDateDelivrance(?\DateTimeImmutable $dateDelivrance): static
    {
        $this->dateDelivrance = $dateDelivrance;
        $this->dateExpiration = $dateDelivrance?->modify('+5 years');

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function getStatut(): StatutCarteProfessionnelle
    {
        return $this->statut;
    }

    public function setStatut(StatutCarteProfessionnelle $statut): static
    {
        $this->statut = $statut;

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

    public function getCheminFichier(): ?string
    {
        return $this->cheminFichier;
    }

    public function setCheminFichier(?string $cheminFichier): static
    {
        $this->cheminFichier = $cheminFichier;

        return $this;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(?string $nomOriginal): static
    {
        $this->nomOriginal = $nomOriginal;

        return $this;
    }

    public function getCheminQrCode(): ?string
    {
        return $this->cheminQrCode;
    }

    public function setCheminQrCode(?string $cheminQrCode): static
    {
        $this->cheminQrCode = $cheminQrCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    public function getStatutAffiche(): array
    {
        if (StatutCarteProfessionnelle::VALIDE !== $this->statut) {
            return [
                'label' => $this->statut->label(),
                'badgeClass' => StatutCarteProfessionnelle::ANNULEE === $this->statut ? 'secondary' : 'danger',
            ];
        }

        $today = new \DateTimeImmutable('today');

        if (null === $this->dateExpiration || $this->dateExpiration < $today) {
            return ['label' => 'Expirée', 'badgeClass' => 'secondary'];
        }

        if ($this->dateExpiration <= $today->modify('+60 days')) {
            return ['label' => 'Expire bientôt', 'badgeClass' => 'warning'];
        }

        return ['label' => 'Valide', 'badgeClass' => 'success'];
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->numero ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
