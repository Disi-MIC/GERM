<?php

namespace App\Serializer;

use App\Entity\MaterielInformatique;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Retire le groupe api:read:rh (fournisseur) des lectures de
 * MaterielInformatique quand l'appelant n'a pas ROLE_IT_STOCK (GERM-024).
 *
 * Remplace l'ancien MaterielInformatiqueContextBuilder
 * (SerializerContextBuilderInterface) : un ContextBuilder n'est consulté que
 * par le pipeline natif d'API Platform (GetCollection/Get), jamais par une
 * réponse JSON manuelle de contrôleur ($this->json($materiel, ...)), alors
 * que MaterielInformatiqueController::create()/update()/uploadPhoto()
 * répondent justement ainsi avec des groupes fixes incluant api:read:rh —
 * même angle mort que celui corrigé pour Personnel (GERM-009), qui a montré
 * que décorer le NORMALIZER (ApiPlatform\Serializer\ItemNormalizer) plutôt
 * que le ContextBuilder couvre les deux chemins, puisque $this->json() comme
 * le pipeline natif passent par le même service 'serializer'.
 *
 * Dans le code actuel, ces trois actions sont protégées au niveau contrôleur
 * par #[IsGranted('ROLE_IT_STOCK')] : un appelant sans ce rôle reçoit 403
 * avant même la sérialisation, donc fournisseur n'est pas exploitable
 * aujourd'hui par cette voie précise. Mais cette protection est
 * accidentelle — un futur point d'entrée (ex. une vue résumée ouverte à
 * ROLE_IT_TICKETS ou ROLE_IT_RESPONSABLE) hériterait silencieusement de la
 * fuite sans ce normalizer, exactement comme cela avait failli arriver pour
 * Personnel. Contrairement à PersonnelNormalizer, aucune exception "propre
 * fiche" n'est nécessaire ici : fournisseur est une donnée d'achat, pas une
 * donnée personnelle de l'agent affecté.
 *
 * $decorated est typé par intersection plutôt que par ItemNormalizer : ce
 * décorateur se chaîne avec PersonnelNormalizer sur le même id
 * ('api_platform.serializer.normalizer.item'), donc selon l'ordre choisi par
 * le conteneur, $decorated ici peut être PersonnelNormalizer plutôt que
 * l'ItemNormalizer natif — voir le docblock de PersonnelNormalizer pour le
 * TypeError rencontré avec un type-hint concret.
 */
#[AsDecorator(decorates: 'api_platform.serializer.normalizer.item')]
final class MaterielInformatiqueNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        #[AutowireDecorated] private readonly NormalizerInterface&DenormalizerInterface&SerializerAwareInterface $decorated,
        private readonly Security $security,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if ($data instanceof MaterielInformatique
            && isset($context['groups'])
            && !$this->security->isGranted('ROLE_IT_STOCK')
        ) {
            $context['groups'] = array_values(array_diff((array) $context['groups'], ['api:read:rh']));
        }

        return $this->decorated->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->decorated->setSerializer($serializer);
    }
}
