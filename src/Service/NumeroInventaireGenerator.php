<?php

namespace App\Service;

use App\Repository\MaterielInformatiqueRepository;

/**
 * Numéro d'inventaire du parc informatique, désormais généré par
 * l'application plutôt que saisi à la main : une saisie libre laissait la
 * porte ouverte aux doublons (rattrapés seulement à l'écriture par la
 * contrainte SQL, avec un message d'erreur brut) et aux formats
 * incohérents d'un agent à l'autre — le parc réel n'avait que deux
 * matériels avant ce correctif, déjà sur deux formats différents ("CI-1",
 * "OP-01").
 *
 * Séquence globale plutôt que par service : plus simple, et un
 * changement d'affectation ne rend jamais un numéro incohérent avec son
 * préfixe. Calculée à partir du plus grand numéro déjà utilisé sous ce
 * préfixe (colonne existante, aucune table de séquence dédiée) plutôt que
 * COUNT(*), pour ne jamais réutiliser un numéro après suppression d'un
 * matériel.
 *
 * La base n'est interrogée qu'au premier appel : les appels suivants sur
 * la même instance incrémentent en mémoire. Nécessaire pour
 * MaterielInformatiqueImporter, qui appelle suivant() une fois par ligne
 * sans jamais flush entre deux lignes (voir ImportRunner) — sans ce cache,
 * deux lignes consécutives sans numéro obtiendraient le même, la base ne
 * voyant encore aucune des deux. Sans risque de fuite entre requêtes :
 * chaque requête HTTP reconstruit ses services (pas de worker persistant).
 */
class NumeroInventaireGenerator
{
    private const PREFIXE = 'MAT-';
    private const LARGEUR = 6;

    private ?int $prochainNumero = null;

    public function __construct(private readonly MaterielInformatiqueRepository $repository)
    {
    }

    public function suivant(): string
    {
        if (null === $this->prochainNumero) {
            $this->prochainNumero = $this->repository->dernierNumeroSequentiel(self::PREFIXE) + 1;
        }

        $numero = self::PREFIXE.str_pad((string) $this->prochainNumero, self::LARGEUR, '0', \STR_PAD_LEFT);
        ++$this->prochainNumero;

        return $numero;
    }
}
