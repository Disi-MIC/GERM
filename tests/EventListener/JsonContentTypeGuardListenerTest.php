<?php

namespace App\Tests\EventListener;

use App\EventListener\JsonContentTypeGuardListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Stub minimal : ce noyau n'est jamais réellement invoqué dans ces tests
 * (RequestEvent n'appelle le kernel que si un test le fait explicitement),
 * il ne sert qu'à satisfaire le type du constructeur de RequestEvent.
 */
final class NoyauFactice implements HttpKernelInterface
{
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): HttpResponse
    {
        throw new \LogicException('Ce noyau factice ne doit jamais être invoqué dans ces tests.');
    }
}

/**
 * Verrouille GERM-002 (contrôle de l'Origin sur les écritures /api) — en
 * particulier le cas qui a failli passer inaperçu : en production, le
 * frontend web et l'API sont servis depuis la MÊME origine (Apache sert le
 * SPA en statique sous /app, Symfony répond sous /api du même host), et un
 * navigateur envoie l'en-tête Origin même sur une requête même origine pour
 * les méthodes d'écriture. Une première version de ce listener traitait tout
 * Origin absent de CORS_ALLOW_ORIGIN comme suspect, sans exception pour ce
 * cas — ce qui aurait rejeté toutes les écritures du frontend web en
 * production, puisque CORS_ALLOW_ORIGIN ne couvre que localhost et l'app
 * mobile Capacitor (seuls cas réellement cross-origin).
 */
class JsonContentTypeGuardListenerTest extends TestCase
{
    private JsonContentTypeGuardListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        // Motif réellement utilisé en production, sans délimiteurs (voir
        // origineAutorisee()) : seuls localhost/127.0.0.1 (dev) et l'app
        // mobile Capacitor y figurent — jamais le domaine de production
        // lui-même, puisque le web y est servi en même origine que l'API.
        $this->listener = new JsonContentTypeGuardListener(
            '^(https?://(localhost|127\.0\.0\.1)(:[0-9]+)?|capacitor://localhost)$',
        );
        $this->kernel = new NoyauFactice();
    }

    private function evenement(string $methode, string $uri, ?string $origin, string $corps = '{}', string $contentType = 'application/json'): RequestEvent
    {
        $request = Request::create($uri, $methode, content: $corps);
        $request->headers->set('Content-Type', $contentType);
        if (null !== $origin) {
            $request->headers->set('Origin', $origin);
        }

        return new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Le scénario de production réel : le frontend web appelle l'API sur son
     * propre host (https://10.112.26.30 pour les deux), le navigateur envoie
     * Origin = cette même URL sur un DELETE sans corps, absente de
     * CORS_ALLOW_ORIGIN. Doit être accepté.
     */
    public function testOrigineIdentiqueAHoteRequeteAccepteeMemeAbsenteDeLaListe(): void
    {
        $event = $this->evenement('DELETE', 'https://10.112.26.30/api/composants-materiel/1', 'https://10.112.26.30', corps: '');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOrigineIdentiqueAHoteRequeteAccepteeAvecUnPort(): void
    {
        $event = $this->evenement('POST', 'http://localhost:4200/api/tickets-incident/1/prendre-en-charge', 'http://localhost:4200', corps: '');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }

    /** Cross-origin légitime : l'app mobile Capacitor, explicitement dans la liste. */
    public function testOrigineMobileCapacitorAcceptee(): void
    {
        $event = $this->evenement('DELETE', 'https://10.112.26.30/api/composants-materiel/1', 'capacitor://localhost', corps: '');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }

    /** Un site tiers ne peut ni forger Origin, ni le faire coïncider avec l'hôte de sa propre requête vers nous. */
    public function testOrigineTierceRefuseeSurEcritureSansCorps(): void
    {
        $event = $this->evenement('POST', 'https://10.112.26.30/api/tickets-incident/1/prendre-en-charge', 'https://evil.example.com', corps: '');

        ($this->listener)($event);

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    /**
     * Le contrôle doit être une égalité stricte à l'hôte de la requête, pas
     * un simple "contient" ou "commence par" : un attaquant qui enregistre
     * 10-112-26-30.evil.com ou 10.112.26.30.evil.com ne doit pas passer sous
     * prétexte que la chaîne contient l'hôte légitime.
     */
    #[DataProvider('origineUsurpantLeSuffixe')]
    public function testOrigineUsurpantLeSuffixeDuDomaineRefusee(string $origineForgee): void
    {
        $event = $this->evenement('DELETE', 'https://10.112.26.30/api/composants-materiel/1', $origineForgee, corps: '');

        ($this->listener)($event);

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public static function origineUsurpantLeSuffixe(): iterable
    {
        yield 'sous-domaine apparent' => ['https://10.112.26.30.evil.com'];
        yield 'préfixe' => ['https://evil.com/https://10.112.26.30'];
    }

    /** Un client sans Origin (non-navigateur) n'est pas un vecteur CSRF : repli sur le Content-Type. */
    public function testOrigineAbsenteAccepteeAvecBonContentType(): void
    {
        $event = $this->evenement('POST', 'https://10.112.26.30/api/composants-materiel', null, corps: '{"specification":"test"}');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }

    /** Une origine tierce doit être refusée même avec un Content-Type JSON valide : elle ne franchit pas la première barrière. */
    public function testOrigineTierceRefuseeMemeAvecContentTypeJson(): void
    {
        $event = $this->evenement('POST', 'https://10.112.26.30/api/composants-materiel', 'https://evil.example.com', corps: '{"specification":"test"}');

        ($this->listener)($event);

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    /** Non-régression : un Content-Type non supporté reste rejeté pour une origine légitime. */
    public function testContentTypeNonSupporteRefuseMemeAvecOrigineLegitime(): void
    {
        $event = $this->evenement('POST', 'https://10.112.26.30/api/composants-materiel', 'https://10.112.26.30', corps: 'specification=test', contentType: 'application/x-www-form-urlencoded');

        ($this->listener)($event);

        self::assertSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, $event->getResponse()?->getStatusCode());
    }

    /** Une requête GET n'est jamais interceptée par ce garde, quelle que soit l'origine. */
    public function testGetNestJamaisIntercepte(): void
    {
        $event = $this->evenement('GET', 'https://10.112.26.30/api/composants-materiel', 'https://evil.example.com', corps: '');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }

    /** Ce garde ne concerne que /api : le back-office Twig n'est pas de son ressort. */
    public function testRoutesHorsApiNonInterceptees(): void
    {
        $event = $this->evenement('POST', 'https://10.112.26.30/admin/personnel/1/delete', 'https://evil.example.com', corps: '');

        ($this->listener)($event);

        self::assertFalse($event->hasResponse());
    }
}
