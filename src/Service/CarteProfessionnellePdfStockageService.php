<?php

namespace App\Service;

use App\Entity\CarteProfessionnelle;

/**
 * Génère et stocke sur le SFTP le PDF + QR code d'une carte professionnelle
 * (delete-then-store : supprime l'ancien PDF/QR s'il existe avant de stocker
 * le nouveau). Partagé entre les contrôleurs Twig et API pour éviter de
 * dupliquer cette séquence à chaque point d'appel.
 */
class CarteProfessionnellePdfStockageService
{
    public function __construct(
        private readonly CarteProfessionnellePdfGenerator $pdfGenerator,
        private readonly FileStorage $fileStorage,
    ) {
    }

    public function genererEtStocker(CarteProfessionnelle $carte): void
    {
        if ($carte->getCheminFichier()) {
            $this->fileStorage->delete($carte->getCheminFichier());
        }
        if ($carte->getCheminQrCode()) {
            $this->fileStorage->delete($carte->getCheminQrCode());
        }

        $resultat = $this->pdfGenerator->generate($carte);

        $stockePdf = $this->fileStorage->storeContent($resultat['pdf'], 'carte-'.$carte->getNumero().'.pdf', 'pdf', 'carte-professionnelle');
        $carte->setCheminFichier($stockePdf['path']);
        $carte->setNomOriginal($stockePdf['originalName']);

        $stockeQr = $this->fileStorage->storeContent($resultat['qrCode'], 'qr-'.$carte->getNumero().'.png', 'png', 'qr-codes');
        $carte->setCheminQrCode($stockeQr['path']);
    }
}
