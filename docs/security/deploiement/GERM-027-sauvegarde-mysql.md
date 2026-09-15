# GERM-027 — Sauvegarde de `germ_db`

**✅ Déployé et vérifié en production le 15/09/2026** — dump `mysqldump`
quotidien, compressé, chiffré, avec rotation sur 14 jours, conservé
localement sous `var/backups/` **et** copié vers le serveur SFTP de
stockage de fichiers (10.112.26.31, déjà utilisé par `FileStorage`), avec
la même rotation appliquée côté distant.

**Réserve non levée** : cette copie suppose que 10.112.26.31 ne partage pas
la même infrastructure physique (alimentation, baie) que
`germ-appdbserver` — non vérifié depuis ces deux machines, à confirmer
auprès de qui gère l'infrastructure physique du Ministère. Si les deux
serveurs sont en réalité colocalisés, ce correctif protège toujours contre
une erreur applicative ou une commande destructrice, mais pas contre un
sinistre touchant les deux machines à la fois.

## Ce qui a été vérifié avant d'écrire ce correctif

`germ_user` (compte applicatif) dispose de `GRANT ALL PRIVILEGES ON
germ_db.*` — suffisant pour `mysqldump --single-transaction` sans privilège
supplémentaire (`--no-tablespaces` évite le besoin de `PROCESS`/`SUPER`,
que ce compte n'a pas). Aucun compte ni privilège dédié à créer.

## Procédure

**1. Générer la clé de chiffrement des sauvegardes, directement sur le
serveur** (comme pour `QR_SECRET_KEY` et `FILE_ENCRYPTION_KEY` — jamais par
un autre canal) :

```bash
ssh germ-appdbserver
cd /var/www/germ
php -r "file_put_contents('.env.local', PHP_EOL.'DB_BACKUP_ENCRYPTION_KEY='.base64_encode(random_bytes(32)).PHP_EOL, FILE_APPEND);"
```

**2. Déployer le code** (`git push production main`) puis vider le cache
`prod` si le déploiement ne le fait pas déjà automatiquement.

**3. Tester une sauvegarde manuelle avant d'automatiser** :

```bash
php bin/console app:backup-database --env=prod
ls -la var/backups/
```

**4. Vérifier que le dump se déchiffre correctement** (sans toucher à la
base réelle) :

```bash
php -r '
$lines = file(".env.local");
$key = null;
foreach ($lines as $l) { if (str_starts_with($l, "DB_BACKUP_ENCRYPTION_KEY=")) { $key = base64_decode(trim(substr($l, strlen("DB_BACKUP_ENCRYPTION_KEY=")))); } }
$fichiers = glob("var/backups/germ_db_*.sql.gz.enc");
$data = file_get_contents(end($fichiers));
$nonce = substr($data, 5, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$ciphertext = substr($data, 5 + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$compressed = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
$sql = gzdecode($compressed);
echo str_contains($sql, "MySQL dump") ? "OK : dump valide (" . strlen($sql) . " octets)\n" : "ECHEC\n";
'
```

**5. Installer le crontab** (utilisateur de déploiement, aucun `sudo`
nécessaire — `crontab` gère son propre crontab sans élévation) :

```bash
crontab -l 2>/dev/null | { cat; echo "0 3 * * * cd /var/www/germ && php bin/console app:backup-database --env=prod --no-interaction >> var/log/backup.log 2>&1"; } | crontab -
crontab -l
```

Exécution quotidienne à 3h du matin (faible activité). Sortie journalisée
dans `var/log/backup.log` pour repérer un échec silencieux.

## Restauration (procédure manuelle, à tester au moins une fois)

Une sauvegarde jamais restaurée n'est pas une sauvegarde qui fonctionne.

```bash
php -r '
$lines = file(".env.local");
$key = null;
foreach ($lines as $l) { if (str_starts_with($l, "DB_BACKUP_ENCRYPTION_KEY=")) { $key = base64_decode(trim(substr($l, strlen("DB_BACKUP_ENCRYPTION_KEY=")))); } }
$data = file_get_contents($argv[1]);
$nonce = substr($data, 5, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$ciphertext = substr($data, 5 + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$compressed = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
file_put_contents("restauration.sql", gzdecode($compressed));
' var/backups/germ_db_<horodatage>.sql.gz.enc

# Sur une base de test, jamais directement sur germ_db en production :
mysql -h 127.0.0.1 -u germ_user -p germ_db_test < restauration.sql
rm restauration.sql
```

**Ne jamais tester une restauration directement sur `germ_db` en
production** — utiliser une base de test, ou au minimum confirmer
explicitement avant tout `mysql ... germ_db < restauration.sql`.

## Vérification après application

```bash
crontab -l | grep backup-database   # présent
ls -la /var/www/germ/var/backups/    # au moins un fichier .sql.gz.enc
```

La copie distante se vérifie avec les mêmes identifiants SFTP que
`FileStorage` (`SFTP_HOST`/`SFTP_USERNAME`/`SFTP_PASSWORD` dans
`.env.local`) — pas d'accès SSH direct à 10.112.26.31 nécessaire, Flysystem
suffit. Un fichier apparaît sous `db-backups/` sur ce serveur après chaque
exécution de `app:backup-database`.
