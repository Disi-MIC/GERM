<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Repository\CarteProfessionnelleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Carte professionnelle d'un agent. Historisée : chaque délivrance ou
 * renouvellement crée une nouvelle ligne, l'historique complet est conservé
 * (utile en cas de perte, vol ou renouvellement).
 *
 * Exposée en lecture seule côté API (frontend Angular, rôle
 * ROLE_RH_CARTE_PRO) : toutes les écritures (création, édition, suppression,
 * génération du PDF, validation) passent par des actions dédiées dans
 * src/Controller/Api/CarteProfessionnelleController.php, jamais par des
 * opérations API Platform natives — la génération du PDF/QR et le garde-fou
 * de suppression (photo de l'agent) sont des effets de bord trop spécifiques
 * pour un Post/Put/Delete générique (même choix que Personnel::delete()).
 */
#[ORM\Entity(repositoryClass: CarteProfessionnelleRepository::class)]
#[ORM\Table(name: 'carte_professionnelle')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/cartes-professionnelles', order: ['dateDelivrance' => 'DESC']),
        new Get(uriTemplate: '/cartes-professionnelles/{id}'),
    ],
    security: "is_granted('ROLE_RH_CARTE_PRO')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(BooleanFilter::class, properties: ['valideeParAdminRh'])]
class CarteProfessionnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'cartesProfessionnelles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de carte est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numero = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de délivrance est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDelivrance = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(length: 20, enumType: StatutCarteProfessionnelle::class)]
    #[Groups(['api:read', 'api:write'])]
    private StatutCarteProfessionnelle $statut = StatutCarteProfessionnelle::VALIDE;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $nomOriginal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminQrCode = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['api:read'])]
    private bool $valideeParAdminRh = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $valideePar = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $valideeLe = null;

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

    /**
     * Encore trop tôt pour demander un renouvellement : carte valide, avec
     * une expiration connue à plus de 60 jours — même seuil que
     * getStatutAffiche() ("Expire bientôt"), pour rester cohérent entre
     * l'affichage et la règle qui bloque la demande côté
     * MeDemandesController::creerDemandeCartePro().
     */
    public function estTropTotPourRenouvellement(): bool
    {
        if (StatutCarteProfessionnelle::VALIDE !== $this->statut || null === $this->dateExpiration) {
            return false;
        }

        return $this->dateExpiration > (new \DateTimeImmutable('today'))->modify('+60 days');
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

    public function isValideeParAdminRh(): bool
    {
        return $this->valideeParAdminRh;
    }

    public function getValideePar(): ?User
    {
        return $this->valideePar;
    }

    #[Groups(['api:read'])]
    public function getValideeParNom(): ?string
    {
        return $this->valideePar?->getNomComplet();
    }

    public function getValideeLe(): ?\DateTimeImmutable
    {
        return $this->valideeLe;
    }

    #[Groups(['api:read'])]
    public function isHasFichier(): bool
    {
        return null !== $this->cheminFichier;
    }

    /**
     * Marque la carte comme validée par le RH Admin : le cachet et la
     * signature de l'autorité ne sont ajoutés au PDF qu'à partir de là (voir
     * CarteProfessionnellePdfGenerator).
     */
    public function valider(User $validateur): void
    {
        $this->valideeParAdminRh = true;
        $this->valideePar = $validateur;
        $this->valideeLe = new \DateTimeImmutable();
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    #[Groups(['api:read'])]
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
