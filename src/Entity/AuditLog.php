<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Enum\ActionAudit;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Journal d'audit applicatif (A09 — OWASP Top 10, §25-27 du cahier des
 * charges). Append-only : aucune méthode de modification n'existe une fois
 * une ligne persistée, et rien ici n'expose de route de suppression, pour
 * qu'une trace ne puisse jamais être maquillée après coup depuis
 * l'application elle-même.
 *
 * `utilisateurEmail` est un instantané au moment de l'action, distinct de la
 * relation `utilisateur` (nullable, SET NULL) : il reste lisible même si le
 * compte est supprimé depuis, et c'est le seul champ renseigné pour une
 * tentative de connexion avec un email qui ne correspond à aucun compte.
 *
 * Ne JAMAIS enregistrer ici : mot de passe, JWT, refresh token, secret, clé
 * privée (§25 du cahier des charges) — aucun champ ne s'y prête par
 * construction, mais tout futur point d'écriture doit rester vigilant sur ce
 * qu'il place dans `details`.
 *
 * Exposée en lecture seule (aucune écriture via API Platform — seul
 * AuditLogService y écrit, côté serveur, jamais depuis une requête cliente),
 * réservée au superadmin : c'est la trace de tous les autres rôles, elle ne
 * doit pas leur être lisible.
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['action', 'created_at'], name: 'IDX_AUDIT_ACTION_DATE')]
#[ORM\Index(columns: ['utilisateur_email'], name: 'IDX_AUDIT_UTILISATEUR_EMAIL')]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/audit-log', order: ['createdAt' => 'DESC'], paginationEnabled: false),
        new Get(uriTemplate: '/audit-log/{id}'),
    ],
    security: "is_granted('ROLE_SUPERADMIN')",
    normalizationContext: ['groups' => ['api:read']],
)]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, enumType: ActionAudit::class)]
    #[Groups(['api:read'])]
    private ?ActionAudit $action = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['api:read'])]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $utilisateurEmail = null;

    /** Classe métier concernée (ex. "DecisionConge", "CarteProfessionnelle"), ou null pour un événement sans entité (connexion, export). */
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api:read'])]
    private ?string $entite = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api:read'])]
    private ?int $entiteId = null;

    /** Contexte lisible par un humain (ex. "Export CSV du personnel — 139 lignes"). Jamais de secret, voir docblock de classe. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $details = null;

    #[ORM\Column(length: 45)]
    #[Groups(['api:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api:read'])]
    private ?string $userAgent = null;

    #[ORM\Column(length: 10)]
    #[Groups(['api:read'])]
    private string $resultat = 'succes';

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

    public function getAction(): ?ActionAudit
    {
        return $this->action;
    }

    public function setAction(ActionAudit $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getUtilisateurEmail(): ?string
    {
        return $this->utilisateurEmail;
    }

    public function setUtilisateurEmail(?string $utilisateurEmail): static
    {
        $this->utilisateurEmail = $utilisateurEmail;

        return $this;
    }

    public function getEntite(): ?string
    {
        return $this->entite;
    }

    public function setEntite(?string $entite): static
    {
        $this->entite = $entite;

        return $this;
    }

    public function getEntiteId(): ?int
    {
        return $this->entiteId;
    }

    public function setEntiteId(?int $entiteId): static
    {
        $this->entiteId = $entiteId;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        // Borné pour éviter qu'un client malveillant ne gonfle indéfiniment
        // cette colonne texte via un en-tête User-Agent forgé.
        $this->userAgent = null !== $userAgent ? mb_substr($userAgent, 0, 500) : null;

        return $this;
    }

    public function getResultat(): string
    {
        return $this->resultat;
    }

    public function setResultat(string $resultat): static
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
