<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MaterielInformatiqueRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Élément du parc informatique du Ministère.
 *
 * Exposée en lecture seule côté API (frontend Angular, rôle ROLE_IT_STOCK) :
 * la création, l'édition et la suppression passent par
 * src/Controller/Api/MaterielInformatiqueController.php — même logique que
 * Personnel. `fournisseur` porte un groupe de lecture distinct (api:read:rh)
 * plutôt que api:read : la vue en lecture seule "Mon parc informatique"
 * exposée à l'agent affecté (src/Controller/Api/MeController.php) ne demande
 * que le groupe api:read et ne doit jamais voir ce champ. Aucune donnée
 * financière (coût, valeur d'acquisition) n'est suivie ici — hors périmètre
 * du parc informatique, volontairement.
 */
#[ORM\Entity(repositoryClass: MaterielInformatiqueRepository::class)]
#[ORM\Table(name: 'materiel_informatique')]
#[ORM\UniqueConstraint(name: 'UNIQ_MATERIEL_NUM_INVENTAIRE', columns: ['numero_inventaire'])]
// Sans ce contrôle applicatif, un doublon ne serait détecté qu'à l'écriture
// en base (contrainte SQL ci-dessus), remontant une erreur 500 brute au lieu
// d'un 422 exploitable par le formulaire.
#[UniqueEntity(fields: ['numeroInventaire'], message: 'Ce numéro d\'inventaire est déjà utilisé par un autre matériel.')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/materiels-informatiques', order: ['numeroInventaire' => 'ASC'], paginationEnabled: false),
        // Élargi à ROLE_IT_TICKETS : la résolution d'IRI (TicketIncident.materiel
        // envoyé à la création d'un ticket) invoque cette opération Get, pas
        // seulement la sécurité de base de la ressource (réservée au Stock).
        new Get(uriTemplate: '/materiels-informatiques/{id}', security: "is_granted('ROLE_IT_STOCK') or is_granted('ROLE_IT_TICKETS')"),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read', 'api:read:rh']],
)]
// Filtre serveur — sans lui, le frontend devait charger tout le parc pour
// filtrer côté JS (voir MaterielInformatiqueListComponent), intenable dès
// que le parc dépasse quelques dizaines de matériels (et incompatible avec
// un client mobile qui n'a pas intérêt à tout rapatrier pour chercher un
// matériel). Remplace MaterielInformatiqueRepository::search(), qui n'était
// jamais appelée par aucun contrôleur.
#[ApiFilter(SearchFilter::class, properties: [
    'numeroInventaire' => 'partial',
    'marque' => 'partial',
    'modele' => 'partial',
    'numeroSerie' => 'partial',
    'type' => 'exact',
    'etat' => 'exact',
    'service' => 'exact',
    'affecteA' => 'exact',
])]
class MaterielInformatique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroInventaire = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de matériel est obligatoire.')]
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
    private ?string $numeroSerie = null;

    /** Numéro de poste interne (4 chiffres) — pertinent seulement pour un matériel de type "Téléphone", mais non contraint côté serveur (voir ListeValeur::TYPE_MATERIEL, une simple donnée, pas un enum figé). */
    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'Le numéro de poste doit comporter exactement 4 chiffres.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroTelephone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $specifications = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateAcquisition = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['api:read:rh', 'api:write:rh'])]
    private ?string $fournisseur = null;

    // Plutôt qu'une date de garantie (jamais renseignée en pratique — 0 des
    // 39 matériels réels en prod ne l'avaient, au même titre que
    // dateAcquisition d'ailleurs) : la date de mise en service reste
    // pertinente même pour un matériel antérieur à la plateforme, d'où son
    // caractère optionnel ici aussi.
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateMiseEnService = null;

    /**
     * Fréquence de maintenance préventive, en mois (3/6/12...) — null si aucun
     * plan n'est requis pour ce matériel. Sert à calculer l'échéance de la
     * prochaine maintenance côté DashboardController (dernière Maintenance
     * réalisée, ou à défaut dateAcquisition/createdAt, + ce nombre de mois),
     * jamais stockée directement pour rester toujours à jour sans
     * synchronisation manuelle.
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La périodicité doit être un nombre de mois positif.')]
    #[Groups(['api:read', 'api:write'])]
    private ?int $periodiciteMois = null;

    /**
     * Logiciels installés — trois catégories fixes (voir CategorieListeValeur
     * LOGICIEL_OS/LOGICIEL_ANTIVIRUS/LOGICIEL_BUREAUTIQUE), toutes optionnelles
     * (un routeur/imprimante n'a ni OS ni bureautique). Chaque champ pointe
     * directement vers la LicenceLogiciel qui couvre cette installation
     * (jamais vers le seul produit ListeValeur) : la licence à sélectionner
     * doit exister au préalable dans le registre des licences — c'est cette
     * association explicite, pas une simple correspondance par produit, qui
     * fait apparaître le matériel dans le décompte "postes couverts" de la
     * bonne licence (voir MaterielInformatiqueRepository::countParLicence()
     * et App\State\LicenceLogicielProvider).
     */
    #[ORM\ManyToOne(targetEntity: LicenceLogiciel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?LicenceLogiciel $systemeExploitation = null;

    #[ORM\ManyToOne(targetEntity: LicenceLogiciel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?LicenceLogiciel $suiteBureautique = null;

    #[ORM\ManyToOne(targetEntity: LicenceLogiciel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?LicenceLogiciel $antivirus = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'état est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $etat = null;

    // Nullable (contrairement à $etat) : ajouté après coup, tout le parc
    // existant n'a pas encore été évalué — voir CategorieListeValeur::
    // NIVEAU_VULNERABILITE, gérée par le RH Admin comme les autres listes
    // de valeurs (états, types...).
    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $niveauVulnerabilite = null;

    // Optionnel : un agent affecté (voir $affecteA) est déjà rattaché à un
    // service bien précis (Personnel::$service), donc ce champ ferait double
    // emploi dans ce cas — getService() délègue alors à
    // $affecteA->getService() et nettoyerServiceRedondant() vide la colonne
    // au persist/update pour ne jamais laisser les deux diverger (même
    // logique que User::getNom() vs Personnel::$nom). Reste une valeur
    // propre, et donc utile, uniquement pour un matériel non affecté — et
    // même là, pas obligatoire : un matériel en stock ou réformé n'a pas
    // besoin d'un service connu.
    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'materiels')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'materiels')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $affecteA = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    /**
     * Chemin de stockage (SFTP, voir App\Service\FileStorage) — jamais exposé
     * tel quel côté API (pas de Groups ici), seule l'action dédiée
     * MaterielInformatiqueController::photo() le lit pour servir le fichier.
     * Même mécanisme que Personnel::$photo.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

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

    public function getNumeroTelephone(): ?string
    {
        return $this->numeroTelephone;
    }

    public function setNumeroTelephone(?string $numeroTelephone): static
    {
        $this->numeroTelephone = $numeroTelephone;

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

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getDateMiseEnService(): ?\DateTimeImmutable
    {
        return $this->dateMiseEnService;
    }

    public function setDateMiseEnService(?\DateTimeImmutable $dateMiseEnService): static
    {
        $this->dateMiseEnService = $dateMiseEnService;

        return $this;
    }

    public function getPeriodiciteMois(): ?int
    {
        return $this->periodiciteMois;
    }

    public function setPeriodiciteMois(?int $periodiciteMois): static
    {
        $this->periodiciteMois = $periodiciteMois;

        return $this;
    }

    public function getSystemeExploitation(): ?LicenceLogiciel
    {
        return $this->systemeExploitation;
    }

    public function setSystemeExploitation(?LicenceLogiciel $systemeExploitation): static
    {
        $this->systemeExploitation = $systemeExploitation;

        return $this;
    }

    public function getSuiteBureautique(): ?LicenceLogiciel
    {
        return $this->suiteBureautique;
    }

    public function setSuiteBureautique(?LicenceLogiciel $suiteBureautique): static
    {
        $this->suiteBureautique = $suiteBureautique;

        return $this;
    }

    public function getAntivirus(): ?LicenceLogiciel
    {
        return $this->antivirus;
    }

    public function setAntivirus(?LicenceLogiciel $antivirus): static
    {
        $this->antivirus = $antivirus;

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

    public function getNiveauVulnerabilite(): ?ListeValeur
    {
        return $this->niveauVulnerabilite;
    }

    public function setNiveauVulnerabilite(?ListeValeur $niveauVulnerabilite): static
    {
        $this->niveauVulnerabilite = $niveauVulnerabilite;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->affecteA?->getService() ?? $this->service;
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

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    #[Groups(['api:read'])]
    public function isHasPhoto(): bool
    {
        return null !== $this->photo;
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
     * Vide la copie propre de $service dès qu'un agent est affecté — voir
     * getService(), qui délègue alors à $affecteA->getService(). Une
     * callback plutôt qu'un nettoyage dans chaque contrôleur : trois chemins
     * d'écriture distincts touchent cette entité (API Angular, CRUD Twig de
     * secours, import en masse), tous doivent respecter cet invariant.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function nettoyerServiceRedondant(): void
    {
        if (null !== $this->affecteA) {
            $this->service = null;
        }
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s)', $this->marque, $this->modele, $this->numeroInventaire);
    }
}
