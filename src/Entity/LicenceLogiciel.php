<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LicenceLogicielRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Achat/renouvellement d'une licence logicielle (Kaspersky, Office...) —
 * simple journal, comme Maintenance : chaque renouvellement est une nouvelle
 * ligne plutôt qu'une édition de la précédente, pour garder l'historique des
 * achats. `logiciel` référence la liste paramétrable ListeValeur (catégories
 * LOGICIEL_OS/LOGICIEL_ANTIVIRUS/LOGICIEL_BUREAUTIQUE) — le même produit
 * (ex. Kaspersky Plus) peut apparaître dans plusieurs licences successives.
 *
 * Exposée en lecture seule côté API : la création et la suppression passent
 * par App\Controller\Api\LicenceLogicielController.
 *
 * Pas de champ "nombre de postes couverts" stocké ici : ce nombre est compté
 * à la volée (matériels dont systemeExploitation/suiteBureautique/antivirus
 * référence ce logiciel — voir MaterielInformatiqueRepository::countParLogiciel())
 * plutôt que saisi et resynchronisé manuellement à chaque nouveau poste équipé.
 */
#[ORM\Entity(repositoryClass: LicenceLogicielRepository::class)]
#[ORM\Table(name: 'licence_logiciel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/licences-logicielles', order: ['dateExpiration' => 'DESC']),
        new Get(uriTemplate: '/licences-logicielles/{id}'),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['logiciel' => 'exact'])]
class LicenceLogiciel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ListeValeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le logiciel est obligatoire.')]
    #[Groups(['api:read', 'api:write'])]
    private ?ListeValeur $logiciel = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $numeroLicence = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?\DateTimeImmutable $dateDebut = null;

    /**
     * Durée de la licence, en mois — saisie par l'utilisateur à la place
     * d'une date d'expiration, plus naturelle pour un renouvellement annuel/
     * pluriannuel. `dateExpiration` est calculée par
     * LicenceLogicielController::create() (dateDebut + dureeMois), jamais
     * acceptée directement du client (pas de groupe api:write dessus).
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?int $dureeMois = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $dateExpiration = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $fournisseur = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read', 'api:write'])]
    private ?string $observations = null;

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

    public function getLogiciel(): ?ListeValeur
    {
        return $this->logiciel;
    }

    public function setLogiciel(?ListeValeur $logiciel): static
    {
        $this->logiciel = $logiciel;

        return $this;
    }

    public function getNumeroLicence(): ?string
    {
        return $this->numeroLicence;
    }

    public function setNumeroLicence(?string $numeroLicence): static
    {
        $this->numeroLicence = $numeroLicence;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDureeMois(): ?int
    {
        return $this->dureeMois;
    }

    public function setDureeMois(?int $dureeMois): static
    {
        $this->dureeMois = $dureeMois;

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

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

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
}
