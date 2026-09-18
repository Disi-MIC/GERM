# Pièce justificative de maintenance — limite de taille relevée à 30 Mo

## Contexte

La limite applicative pour la pièce justificative du formulaire Maintenance
(upload ou scan) est passée de 10 Mo (limite par défaut partagée par tous
les uploads de l'application, `FileStorage::TAILLE_MAX_OCTETS`) à 30 Mo,
spécifique à ce formulaire (`MaintenanceController::PIECE_TAILLE_MAX_OCTETS`) —
les pièces ici sont souvent des photos d'intervention ou des bons/factures
scannés en haute résolution, plus volumineuses qu'un document RH classique.
Les autres formulaires (documents administratifs, cartes pro, photos,
congés...) restent à 10 Mo, inchangés.

Vérifié en local (`symfony serve`, avec `upload_max_filesize`/`post_max_size`
relevés temporairement à 40 Mo pour l'occasion) : un fichier de 25 Mo est
accepté (`201`), un fichier de 31 Mo est rejeté avec le message attendu
(« Le fichier dépasse la taille maximale autorisée (30 Mo). »).

## Point ouvert : plafond PHP.ini du serveur de production

Cette limite applicative est **plafonnée en amont par `upload_max_filesize`
et `post_max_size` de PHP**, que Symfony ne peut pas changer à l'exécution
(voir le docblock de `FileStorage`). Si ces réglages sur `germ-appdbserver`
sont inférieurs à 30 Mo, un fichier de 15-30 Mo sera rejeté par PHP avant
même d'atteindre le code applicatif — l'utilisateur verrait alors une erreur
serveur générique plutôt que le message clair de `FileStorage`.

**Non vérifié** : je n'ai pas d'accès en lecture à la configuration PHP de
production (l'accès SSH en lecture a été refusé par le mode automatique de
la session), et une requête de sonde directe vers la production a également
été refusée (modification/écriture vers une ressource partagée). Les
uploads existants (documents RH, etc.) fonctionnant déjà jusqu'à 10 Mo,
`post_max_size`/`upload_max_filesize` sont donc **au moins** ≥ 10 Mo en
production — mais rien ne garantit qu'ils atteignent 30 Mo.

## Procédure (à exécuter par un administrateur avec accès à `germ-appdbserver`)

1. Vérifier les valeurs actuelles :
   ```bash
   php -i | grep -E "^upload_max_filesize|^post_max_size"
   # ou, si PHP-FPM avec un pool dédié :
   grep -rE "upload_max_filesize|post_max_size" /etc/php/*/fpm/pool.d/*.conf /etc/php/*/fpm/php.ini
   ```
2. Si l'une des deux valeurs est inférieure à 30 Mo (prévoir une marge, ex.
   `35M`, pour l'en-tête multipart et les autres champs du formulaire), les
   relever dans `php.ini` (ou le pool FPM dédié si l'app en a un) :
   ```ini
   upload_max_filesize = 35M
   post_max_size = 35M
   ```
3. Recharger PHP-FPM (`systemctl reload php8.x-fpm` ou équivalent) — pas de
   redémarrage Apache nécessaire si Apache proxy vers FPM.
4. Vérifier avec un upload réel de 25-30 Mo depuis le formulaire Maintenance
   (compte `ROLE_IT_STOCK` ou `ROLE_IT_RESPONSABLE`).
