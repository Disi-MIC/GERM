<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LicenceLogicielRepository;
use App\State\LicenceLogicielProvider;
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
 * Pas de champ "nombre de postes couverts" persisté : $nombrePostes est
 * transitoire (pas de #[ORM\Column]), rempli par App\State\LicenceLogicielProvider
 * pour les lectures API Platform natives et par LicenceLogicielController::create()
 * à la création — jamais saisi ni resynchronisé manuellement, toujours recompté
 * à la volée depuis MaterielInformatiqueRepository::countParLicence() (un
 * matériel pointe directement vers la ligne de licence qui le couvre — voir
 * MaterielInformatique::$systemeExploitation/$suiteBureautique/$antivirus).
 * Exposé ici (plutôt que recalculé côté client) pour qu'un client mobile
 * obtienne la même valeur sans dupliquer cette logique.
 */
#[ORM\Entity(repositoryClass: LicenceLogicielRepository::class)]
#[ORM\Table(name: 'licence_logiciel')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/licences-logicielles', order: ['dateExpiration' => 'DESC'], paginationEnabled: false, provider: LicenceLogicielProvider::class),
        new Get(uriTemplate: '/licences-logicielles/{id}', provider: LicenceLogicielProvider::class),
    ],
    security: "is_granted('ROLE_IT_STOCK')",
    normalizationContext: ['groups' => ['api:read']],
)]
// 'logiciel.categorie' alimente les 3 sélecteurs dépendants du formulaire
// matériel (OS/bureautique/antivirus, voir MaterielInformatiqueDetailComponent) :
// lister les licences disponibles pour la catégorie choisie.
#[ApiFilter(SearchFilter::class, properties: ['logiciel' => 'exact', 'logiciel.categorie' => 'exact'])]
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
    #[Assert\Positive(message: 'La durée doit être un nombre de mois positif.')]
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

    #[Groups(['api:read'])]
    private int $nombrePostes = 0;

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

    public function getNombrePostes(): int
    {
        return $this->nombrePostes;
    }

    public function setNombrePostes(int $nombrePostes): static
    {
        $this->nombrePostes = $nombrePostes;

        return $this;
    }
}
