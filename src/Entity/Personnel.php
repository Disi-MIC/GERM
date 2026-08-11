<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\Sexe;
use App\Entity\Enum\StatutPersonnel;
use App\Repository\PersonnelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fiche RH d'un agent du Ministère (ressource "Personnel").
 *
 * Exposée en lecture seule côté API (frontend Angular, rôle
 * ROLE_RH_PERSONNEL — la liste est aussi accessible à ROLE_RH_CARTE_PRO pour
 * le sélecteur agent des formulaires carte professionnelle) : toutes les
 * écritures (création avec mouvement de nomination initial, édition,
 * suppression avec son garde-fou métier, photo) passent par
 * src/Controller/Api/PersonnelController.php — même choix que
 * CarteProfessionnelle. Twig ne reste accessible qu'au superadmin, en
 * secours.
 */
#[ORM\Entity(repositoryClass: PersonnelRepository::class)]
#[ORM\Table(name: 'personnel')]
#[ORM\UniqueConstraint(name: 'UNIQ_PERSONNEL_MATRICULE', columns: ['matricule'])]
#[ApiResource(
    operations: [
        // Liste accessible aussi à ROLE_RH_CARTE_PRO (sélection de l'agent
        // dans les formulaires carte/demande de carte professionnelle) et
        // ROLE_IT_TECHNICIEN (sélecteur "Affecté à" du formulaire Matériel
        // informatique, domaine IT distinct du RH).
        new GetCollection(security: "is_granted('ROLE_RH_PERSONNEL') or is_granted('ROLE_RH_CARTE_PRO') or is_granted('ROLE_IT_TECHNICIEN')"),
        // Même élargissement que GetCollection ci-dessus : la résolution d'IRI
        // (ex. Maintenance.realisePar envoyé par le formulaire IT) invoque
        // cette opération Get, pas seulement la sécurité de base de la ressource.
        new Get(security: "is_granted('ROLE_RH_PERSONNEL') or is_granted('ROLE_RH_CARTE_PRO') or is_granted('ROLE_IT_TECHNICIEN')"),
    ],
    security: "is_granted('ROLE_RH_PERSONNEL')",
    normalizationContext: ['groups' => ['api:read']],
)]
class Personnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    // Optionnel : un agent nouvellement recruté peut être enregistré avant
    // l'attribution officielle de son matricule (voir DemandeCartePro, où ce
    // champ détermine le justificatif attendu pour une nouvelle carte —
    // prise de service si présent, contrat sinon).
    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $matricule = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 1, enumType: Sexe::class)]
    #[Groups(['api:read', 'api:write'])]
    private ?Sexe $sexe = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Groups(['api:read', 'api:write'])]
    private ?string $fonction = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $grade = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de contrat est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $typeContrat = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateEmbauche = null;

    #[ORM\Column(length: 20, enumType: StatutPersonnel::class)]
    #[Groups(['api:read', 'api:write'])]
    private ?StatutPersonnel $statut = StatutPersonnel::ACTIF;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    #[Groups(['api:read', 'api:write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'personnels')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le service/direction est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?Service $service = null;

    #[ORM\OneToOne(inversedBy: 'personnel', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'affecteA', targetEntity: MaterielInformatique::class)]
    private Collection $materiels;

    #[ORM\OneToMany(mappedBy: 'chauffeurAffecte', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: HistoriqueAffectation::class)]
    #[ORM\OrderBy(['dateEffet' => 'DESC'])]
    private Collection $historiqueAffectations;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: CarteProfessionnelle::class)]
    #[ORM\OrderBy(['dateDelivrance' => 'DESC'])]
    private Collection $cartesProfessionnelles;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: DemandeCartePro::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $demandesCartePro;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: Conge::class)]
    #[ORM\OrderBy(['dateDebut' => 'DESC'])]
    private Collection $conges;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: DemandeJouissance::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $demandesJouissance;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: DecisionConge::class)]
    #[ORM\OrderBy(['dateDecision' => 'DESC'])]
    private Collection $decisionsConge;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: DemandeDecision::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $demandesDecision;

    #[ORM\OneToMany(mappedBy: 'personnel', targetEntity: DocumentAdministratif::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $documentsAdministratifs;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->materiels = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
        $this->historiqueAffectations = new ArrayCollection();
        $this->cartesProfessionnelles = new ArrayCollection();
        $this->demandesCartePro = new ArrayCollection();
        $this->conges = new ArrayCollection();
        $this->demandesJouissance = new ArrayCollection();
        $this->decisionsConge = new ArrayCollection();
        $this->demandesDecision = new ArrayCollection();
        $this->documentsAdministratifs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getNomComplet(): string
    {
        return trim(sprintf('%s %s', $this->prenom, $this->nom));
    }

    public function getSexe(): ?Sexe
    {
        return $this->sexe;
    }

    public function setSexe(?Sexe $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getTypeContrat(): ?ListeValeur
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?ListeValeur $typeContrat): static
    {
        $this->typeContrat = $typeContrat;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeImmutable
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?\DateTimeImmutable $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;

        return $this;
    }

    public function getStatut(): ?StatutPersonnel
    {
        return $this->statut;
    }

    public function setStatut(?StatutPersonnel $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

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

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, MaterielInformatique>
     */
    public function getMateriels(): Collection
    {
        return $this->materiels;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    /**
     * @return Collection<int, HistoriqueAffectation>
     */
    public function getHistoriqueAffectations(): Collection
    {
        return $this->historiqueAffectations;
    }

    /**
     * @return Collection<int, CarteProfessionnelle>
     */
    public function getCartesProfessionnelles(): Collection
    {
        return $this->cartesProfessionnelles;
    }

    /**
     * @return Collection<int, DemandeCartePro>
     */
    public function getDemandesCartePro(): Collection
    {
        return $this->demandesCartePro;
    }

    /**
     * @return Collection<int, Conge>
     */
    public function getConges(): Collection
    {
        return $this->conges;
    }

    /**
     * @return Collection<int, DemandeJouissance>
     */
    public function getDemandesJouissance(): Collection
    {
        return $this->demandesJouissance;
    }

    /**
     * @return Collection<int, DecisionConge>
     */
    public function getDecisionsConge(): Collection
    {
        return $this->decisionsConge;
    }

    /**
     * @return Collection<int, DemandeDecision>
     */
    public function getDemandesDecision(): Collection
    {
        return $this->demandesDecision;
    }

    /**
     * @return Collection<int, DocumentAdministratif>
     */
    public function getDocumentsAdministratifs(): Collection
    {
        return $this->documentsAdministratifs;
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
        return $this->getNomComplet();
    }
}
