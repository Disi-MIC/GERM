<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\LicenceLogiciel;
use App\Repository\MaterielInformatiqueRepository;

/**
 * Décore les providers Doctrine natifs de LicenceLogiciel (Get/GetCollection)
 * pour y injecter nombrePostes avant sérialisation — champ transitoire non
 * mappé (voir l'entité) que le provider standard ne peut pas remplir tout
 * seul puisqu'il dépend d'une requête sur MaterielInformatique, pas d'une
 * colonne de licence_logiciel.
 */
final class LicenceLogicielProvider implements ProviderInterface
{
    public function __construct(
        private readonly CollectionProvider $collectionProvider,
        private readonly ItemProvider $itemProvider,
        private readonly MaterielInformatiqueRepository $materielInformatiqueRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $licences = $this->collectionProvider->provide($operation, $uriVariables, $context);
            foreach ($licences as $licence) {
                $this->hydraterNombrePostes($licence);
            }

            return $licences;
        }

        $licence = $this->itemProvider->provide($operation, $uriVariables, $context);
        if ($licence instanceof LicenceLogiciel) {
            $this->hydraterNombrePostes($licence);
        }

        return $licence;
    }

    private function hydraterNombrePostes(LicenceLogiciel $licence): void
    {
        $licence->setNombrePostes($this->materielInformatiqueRepository->countParLicence($licence));
    }
}
