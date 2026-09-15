<?php

namespace App\Tests\Serializer;

use ApiPlatform\Serializer\ItemNormalizer;
use App\Entity\MaterielInformatique;
use App\Serializer\MaterielInformatiqueNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Verrouille GERM-024 : un appelant sans ROLE_IT_STOCK ne doit jamais recevoir
 * MaterielInformatique::$fournisseur (groupe api:read:rh), y compris via une
 * réponse JSON manuelle de contrôleur ($this->json(...)) — pas seulement via
 * le pipeline natif d'API Platform, angle mort de l'ancien
 * MaterielInformatiqueContextBuilder (SerializerContextBuilderInterface) que
 * ce normalizer remplace.
 */
class MaterielInformatiqueNormalizerTest extends TestCase
{
    private function normalizer(bool $estItStock, ItemNormalizer $decorated): MaterielInformatiqueNormalizer
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (string $role) => 'ROLE_IT_STOCK' === $role && $estItStock,
        );

        return new MaterielInformatiqueNormalizer($decorated, $security);
    }

    private function decoratedCapturant(): array
    {
        $contexteRecu = new \stdClass();
        $contexteRecu->valeur = null;

        $decorated = $this->createStub(ItemNormalizer::class);
        $decorated->method('normalize')->willReturnCallback(
            static function ($data, $format, $context) use ($contexteRecu) {
                $contexteRecu->valeur = $context;

                return [];
            },
        );

        return [$decorated, $contexteRecu];
    }

    public function testRoleItStockConserveLeGroupeRh(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(estItStock: true, decorated: $decorated)
            ->normalize(new MaterielInformatique(), 'json', ['groups' => ['api:read', 'api:read:rh']]);

        self::assertContains('api:read:rh', $contexte->valeur['groups']);
    }

    public function testRoleNonItStockRetireLeGroupeRh(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(estItStock: false, decorated: $decorated)
            ->normalize(new MaterielInformatique(), 'json', ['groups' => ['api:read', 'api:read:rh']]);

        self::assertNotContains('api:read:rh', $contexte->valeur['groups']);
        self::assertContains('api:read', $contexte->valeur['groups']);
    }

    /**
     * Le scénario concret de GERM-024 : une réponse JSON manuelle de
     * contrôleur ($this->json($materiel, ..., ['groups' => GROUPES_LECTURE]))
     * ne passe jamais par un SerializerContextBuilderInterface — ce
     * normalizer est le seul filet une fois que le contexte contient déjà
     * api:read:rh en dur.
     */
    public function testGroupeRhRetireMemeQuandDemandeExplicitementParLeControleur(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(estItStock: false, decorated: $decorated)
            ->normalize(new MaterielInformatique(), 'json', ['groups' => ['api:read', 'api:read:rh']]);

        self::assertSame(['api:read'], $contexte->valeur['groups']);
    }

    public function testObjetNonMaterielNestJamaisTouche(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(estItStock: false, decorated: $decorated)
            ->normalize(new \stdClass(), 'json', ['groups' => ['api:read', 'api:read:rh']]);

        self::assertContains('api:read:rh', $contexte->valeur['groups']);
    }

    /** Un contexte sans clé 'groups' (ex. un autre format que JSON-LD) ne doit pas planter. */
    public function testAbsenceDeGroupesDansLeContexteNePlantePas(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(estItStock: false, decorated: $decorated)
            ->normalize(new MaterielInformatique(), 'json', []);

        self::assertSame([], $contexte->valeur);
    }

    /**
     * Sans ce relais, ItemNormalizer — qui a besoin de son propre sérialiseur
     * pour normaliser récursivement les relations imbriquées — resterait
     * sans, provoquant une LogicException dès qu'un objet imbriqué doit être
     * normalisé (même piège que PersonnelNormalizer, voir son test).
     */
    public function testSetSerializerEstRelayeAuDecorateur(): void
    {
        $decorated = $this->createMock(ItemNormalizer::class);
        $serializer = $this->createStub(SerializerInterface::class);
        $decorated->expects(self::once())->method('setSerializer')->with($serializer);

        $this->normalizer(estItStock: false, decorated: $decorated)->setSerializer($serializer);
    }
}
