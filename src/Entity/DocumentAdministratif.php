<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\DocumentAdministratifRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Document administratif d'un agent (CNI, diplôme, contrat, acte de naissance...),
 * archivé par le RH Personnel. Le fichier est toujours présent dès la création
 * (contrairement aux pièces jointes des demandes, facultatives) : pas d'opération
 * Post native — la création (multipart, fichier + métadonnées en un seul envoi)
 * et la suppression passent par src/Controller/Api/DocumentAdministratifController.php.
 * Pas de Put : un document erroné se supprime et se réimporte, comme pour
 * HistoriqueAffectation (journal, pas de correction en place).
 */
#[ORM\Entity(repositoryClass: DocumentAdministratifRepository::class)]
#[ORM\Table(name: 'document_administratif')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/documents-administratifs', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/documents-administratifs/{id}'),
    ],
    security: "is_granted('ROLE_RH_PERSONNEL')",
    normalizationContext: ['groups' => ['api:read']],
)]
class DocumentAdministratif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'documentsAdministratifs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le type de document est obligatoire.')]
    #[Groups(['api:read'])]
    private ?ListeValeur $type = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Groups(['api:read'])]
    private ?string $libelle = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateDocument = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(length: 255)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255)]
    #[Groups(['api:read'])]
    private ?string $nomOriginal = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $observations = null;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * true si déposé par l'agent lui-même (Api/MeDemandesController::creerDocumentAdministratif,
     * limité à un type-document "justificatif" — CNI, diplôme, CV... — jamais
     * aux types à valeur d'acte RH comme decision_nomination/attestation/contrat,
     * qui restent exclusivement créés par le RH Personnel), false si déposé
     * par le RH Personnel (Api/DocumentAdministratifController::create). Sert
     * uniquement de repère visuel côté liste RH, aucun effet sur les droits.
     */
    #[ORM\Column]
    #[Groups(['api:read'])]
    private bool $soumisParAgent = false;

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

    public function getType(): ?ListeValeur
    {
        return $this->type;
    }

    public function setType(?ListeValeur $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDateDocument(): ?\DateTimeImmutable
    {
        return $this->dateDocument;
    }

    public function setDateDocument(?\DateTimeImmutable $dateDocument): static
    {
        $this->dateDocument = $dateDocument;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeImmutable $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getCheminFichier(): ?string
    {
        return $this->cheminFichier;
    }

    public function setCheminFichier(string $cheminFichier): static
    {
        $this->cheminFichier = $cheminFichier;

        return $this;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(string $nomOriginal): static
    {
        $this->nomOriginal = $nomOriginal;

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

    public function isSoumisParAgent(): bool
    {
        return $this->soumisParAgent;
    }

    public function setSoumisParAgent(bool $soumisParAgent): static
    {
        $this->soumisParAgent = $soumisParAgent;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->libelle ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
