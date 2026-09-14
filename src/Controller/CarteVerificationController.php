<?php

namespace App\Controller;

use App\Repository\CarteProfessionnelleRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Vérification publique d'une carte professionnelle via le QR code imprimé
 * dessus. Volontairement public (aucune authentification) et minimal :
 * n'affiche que le strict nécessaire pour confirmer l'authenticité d'une
 * carte, sans donnée sensible (ni matricule, ni contact, ni photo).
 *
 * Reste que la page révèle un nom et une fonction à qui présente un numéro
 * valide : sans limitation de débit, un tiers pouvait balayer les numéros et
 * reconstituer l'annuaire nominatif du Ministère. Le numéro étant saisi à la
 * main par le service RH (donc potentiellement séquentiel) et imprimé sur des
 * cartes déjà en circulation, la limitation par IP est ici la protection
 * applicable sans invalider les cartes existantes.
 */
class CarteVerificationController extends AbstractController
{
    public function __construct(
        #[Target('verification_carte')] private readonly RateLimiterFactoryInterface $limiteur,
    ) {
    }

    #[Route('/verif-carte/{numero}', name: 'public_carte_verification', methods: ['GET'])]
    public function verifier(string $numero, Request $request, CarteProfessionnelleRepository $carteRepository): Response
    {
        if (!$this->limiteur->create($request->getClientIp())->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Trop de vérifications successives. Merci de réessayer dans un instant.');
        }

        $carte = $carteRepository->findOneBy(['numero' => $numero]);

        return $this->render('carte_pro/verification.html.twig', [
            'carte' => $carte,
        ]);
    }
}
