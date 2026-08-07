<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Enum\StatutDemande;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Repository\DemandeCarteProRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Demande de carte professionnelle : nouvelle carte, renouvellement, ou déclaration
 * de perte/vol. Pour un renouvellement ou une perte/vol, la carte actuelle de
 * l'agent doit être référencée. Approuver crée la CarteProfessionnelle correspondante
 * (sans modifier automatiquement le statut de l'ancienne carte, laissé à l'admin) ;
 * refuser la laisse comme trace, sans carte créée.
 *
 * Exposée en lecture + création simple côté API (frontend Angular, rôle
 * ROLE_RH_CARTE_PRO). Le traitement (approuver/refuser), qui crée la
 * CarteProfessionnelle correspondante, reste une action dédiée dans
 * src/Controller/Api/DemandeCarteProController.php — même raison que pour
 * CarteProfessionnelle : effet de bord trop spécifique pour un Put générique.
 */
#[ORM\Entity(repositoryClass: DemandeCarteProRepository::class)]
#[ORM\Table(name: 'demande_carte_pro')]
#[Assert\Callback('validateCarteRequisePourRenouvellementOuPerteVol')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/demandes-carte-pro', order: ['createdAt' => 'DESC']),
        new Get(uriTemplate: '/demandes-carte-pro/{id}'),
        new Post(uriTemplate: '/demandes-carte-pro'),
    ],
    security: "is_granted('ROLE_RH_CARTE_PRO')",
    normalizationContext: ['groups' => ['api:read']],
    denormalizationContext: ['groups' => ['api:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['personnel' => 'exact'])]
#[ApiFilter(BackedEnumFilter::class, properties: ['statut'])]
class DemandeCartePro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'demandesCartePro')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:read', 'api:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, enumType: TypeDemandeCartePro::class)]
    #[Assert\NotNull(message: 'Le type de demande est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?TypeDemandeCartePro $typeDemande = null;

    #[ORM\ManyToOne(targetEntity: CarteProfessionnelle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read', 'api:write'])]
    private ?CarteProfessionnelle $carteReference = null;

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

    #[ORM\ManyToOne(targetEntity: CarteProfessionnelle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read'])]
    private ?CarteProfessionnelle $carteCreee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $nomOriginal = null;

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

    #[Groups(['api:read'])]
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
