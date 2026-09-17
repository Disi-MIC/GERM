<?php

namespace App\EventListener;

use App\Entity\Enum\ActionAudit;
use App\Service\AuditLogService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Journalise chaque refus d'accès (#[IsGranted], denyAccessUnlessGranted() —
 * y compris le contrôle d'appartenance centralisé dans
 * App\Security\PersonnelOwnershipVoter, GERM-020 — ou levée directe de
 * AccessDeniedHttpException, ex. CarteProfessionnelleController::pdfTelecharger())
 * sur /api et /admin.
 *
 * Vérifie DEUX types d'exception, pas un seul : Symfony\...\Security\Core\
 * Exception\AccessDeniedException (levée par #[IsGranted] et par le contrôle
 * d'accès natif d'API Platform) est systématiquement convertie en
 * Symfony\...\HttpKernel\Exception\AccessDeniedHttpException par le
 * ExceptionListener du composant sécurité, qui s'exécute à une priorité plus
 * haute que celle-ci — vérifié en local, confirmé par le "[previous
 * exception]" du log Symfony. Un écouteur enregistré à basse priorité (donc
 * après cette conversion, comme ci-dessous) ne voit donc jamais la première
 * forme en pratique ; les deux sont couvertes ici pour ne pas dépendre d'un
 * ordre d'exécution qui pourrait changer avec la version de Symfony.
 *
 * Se contente de lire l'exception, jamais d'y toucher : la réponse 403 reste
 * entièrement produite par le gestionnaire de sécurité natif de Symfony,
 * cet écouteur ne fait qu'observer.
 */
#[AsEventListener(event: ExceptionEvent::class, priority: -10)]
class AccessDeniedAuditListener
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof AccessDeniedException && !$throwable instanceof AccessDeniedHttpException) {
            return;
        }

        $chemin = $event->getRequest()->getPathInfo();
        if (!str_starts_with($chemin, '/api/') && !str_starts_with($chemin, '/admin/')) {
            return;
        }

        $this->auditLog->enregistrer(
            ActionAudit::ACCES_REFUSE,
            resultat: 'echec',
            details: \sprintf('%s %s', $event->getRequest()->getMethod(), $chemin),
        );
    }
}
