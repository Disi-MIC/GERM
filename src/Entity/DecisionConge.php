<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\DecisionCongeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Décision de congé (annuel) : autorisation formelle, avec une période de validité,
 * sur laquelle une ou plusieurs demandes de jouissance peuvent s'appuyer tant qu'elle
 * n'a pas expiré. Créée automatiquement par le RH Congé à la dernière étape du
 * circuit d'une DemandeDecision (Api/DemandeDecisionController::genererEtTransmettre(),
 * $genereePar/$nombreJours renseignés à cette occasion — le circuit d'approbation
 * ministériel a déjà eu lieu physiquement avant, voir DemandeDecision), ou
 * directement pour enregistrer une décision papier antérieure à l'application
 * (decision-conge-form, superadmin).
 *
 * Exposée en lecture seule côté API : les écritures passent par
 * src/Controller/Api/DecisionCongeController.php (la suppression y est
 * bloquée si des demandes de jouissance s'appuient encore sur la décision —
 * la collection `demandesJouissance` n'est donc volontairement pas exposée,
 * ce garde-fou reste serveur-only).
 */
#[ORM\Entity(repositoryClass: DecisionCongeRepository::class)]
#[ORM\Table(name: 'decision_conge')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/decisions-conge', order: ['dateExpiration' => 'DESC']),
        new Get(uriTemplate: '/decisions-conge/{id}'),
    ],
    security: "is_granted('ROLE_RH_CONGE')",
    normalizationContext: ['groups' => ['api:read']],
)]
class DecisionConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'decisionsConge')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de décision est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'octroi est obligatoire.")]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDecision = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    #[Assert\GreaterThan(propertyPath: 'dateDecision', message: "La date d'expiration doit être postérieure à la date d'octroi.")]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

    /** Nombre de jours de congé accordés par cette décision — renseigné par le RH Congé au moment de la transmission (DemandeDecisionController::transmettre()), absent pour les décisions saisies directement. */
    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?int $nombreJours = null;

    /**
     * Début de la période de service ayant ouvert droit à ce congé (fin :
     * $dateDecision ci-dessus) — copié depuis DemandeDecision::$dateDerniereDecision
     * ou $datePriseDeService au moment de la génération (voir
     * DemandeDecisionController::genererEtTransmettre()), absent pour les
     * décisions saisies directement.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $periodeDebut = null;

    /**
     * Texte légal par défaut au moment de la génération — copié depuis
     * ParametresDecisionConge (réglages RH Admin, voir son commentaire de
     * classe) plutôt que référencé dynamiquement, pour qu'une modification
     * ultérieure des réglages ne change jamais le contenu d'une décision
     * déjà délivrée.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $visasDecrets = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $article2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $article3 = null;

    /** L'opérateur RH Congé ayant généré la décision — voir DemandeDecisionController::genererEtTransmettre(). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $genereePar = null;

    #[ORM\OneToMany(mappedBy: 'decision', targetEntity: DemandeJouissance::class)]
    private Collection $demandesJouissance;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->demandesJouissance = new ArrayCollection();
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

    public function getNumeroDecision(): ?string
    {
        return $this->numeroDecision;
    }

    public function setNumeroDecision(string $numeroDecision): static
    {
        $this->numeroDecision = $numeroDecision;

        return $this;
    }

    public function getDateDecision(): ?\DateTimeImmutable
    {
        return $this->dateDecision;
    }

    public function setDateDecision(?\DateTimeImmutable $dateDecision): static
    {
        $this->dateDecision = $dateDecision;

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

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function getNombreJours(): ?int
    {
        return $this->nombreJours;
    }

    public function setNombreJours(?int $nombreJours): static
    {
        $this->nombreJours = $nombreJours;

        return $this;
    }

    public function getPeriodeDebut(): ?\DateTimeImmutable
    {
        return $this->periodeDebut;
    }

    public function setPeriodeDebut(?\DateTimeImmutable $periodeDebut): static
    {
        $this->periodeDebut = $periodeDebut;

        return $this;
    }

    public function getVisasDecrets(): ?string
    {
        return $this->visasDecrets;
    }

    public function setVisasDecrets(?string $visasDecrets): static
    {
        $this->visasDecrets = $visasDecrets;

        return $this;
    }

    public function getArticle2(): ?string
    {
        return $this->article2;
    }

    public function setArticle2(?string $article2): static
    {
        $this->article2 = $article2;

        return $this;
    }

    public function getArticle3(): ?string
    {
        return $this->article3;
    }

    public function setArticle3(?string $article3): static
    {
        $this->article3 = $article3;

        return $this;
    }

    public function getGenereePar(): ?User
    {
        return $this->genereePar;
    }

    public function setGenereePar(?User $genereePar): static
    {
        $this->genereePar = $genereePar;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getGenereeParNom(): ?string
    {
        return $this->genereePar?->getNomComplet();
    }

    /**
     * @return Collection<int, DemandeJouissance>
     */
    public function getDemandesJouissance(): Collection
    {
        return $this->demandesJouissance;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['api:read'])]
    public function isValide(): bool
    {
        return $this->dateExpiration >= new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->numeroDecision ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
