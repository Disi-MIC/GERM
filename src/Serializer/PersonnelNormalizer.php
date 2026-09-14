<?php

namespace App\Serializer;

use ApiPlatform\Serializer\ItemNormalizer;
use App\Entity\Personnel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Retire le groupe api:read:personnel-sensible (état civil, coordonnées,
 * carrière — voir Personnel) des lectures de Personnel quand l'appelant n'a
 * pas ROLE_RH_PERSONNEL (GERM-009). Seuls id/matricule/nom/prenom/nomComplet
 * restent visibles à ROLE_IT_STOCK et ROLE_RH_CARTE_PRO, qui n'ont besoin que
 * d'identifier un agent dans un sélecteur — jamais de connaître sa date de
 * naissance, son adresse ou son téléphone personnel.
 *
 * Exception : un agent voit toujours sa PROPRE fiche en entier
 * ($data->getUser() === l'utilisateur courant), quel que soit son rôle —
 * c'est ce qui alimente "Mon Profil" en self-service (voir
 * MeController::personnel(), qui redemande explicitement ce groupe). Sans
 * cette exception, un simple ROLE_AGENT consultant son propre profil se
 * serait vu retirer ses propres données.
 *
 * Décore le NORMALIZER (ApiPlatform\Serializer\ItemNormalizer), pas le
 * SerializerContextBuilderInterface comme MaterielInformatiqueContextBuilder
 * pour son champ fournisseur : un ContextBuilder n'est consulté que par le
 * pipeline natif d'API Platform (GetCollection/Get), jamais par une réponse
 * JSON manuelle de contrôleur ($this->json($materiel, ...)). Vérifié
 * empiriquement : PUT /api/materiels-informatiques/{id} (réponse manuelle
 * de MaterielInformatiqueController::update()) embarque le Personnel affecté
 * en entier — le ContextBuilder existant ne l'aurait pas empêché, et
 * fournisseur souffre d'ailleurs du même angle mort (GERM-024, non traité
 * ici). Ce normalizer est invoqué dans les deux cas, puisque $this->json()
 * comme le pipeline natif passent par le même service 'serializer' — donc
 * par la même chaîne de normalizers.
 */
#[AsDecorator(decorates: 'api_platform.serializer.normalizer.item')]
final class PersonnelNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        #[AutowireDecorated] private readonly ItemNormalizer $decorated,
        private readonly Security $security,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if ($data instanceof Personnel
            && isset($context['groups'])
            && !$this->security->isGranted('ROLE_RH_PERSONNEL')
            && $data->getUser() !== $this->security->getUser()
        ) {
            $context['groups'] = array_values(array_diff((array) $context['groups'], ['api:read:personnel-sensible']));
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

    /**
     * Le composant Serializer appelle setSerializer() sur chaque normalizer
     * de la chaîne qui implémente SerializerAwareInterface — dont
     * ItemNormalizer, qui en a besoin pour normaliser récursivement les
     * relations imbriquées. Sans ce relais explicite, seule CETTE façade est
     * visible du Serializer (elle a remplacé ItemNormalizer dans la chaîne
     * enregistrée) : $decorated ne recevrait jamais son propre sérialiseur,
     * d'où "The injected serializer must be an instance of
     * NormalizerInterface" dès qu'un objet imbriqué doit être normalisé.
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
