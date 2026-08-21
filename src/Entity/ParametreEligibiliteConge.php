<?php

namespace App\Entity;

use App\Entity\Enum\CategorieAgentConge;
use App\Repository\ParametreEligibiliteCongeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Réglages RH Admin du calcul d'éligibilité/octroi de jours de congé — un
 * jeu de paramètres par CategorieAgentConge (fonctionnaire/non-fonctionnaire,
 * une ligne chacun — voir ParametreEligibiliteCongeRepository::recupererOuCreer()),
 * consommés par EligibiliteDecisionCongeService::calculer(), seule source de
 * vérité du calcul (jamais recalculé côté Angular). Valeurs par défaut (2
 * jours/mois, plafond 90, délai d'éligibilité 12 mois) identiques aux
 * constantes historiquement codées en dur dans ce service, pour ne rien
 * changer au comportement existant tant que le RH Admin n'a pas modifié l'un
 * des deux jeux de paramètres.
 */
#[ORM\Entity(repositoryClass: ParametreEligibiliteCongeRepository::class)]
#[ORM\Table(name: 'parametre_eligibilite_conge')]
#[ORM\UniqueConstraint(name: 'uniq_parametre_eligibilite_categorie', columns: ['categorie'])]
class ParametreEligibiliteConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: CategorieAgentConge::class)]
    #[Groups(['api:read'])]
    private CategorieAgentConge $categorie;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private int $joursParMois = 2;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private int $plafondJours = 90;

    #[ORM\Column]
    #[Groups(['api:read'])]
    private int $delaiEligibiliteMois = 12;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $misAJourPar = null;

    public function __construct(CategorieAgentConge $categorie)
    {
        $this->categorie = $categorie;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): CategorieAgentConge
    {
        return $this->categorie;
    }

    public function getJoursParMois(): int
    {
        return $this->joursParMois;
    }

    public function setJoursParMois(int $joursParMois): static
    {
        $this->joursParMois = $joursParMois;

        return $this;
    }

    public function getPlafondJours(): int
    {
        return $this->plafondJours;
    }

    public function setPlafondJours(int $plafondJours): static
    {
        $this->plafondJours = $plafondJours;

        return $this;
    }

    public function getDelaiEligibiliteMois(): int
    {
        return $this->delaiEligibiliteMois;
    }

    public function setDelaiEligibiliteMois(int $delaiEligibiliteMois): static
    {
        $this->delaiEligibiliteMois = $delaiEligibiliteMois;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getMisAJourPar(): ?User
    {
        return $this->misAJourPar;
    }

    public function setMisAJourPar(?User $misAJourPar): static
    {
        $this->misAJourPar = $misAJourPar;

        return $this;
    }

    #[Groups(['api:read'])]
    public function getMisAJourParNom(): ?string
    {
        return $this->misAJourPar?->getNomComplet();
    }
}
