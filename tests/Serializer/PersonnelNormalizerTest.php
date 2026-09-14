<?php

namespace App\Tests\Serializer;

use ApiPlatform\Serializer\ItemNormalizer;
use App\Entity\Personnel;
use App\Entity\User;
use App\Serializer\PersonnelNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Verrouille GERM-009 : un appelant sans ROLE_RH_PERSONNEL ne doit recevoir
 * que l'identité minimale d'un Personnel qui n'est pas le sien (id, matricule,
 * nom, prenom — voir Personnel::class), jamais l'état civil, les coordonnées
 * ou la carrière. Couvre aussi le piège rencontré en vérification : un agent
 * doit toujours voir sa PROPRE fiche en entier, quel que soit son rôle.
 */
class PersonnelNormalizerTest extends TestCase
{
    private function normalizer(?User $utilisateurConnecte, bool $estRhPersonnel, ItemNormalizer $decorated): PersonnelNormalizer
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (string $role) => 'ROLE_RH_PERSONNEL' === $role && $estRhPersonnel,
        );
        $security->method('getUser')->willReturn($utilisateurConnecte);

        return new PersonnelNormalizer($decorated, $security);
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

    public function testRolePersonnelRhConserveLeGroupeSensible(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(null, estRhPersonnel: true, decorated: $decorated)
            ->normalize(new Personnel(), 'json', ['groups' => ['api:read', 'api:read:personnel-sensible']]);

        self::assertContains('api:read:personnel-sensible', $contexte->valeur['groups']);
    }

    public function testRoleNonRhRetireLeGroupeSensibleSurLaFicheDautrui(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $appelant = new User();
        $autrePersonnel = new Personnel(); // getUser() reste null : jamais l'appelant

        $this->normalizer($appelant, estRhPersonnel: false, decorated: $decorated)
            ->normalize($autrePersonnel, 'json', ['groups' => ['api:read', 'api:read:personnel-sensible']]);

        self::assertNotContains('api:read:personnel-sensible', $contexte->valeur['groups']);
        self::assertContains('api:read', $contexte->valeur['groups']);
    }

    /**
     * Le scénario rencontré en vérification : un simple agent (aucun rôle
     * métier) consultant SA PROPRE fiche via /api/me/personnel doit
     * continuer à voir l'intégralité de ses données.
     */
    public function testAgentVoitSaPropreFicheEnEntierMemeSansRoleRh(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $appelant = new User();
        $saPropreFiche = new Personnel();
        $saPropreFiche->setUser($appelant);

        $this->normalizer($appelant, estRhPersonnel: false, decorated: $decorated)
            ->normalize($saPropreFiche, 'json', ['groups' => ['api:read', 'api:read:personnel-sensible']]);

        self::assertContains('api:read:personnel-sensible', $contexte->valeur['groups']);
    }

    public function testObjetNonPersonnelNestJamaisTouche(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(null, estRhPersonnel: false, decorated: $decorated)
            ->normalize(new \stdClass(), 'json', ['groups' => ['api:read', 'api:read:personnel-sensible']]);

        self::assertContains('api:read:personnel-sensible', $contexte->valeur['groups']);
    }

    /** Un contexte sans clé 'groups' (ex. un autre format que JSON-LD) ne doit pas planter. */
    public function testAbsenceDeGroupesDansLeContexteNePlantePas(): void
    {
        [$decorated, $contexte] = $this->decoratedCapturant();

        $this->normalizer(null, estRhPersonnel: false, decorated: $decorated)
            ->normalize(new Personnel(), 'json', []);

        self::assertSame([], $contexte->valeur);
    }

    /**
     * Sans ce relais, ItemNormalizer — qui a besoin de son propre sérialiseur
     * pour normaliser récursivement les relations imbriquées — resterait
     * sans, provoquant une LogicException dès qu'un objet imbriqué doit être
     * normalisé (rencontré en vérification, voir docblock de la classe).
     */
    public function testSetSerializerEstRelayeAuDecorateur(): void
    {
        $decorated = $this->createMock(ItemNormalizer::class);
        $serializer = $this->createStub(SerializerInterface::class);
        $decorated->expects(self::once())->method('setSerializer')->with($serializer);

        $this->normalizer(null, estRhPersonnel: false, decorated: $decorated)->setSerializer($serializer);
    }
}
