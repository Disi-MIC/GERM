<?php

namespace App\Entity;

use App\Entity\Enum\CategorieAgentConge;
use App\Repository\ParametresDecisionCongeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Réglages du texte légal par défaut inséré automatiquement dans chaque
 * DecisionConge générée par le RH Congé
 * (Api/DemandeDecisionController::genererEtTransmettre()) : visas des
 * décrets en vigueur, article 2, article 3 — un jeu par CategorieAgentConge
 * (fonctionnaire/non-fonctionnaire, une ligne chacun, voir
 * ParametresDecisionCongeRepository::recupererOuCreer()), puisque le texte
 * légal diffère selon la base légale (Loi 61-33 du 15/06/1961 pour les
 * fonctionnaires, Décret 74-347 du 12/04/1974 pour les non-fonctionnaires —
 * voir aussi EligibiliteDecisionCongeService). Modifiable uniquement par le
 * RH Admin (Api/ParametresDecisionCongeController) — texte qui peut changer
 * (nouveau décret, révision des articles) indépendamment du circuit
 * d'approbation. Chaque DecisionConge conserve sa propre copie de ce texte
 * telle qu'elle était au moment de sa génération (voir ses champs
 * articleX/visasDecrets) plutôt qu'une référence dynamique à ces réglages :
 * les modifier plus tard ne doit jamais changer le contenu d'une décision
 * déjà délivrée.
 */
#[ORM\Entity(repositoryClass: ParametresDecisionCongeRepository::class)]
#[ORM\Table(name: 'parametres_decision_conge')]
#[ORM\UniqueConstraint(name: 'uniq_parametres_decision_categorie', columns: ['categorie'])]
class ParametresDecisionConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: CategorieAgentConge::class)]
    #[Groups(['api:read'])]
    private CategorieAgentConge $categorie;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $visasDecrets = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $article2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $article3 = null;

    /** Liste de diffusion (une entrée par ligne) — voir le bloc AMPLIATIONS du document papier. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $ampliations = null;

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

    public function getAmpliations(): ?string
    {
        return $this->ampliations;
    }

    public function setAmpliations(?string $ampliations): static
    {
        $this->ampliations = $ampliations;

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
