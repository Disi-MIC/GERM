<?php

namespace App\Controller;

use App\Repository\CarteProfessionnelleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Vérification publique d'une carte professionnelle via le QR code imprimé
 * dessus. Volontairement public (aucune authentification) et minimal :
 * n'affiche que le strict nécessaire pour confirmer l'authenticité d'une
 * carte, sans donnée sensible (ni matricule, ni contact, ni photo).
 */
class CarteVerificationController extends AbstractController
{
    #[Route('/verif-carte/{numero}', name: 'public_carte_verification')]
    public function verifier(string $numero, CarteProfessionnelleRepository $carteRepository): Response
    {
        $carte = $carteRepository->findOneBy(['numero' => $numero]);

        return $this->render('carte_pro/verification.html.twig', [
            'carte' => $carte,
        ]);
    }
}
