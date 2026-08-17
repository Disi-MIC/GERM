<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\StatutDemande;
use App\Repository\DemandeDecisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Demande de décision de congé : pour un agent qui n'en a jamais eu, ou dont la
 * dernière a expiré/atteint son terme. Circuit à cinq étapes :
 *
 *  1. Le RH Congé vérifie les pièces et génère la DecisionConge (transmettre())
 *     ou rejette pour un motif prédéterminé (rejeter()) ;
 *  2. le RH Admin valide la décision déjà créée (approuver(), ne crée rien de
 *     nouveau — voir DecisionConge::valider()), ce qui déclenche hors
 *     application l'impression, le passage au service courrier et la
 *     signature de l'autorité ;
 *  3. une fois le papier signé revenu, le RH Admin le vérifie et le transmet
 *     au RH Congé (confirmerRetour()) ;
 *  4. le RH Congé remet enfin la décision à l'agent, physiquement et dans
 *     l'application (transmettreAgent()).
 *
 * Exposée en lecture seule côté API : toutes les écritures passent par
 * src/Controller/Api/DemandeDecisionController.php.
 */
#[ORM\Entity(repositoryClass: DemandeDecisionRepository::class)]
#[ORM\Table(name: 'demande_decision')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/demandes-decision', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/demandes-decision/{id}'),
    ],
    security: "is_granted('ROLE_RH_CONGE')",
    normalizationContext: ['groups' => ['api:read']],
)]
class DemandeDecision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'demandesDecision')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDerniereDecision = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroDerniereDecision = null;

    /**
     * Détermine les pièces attendues : un agent nouvellement affecté ne peut
     * pas encore avoir de décision de congé antérieure, donc seule sa prise
     * de service est exigée (piece1) ; sinon l'ancienne décision (piece2)
     * est exigée en plus — voir piece1()/piece2() côté contrôleur et les
     * formulaires Angular (demande-decision-form, nouvelle-demande-decision)
     * qui adaptent labels et champs requis sur ce booléen.
     */
    #[ORM\Column]
    #[Assert\NotNull(message: "Merci d'indiquer si l'agent est nouvellement affecté.")]
    #[Groups(['api:read', 'api:write'])]
    private ?bool $nouvellementAffecte = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $motif = null;

    #[ORM\Column(length: 20, enumType: StatutDemande::class)]
    #[Groups(['api:read'])]
    private StatutDemande $statut = StatutDemande::EN_ATTENTE;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateTraitement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $commentaireTraitement = null;

    /** Motif prédéterminé (ListeValeur, catégorie motif-rejet-decision-conge) — obligatoire quand statut = REFUSEE. */
    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['api:read'])]
    private ?ListeValeur $motifRejet = null;

    #[ORM\ManyToOne(targetEntity: DecisionConge::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read'])]
    private ?DecisionConge $decisionCreee = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: PieceJustificativeDecision::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['api:read'])]
    private Collection $pieces;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->pieces = new ArrayCollection();
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

    public function getDateDerniereDecision(): ?\DateTimeImmutable
    {
        return $this->dateDerniereDecision;
    }

    public function setDateDerniereDecision(?\DateTimeImmutable $dateDerniereDecision): static
    {
        $this->dateDerniereDecision = $dateDerniereDecision;

        return $this;
    }

    public function getNumeroDerniereDecision(): ?string
    {
        return $this->numeroDerniereDecision;
    }

    public function setNumeroDerniereDecision(?string $numeroDerniereDecision): static
    {
        $this->numeroDerniereDecision = $numeroDerniereDecision;

        return $this;
    }

    public function isNouvellementAffecte(): ?bool
    {
        return $this->nouvellementAffecte;
    }

    public function setNouvellementAffecte(?bool $nouvellementAffecte): static
    {
        $this->nouvellementAffecte = $nouvellementAffecte;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getStatut(): StatutDemande
    {
        return $this->statut;
    }

    public function setStatut(StatutDemande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateTraitement(): ?\DateTimeImmutable
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTimeImmutable $dateTraitement): static
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    public function getCommentaireTraitement(): ?string
    {
        return $this->commentaireTraitement;
    }

    public function setCommentaireTraitement(?string $commentaireTraitement): static
    {
        $this->commentaireTraitement = $commentaireTraitement;

        return $this;
    }

    public function getMotifRejet(): ?ListeValeur
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?ListeValeur $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

        return $this;
    }

    public function getDecisionCreee(): ?DecisionConge
    {
        return $this->decisionCreee;
    }

    public function setDecisionCreee(?DecisionConge $decisionCreee): static
    {
        $this->decisionCreee = $decisionCreee;

        return $this;
    }

    /**
     * @return Collection<int, PieceJustificativeDecision>
     */
    public function getPieces(): Collection
    {
        return $this->pieces;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['api:read'])]
    public function isEnAttente(): bool
    {
        return StatutDemande::EN_ATTENTE === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isTransmise(): bool
    {
        return StatutDemande::TRANSMISE === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isApprouvee(): bool
    {
        return StatutDemande::APPROUVEE === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isRetournee(): bool
    {
        return StatutDemande::RETOURNEE === $this->statut;
    }

    public function __toString(): string
    {
        return sprintf('Demande de décision — %s', $this->personnel?->getNomComplet() ?? '');
    }
}
