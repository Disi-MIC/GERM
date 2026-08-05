<?php

namespace App\Entity;

use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Repository\DemandeCarteProRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Demande de carte professionnelle : nouvelle carte, renouvellement, ou déclaration
 * de perte/vol. Pour un renouvellement ou une perte/vol, la carte actuelle de
 * l'agent doit être référencée. Approuver crée la CarteProfessionnelle correspondante
 * (sans modifier automatiquement le statut de l'ancienne carte, laissé à l'admin) ;
 * refuser la laisse comme trace, sans carte créée.
 */
#[ORM\Entity(repositoryClass: DemandeCarteProRepository::class)]
#[ORM\Table(name: 'demande_carte_pro')]
#[Assert\Callback('validateCarteRequisePourRenouvellementOuPerteVol')]
class DemandeCartePro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'demandesCartePro')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, enumType: TypeDemandeCartePro::class)]
    #[Assert\NotNull(message: 'Le type de demande est obligatoire.')]
    private ?TypeDemandeCartePro $typeDemande = null;

    #[ORM\ManyToOne(targetEntity: CarteProfessionnelle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CarteProfessionnelle $carteReference = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(length: 20, enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::EN_ATTENTE;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateTraitement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaireTraitement = null;

    #[ORM\ManyToOne(targetEntity: CarteProfessionnelle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CarteProfessionnelle $carteCreee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomOriginal = null;

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

    public function getTypeDemande(): ?TypeDemandeCartePro
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(?TypeDemandeCartePro $typeDemande): static
    {
        $this->typeDemande = $typeDemande;

        return $this;
    }

    public function getCarteReference(): ?CarteProfessionnelle
    {
        return $this->carteReference;
    }

    public function setCarteReference(?CarteProfessionnelle $carteReference): static
    {
        $this->carteReference = $carteReference;

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

    public function getCarteCreee(): ?CarteProfessionnelle
    {
        return $this->carteCreee;
    }

    public function setCarteCreee(?CarteProfessionnelle $carteCreee): static
    {
        $this->carteCreee = $carteCreee;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isEnAttente(): bool
    {
        return StatutDemande::EN_ATTENTE === $this->statut;
    }

    public function validateCarteRequisePourRenouvellementOuPerteVol(ExecutionContextInterface $context): void
    {
        if (TypeDemandeCartePro::NOUVELLE !== $this->typeDemande && null === $this->carteReference) {
            $context->buildViolation('La carte actuelle est requise pour un renouvellement ou une déclaration de perte/vol.')
                ->atPath('carteReference')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->typeDemande?->label() ?? '', $this->personnel?->getNomComplet() ?? '');
    }
}
