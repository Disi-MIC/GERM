<?php

namespace App\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use App\Entity\MaterielInformatique;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;

/**
 * Retire le groupe api:read:rh (fournisseur) des lectures de MaterielInformatique
 * quand l'utilisateur courant n'a pas ROLE_IT_STOCK. L'opération Get native est
 * élargie à ROLE_IT_TICKETS (résolution d'IRI depuis TicketIncident.materiel,
 * voir l'entité) mais partage le même normalizationContext que le Stock —
 * API Platform ne permet pas deux contextes de sérialisation différents pour
 * une même opération selon le rôle appelant, d'où ce décorateur plutôt qu'une
 * simple annotation déclarative.
 */
#[AsDecorator(decorates: 'api_platform.serializer.context_builder')]
final class MaterielInformatiqueContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly Security $security,
    ) {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if ($normalization
            && MaterielInformatique::class === ($context['resource_class'] ?? null)
            && !$this->security->isGranted('ROLE_IT_STOCK')
            && isset($context['groups'])
        ) {
            $context['groups'] = array_values(array_diff((array) $context['groups'], ['api:read:rh']));
        }

        return $context;
    }
}
