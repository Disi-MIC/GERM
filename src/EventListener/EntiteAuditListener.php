<?php

namespace App\EventListener;

use App\Entity\CarteProfessionnelle;
use App\Entity\ComposantMateriel;
use App\Entity\Conge;
use App\Entity\DecisionConge;
use App\Entity\Delegation;
use App\Entity\DemandeCartePro;
use App\Entity\DemandeDecision;
use App\Entity\DemandeJouissance;
use App\Entity\Direction;
use App\Entity\DocumentAdministratif;
use App\Entity\Enum\ActionAudit;
use App\Entity\LicenceLogiciel;
use App\Entity\Maintenance;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Entity\TicketIncident;
use App\Entity\Vehicule;
use App\Service\AuditLogService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * GERM-018 : journalise la création et la suppression des entités métier
 * porteuses de données personnelles ou administratives (RH) ainsi que les
 * entités opérationnelles IT (tickets, maintenance, composants matériel,
 * étendues le 18/09/2026 — §12 de OWASP-GERM-AUDIT.md) — le cahier des
 * charges (§25-26) les demande explicitement, contrairement aux entités
 * purement techniques ou déjà elles-mêmes des journaux (Historique*,
 * TicketEscalade, AuditLog). `User` en est aussi exclu : son cycle de vie
 * est déjà tracé plus finement par UserController (diff des rôles à la
 * modification), un doublon ici ferait perdre ce détail dans un intitulé
 * générique.
 *
 * Passer par un event listener Doctrine plutôt que par un appel explicite
 * dans chacun des ~40 points de création/suppression répartis dans les
 * contrôleurs Admin et Api : plus sûr contre l'oubli (un futur contrôleur
 * sur une de ces entités est couvert sans rien y ajouter), et un seul
 * endroit à revoir si la liste doit changer.
 *
 * `postPersist`/`postRemove` sont déclenchés PENDANT un flush : y appeler
 * directement AuditLogService::enregistrer() (qui flush lui-même)
 * provoquerait un flush ré-entrant. Les événements sont donc mis en
 * attente ici et rejoués dans `postFlush`, une fois le flush déclencheur
 * terminé — la ré-entrance qu'entraîne alors le flush d'enregistrer() est
 * sans risque : `AuditLog` n'étant pas dans la liste surveillée,
 * `empiler()` n'y ajoute rien et postFlush s'arrête immédiatement.
 *
 * `getId()` a été observé revenant `null` dans `postRemove` (Doctrine ORM
 * 3.6 efface l'identifiant avant de notifier ce hook, contrairement à
 * `postPersist` où il vient d'être assigné) — vérifié par un test manuel
 * avant ce commit, qui a d'abord silencieusement journalisé `entite_id`
 * vide. L'identifiant est donc capturé plus tôt, dans `preRemove`, où il
 * est encore présent.
 */
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class EntiteAuditListener
{
    private const ENTITES_AUDITEES = [
        Personnel::class => 'Personnel',
        Conge::class => 'Congé',
        DecisionConge::class => 'DecisionConge',
        DemandeDecision::class => 'DemandeDecision',
        DemandeCartePro::class => 'DemandeCartePro',
        DemandeJouissance::class => 'DemandeJouissance',
        CarteProfessionnelle::class => 'CarteProfessionnelle',
        Vehicule::class => 'Vehicule',
        MaterielInformatique::class => 'MaterielInformatique',
        LicenceLogiciel::class => 'LicenceLogiciel',
        Delegation::class => 'Delegation',
        Service::class => 'Service',
        Direction::class => 'Direction',
        DocumentAdministratif::class => 'DocumentAdministratif',
        TicketIncident::class => 'TicketIncident',
        Maintenance::class => 'Maintenance',
        ComposantMateriel::class => 'ComposantMateriel',
    ];

    /** @var list<array{ActionAudit, string, ?int}> */
    private array $enAttente = [];

    /** @var array<int, ?int> spl_object_id() => id, capturé avant que postRemove ne l'efface */
    private array $idsAvantSuppression = [];

    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entite = $args->getObject();
        if (!isset(self::ENTITES_AUDITEES[$entite::class])) {
            return;
        }

        $this->idsAvantSuppression[spl_object_id($entite)] = method_exists($entite, 'getId') ? $entite->getId() : null;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->empiler($args->getObject(), ActionAudit::ENTITE_CREEE, $args->getObject()->getId());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entite = $args->getObject();
        $id = $this->idsAvantSuppression[spl_object_id($entite)] ?? null;
        unset($this->idsAvantSuppression[spl_object_id($entite)]);

        $this->empiler($entite, ActionAudit::ENTITE_SUPPRIMEE, $id);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->enAttente) {
            return;
        }

        $aTraiter = $this->enAttente;
        $this->enAttente = [];

        foreach ($aTraiter as [$action, $libelle, $id]) {
            $this->auditLogService->enregistrer($action, entite: $libelle, entiteId: $id);
        }
    }

    private function empiler(object $entite, ActionAudit $action, ?int $id): void
    {
        $libelle = self::ENTITES_AUDITEES[$entite::class] ?? null;
        if (null === $libelle) {
            return;
        }

        $this->enAttente[] = [$action, $libelle, $id];
    }
}
