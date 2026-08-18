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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Demande de décision de congé : pour un agent qui n'en a jamais eu, ou dont la
 * dernière a expiré/atteint son terme. Circuit à cinq étapes — voir le
 * commentaire de StatutDemande pour le détail :
 *
 *  1. RH Congé vérifie les pièces, valide (Api/DemandeDecisionController::valider())
 *     ou rejette pour un motif prédéterminé (rejeter()) ;
 *  2. l'agent dépose physiquement au service courrier et le confirme
 *     (Api/MeDemandesController::confirmerDepotCourrierDecision()) ;
 *  3. circuit ministériel hors application ;
 *  4. RH Admin dépose les documents scannés du retour (retour()) ;
 *  5. RH Congé calcule le nombre de jours, génère la DecisionConge et
 *     transmet à l'agent (genererEtTransmettre()) — seule étape qui crée
 *     effectivement la DecisionConge, contrairement aux versions précédentes
 *     de ce circuit.
 *
 * Exposée en lecture seule côté API : toutes les écritures passent par
 * src/Controller/Api/DemandeDecisionController.php (et
 * Api/MeDemandesController.php pour la confirmation de dépôt courrier, action
 * de l'agent lui-même).
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

    /** Documents scannés déposés par le RH Admin au retour du circuit papier (statut RETOURNEE) — voir retour(). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminDocumentRetour = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $nomOriginalDocumentRetour = null;

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

    /**
     * Un agent qui n'est pas nouvellement affecté a forcément déjà eu une
     * décision de congé : le numéro et l'année d'obtention de cette dernière
     * décision sont donc exigés en plus de la pièce jointe correspondante
     * (voir les formulaires Angular nouvelle-demande-decision et
     * demande-decision-form, qui ne collectent que l'année — stockée ici au
     * 1er janvier de cette année, affinée si besoin par le RH Congé à la
     * génération de la décision, voir DemandeDecisionController::genererEtTransmettre()).
     */
    #[Assert\Callback]
    public function validerInformationsDerniereDecision(ExecutionContextInterface $context): void
    {
        if (false !== $this->nouvellementAffecte) {
            return;
        }

        if (!$this->numeroDerniereDecision) {
            $context->buildViolation("Merci d'indiquer le numéro de la dernière décision de congé.")
                ->atPath('numeroDerniereDecision')
                ->addViolation();
        }

        if (!$this->dateDerniereDecision) {
            $context->buildViolation("Merci d'indiquer l'année d'obtention de la dernière décision de congé.")
                ->atPath('dateDerniereDecision')
                ->addViolation();
        }
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

    public function getCheminDocumentRetour(): ?string
    {
        return $this->cheminDocumentRetour;
    }

    public function setCheminDocumentRetour(?string $cheminDocumentRetour): static
    {
        $this->cheminDocumentRetour = $cheminDocumentRetour;

        return $this;
    }

    public function getNomOriginalDocumentRetour(): ?string
    {
        return $this->nomOriginalDocumentRetour;
    }

    public function setNomOriginalDocumentRetour(?string $nomOriginalDocumentRetour): static
    {
        $this->nomOriginalDocumentRetour = $nomOriginalDocumentRetour;

        return $this;
    }

    #[Groups(['api:read'])]
    public function isHasDocumentRetour(): bool
    {
        return null !== $this->cheminDocumentRetour;
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
    public function isValidee(): bool
    {
        return StatutDemande::VALIDEE === $this->statut;
    }

    #[Groups(['api:read'])]
    public function isDeposeeCourrier(): bool
    {
        return StatutDemande::DEPOSEE_COURRIER === $this->statut;
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
