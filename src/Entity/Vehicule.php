<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\Carburant;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Élément du parc automobile du Ministère.
 *
 * Exposée en lecture seule via API Platform (voir Api/VehiculeController.php
 * pour les écritures — même principe que MaterielInformatique) et réservée
 * au superadmin (ROLE_SUPERADMIN) : aucun rôle dédié à la gestion du parc
 * automobile n'existe encore, contrairement au parc informatique/RH.
 * L'admin Twig historique (Controller/Admin/VehiculeController) reste en
 * place à côté, même périmètre ROLE_SUPERADMIN.
 *
 * `valeurAcquisition` porte le groupe api:read:admin (pas api:read) : la vue
 * self-service "Mon parc automobile" (MeController::vehicules()) partage le
 * groupe api:read et ne doit jamais exposer cette donnée au chauffeur
 * affecté — même logique que MaterielInformatique::$fournisseur
 * (api:read:rh).
 */
#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicule')]
#[ORM\UniqueConstraint(name: 'UNIQ_VEHICULE_IMMATRICULATION', columns: ['immatriculation'])]
#[UniqueEntity(fields: ['immatriculation'], message: 'Cette immatriculation est déjà utilisée par un autre véhicule.')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/vehicules', order: ['immatriculation' => 'ASC'], paginationEnabled: false),
        new Get(uriTemplate: '/vehicules/{id}'),
    ],
    security: "is_granted('ROLE_SUPERADMIN')",
    normalizationContext: ['groups' => ['api:read', 'api:read:admin']],
)]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $immatriculation = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de véhicule est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $type = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $marque = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $modele = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroChassis = null;

    #[ORM\Column(length: 20, enumType: Carburant::class, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?Carburant $carburant = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateAcquisition = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Groups(['api:read:admin', 'api:write'])]
    private ?string $valeurAcquisition = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?int $kilometrage = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $assuranceJusquau = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $visiteTechniqueJusquau = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'état est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $etat = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service/direction est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $chauffeurAffecte = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    /** Intervalle entre deux vidanges, en km (ex. 10000) — null si non suivi. */
    #[ORM\Column(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?int $periodiciteVidangeKm = null;

    /**
     * Kilométrage/date de la dernière vidange journalisée — dénormalisé,
     * maintenu par Api/HistoriqueVidangeController à chaque création/
     * suppression d'entrée (voir HistoriqueVidangeRepository::findDerniereVidange()),
     * pour ne pas recalculer le dernier relevé à chaque lecture de la fiche.
     * Jamais saisi directement (pas de groupe api:write).
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?int $derniereVidangeKm = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $derniereVidangeDate = null;

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

    public function getPeriodiciteVidangeKm(): ?int
    {
        return $this->periodiciteVidangeKm;
    }

    public function setPeriodiciteVidangeKm(?int $periodiciteVidangeKm): static
    {
        $this->periodiciteVidangeKm = $periodiciteVidangeKm;

        return $this;
    }

    public function getDerniereVidangeKm(): ?int
    {
        return $this->derniereVidangeKm;
    }

    public function setDerniereVidangeKm(?int $derniereVidangeKm): static
    {
        $this->derniereVidangeKm = $derniereVidangeKm;

        return $this;
    }

    public function getDerniereVidangeDate(): ?\DateTimeImmutable
    {
        return $this->derniereVidangeDate;
    }

    public function setDerniereVidangeDate(?\DateTimeImmutable $derniereVidangeDate): static
    {
        $this->derniereVidangeDate = $derniereVidangeDate;

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

    /**
     * Statut affiché pour l'échéance d'assurance — même fenêtre de 30 jours
     * que VehiculeRepository::findEcheancesProches(), pour rester cohérent
     * entre la fiche et l'alerte du tableau de bord superadmin.
     *
     * @return array{label: string, badgeClass: string}|null
     */
    #[Groups(['api:read'])]
    public function getStatutAssurance(): ?array
    {
        return $this->statutEcheance($this->assuranceJusquau);
    }

    /** @return array{label: string, badgeClass: string}|null */
    #[Groups(['api:read'])]
    public function getStatutVisiteTechnique(): ?array
    {
        return $this->statutEcheance($this->visiteTechniqueJusquau);
    }

    /**
     * Statut affiché pour l'échéance de vidange — au kilométrage plutôt qu'à
     * la date (contrairement à l'assurance/la visite technique) : une
     * vidange se programme par usage, pas par calendrier. `null` si le
     * véhicule n'a pas de périodicité renseignée ou aucune vidange
     * journalisée (voir HistoriqueVidange) — rien à afficher plutôt qu'une
     * échéance inventée.
     *
     * @return array{label: string, badgeClass: string}|null
     */
    #[Groups(['api:read'])]
    public function getStatutVidange(): ?array
    {
        if (null === $this->periodiciteVidangeKm || null === $this->derniereVidangeKm || null === $this->kilometrage) {
            return null;
        }

        $kmRestants = ($this->derniereVidangeKm + $this->periodiciteVidangeKm) - $this->kilometrage;

        if ($kmRestants <= 0) {
            return ['label' => 'Dépassée', 'badgeClass' => 'danger'];
        }

        // Marge d'alerte proportionnelle à la périodicité (10 %) plutôt qu'un
        // seuil fixe en km : une vidange tous les 5 000 km et une autre tous
        // les 15 000 km n'ont pas la même marge de prévoyance raisonnable.
        if ($kmRestants <= max(500, (int) round($this->periodiciteVidangeKm * 0.1))) {
            return ['label' => \sprintf('À prévoir (%d km restants)', $kmRestants), 'badgeClass' => 'warning'];
        }

        return ['label' => 'OK', 'badgeClass' => 'success'];
    }

    /** @return array{label: string, badgeClass: string}|null */
    private function statutEcheance(?\DateTimeImmutable $date): ?array
    {
        if (null === $date) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            return ['label' => 'Expirée', 'badgeClass' => 'danger'];
        }

        if ($date <= $today->modify('+30 days')) {
            return ['label' => 'Expire bientôt', 'badgeClass' => 'warning'];
        }

        return ['label' => 'Valide', 'badgeClass' => 'success'];
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s)', $this->marque, $this->modele, $this->immatriculation);
    }
}
