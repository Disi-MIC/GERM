<?php

namespace App\Entity;

use App\Repository\PieceJustificativeJouissanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PieceJustificativeJouissanceRepository::class)]
#[ORM\Table(name: 'piece_justificative_jouissance')]
class PieceJustificativeJouissance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeJouissance::class, inversedBy: 'pieces')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?DemandeJouissance $demande = null;

    #[ORM\Column(length: 255)]
    private ?string $cheminFichier = null;

    #[ORM\Column(length: 255)]
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

    public function getDemande(): ?DemandeJouissance
    {
        return $this->demande;
    }

    public function setDemande(?DemandeJouissance $demande): static
    {
        $this->demande = $demande;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
