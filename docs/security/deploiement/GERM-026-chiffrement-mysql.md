# GERM-026 — Chiffrement de MySQL (transport et repos)

**Décidé le 17/09/2026** : chiffrer MySQL à la fois en transit et au repos.
Les deux volets ont des contraintes très différentes :

- **Transport** : réalisable entièrement côté application, sans `sudo`.
  Implémenté et testé (voir ci-dessous).
- **Repos** : nécessite un accès `sudo` (plugin keyring, redémarrage de
  MySQL) que je n'ai pas — procédure préparée ci-dessous pour un
  administrateur, sur le modèle de GERM-006 et GERM-023.

## 1. Transport — ✅ implémenté, testé, prêt à déployer

### Ce qui a été vérifié avant d'écrire le correctif

Depuis `germ-appdbserver` (`php bin/console dbal:run-sql ... --force-fetch`,
lecture seule) :

```
have_ssl        YES
ssl_ca          ca.pem
ssl_cert        server-cert.pem
ssl_key         server-key.pem
```

MySQL 8 génère automatiquement ces certificats au premier démarrage et sait
déjà négocier du TLS — mais rien côté application ne le demandait
(`require_secure_transport=OFF`, `Ssl_cipher` vide sur la connexion réelle de
l'app avant correction). `require_secure_transport=ON` imposerait TLS
serveur-side mais nécessite de modifier `my.cnf` et de redémarrer MySQL —
hors de portée sans `sudo` ; voir §2.

`/var/lib/mysql/` est `700 mysql:mysql` : ni `adminappdbserver` (mon accès
SSH), ni `www-data` (l'utilisateur du serveur web) ne peuvent lire `ca.pem`.
Sans lui, l'application ne peut pas vérifier cryptographiquement l'identité
du serveur MySQL (`VERIFY_CA`/`VERIFY_IDENTITY`) — seulement chiffrer le
canal sans l'authentifier (`VERIFY_SERVER_CERT=false`). **Décision validée
avec le commanditaire le 17/09/2026** : acceptable ici, puisque MySQL
n'écoute que sur `127.0.0.1` (confirmé, aucune interface externe — GERM-026
initial) : le risque qu'un serveur MySQL usurpé réponde à la place du vrai
sur cette même machine est nul en pratique. Strictement supérieur à l'état
antérieur (aucun chiffrement).

### Correctif

`config/packages/doctrine.yaml` :

```yaml
doctrine:
    dbal:
        options:
            !php/const PDO::MYSQL_ATTR_SSL_CIPHER: 'AES256-GCM-SHA384:AES128-GCM-SHA256'
            !php/const PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT: false
```

### Testé avant tout déploiement

Directement sur la base de développement tunnelée (`127.0.0.1:8889`) — le
**même** serveur MySQL que la production, seul le chemin réseau diffère —
via une commande console temporaire créée puis supprimée, jamais commitée
(`DriverManager::getConnection()` avec les mêmes `driverOptions` que ceux
ajoutés à `doctrine.yaml`) :

```
sans option SSL (état actuel)          -> Ssl_cipher vide (en clair)
ssl_cipher seul                        -> ECHEC (Cannot connect to MySQL using SSL)
verify_server_cert=false seul          -> Ssl_cipher vide (n'active pas TLS à lui seul)
ssl_cipher + verify_server_cert=false  -> Ssl_cipher = TLS_AES_256_GCM_SHA384
```

Seule la dernière combinaison active réellement TLS — c'est celle retenue.
Après `cache:clear` et ajout à `doctrine.yaml`, la connexion réelle de
l'application (pas un test ad hoc) confirme aussi
`Ssl_cipher = TLS_AES_256_GCM_SHA384`. Suite PHPUnit complète (108 tests,
6 ignorés comme avant, aucune régression) rejouée après le changement.

### À faire au déploiement

Après `git push` vers la production :

```bash
ssh germ-appdbserver "cd /var/www/germ && php bin/console dbal:run-sql \"SHOW STATUS LIKE 'Ssl_cipher'\" --force-fetch"
```

Doit afficher `TLS_AES_256_GCM_SHA384` (ou un autre cipher TLS 1.3/1.2, pas
une valeur vide). Si vide : le déploiement a effacé le cache de conteneur
sans le régénérer, ou une autre configuration DBAL prend le pas — revenir
sur le commit avant d'investiguer plus loin, l'application reste
fonctionnelle en clair entre-temps (aucune rupture de service, seule la
régression de sécurité).

**Non couvert par ce correctif** : `require_secure_transport=ON` côté
serveur (imposerait TLS à *tout* client, pas seulement à cette
application) et la vérification du certificat serveur — les deux
nécessitent un accès `sudo` (modification de `my.cnf` + redémarrage MySQL
pour le premier ; lecture de `ca.pem` hors de `/var/lib/mysql/` pour le
second).

## 2. Repos — préparé, à exécuter par un administrateur `sudo`

Contrairement au transport, aucun contournement applicatif n'existe ici :
le chiffrement InnoDB au repos (TDE) exige un plugin keyring configuré côté
serveur et le privilège `ENCRYPTION_KEY_ADMIN`/`SUPER`, qu'`germ_user` n'a
volontairement pas (GERM-026 initial : scopé à `GRANT ALL ON germ_db.*`
uniquement, aucun privilège global).

### Étape 1 — activer un plugin keyring

MySQL 8.0.24+ recommande `component_keyring_file` (l'ancien
`keyring_file` en tant que plugin est déprécié) :

```bash
sudo mysql -e "INSTALL COMPONENT 'file://component_keyring_file';"
```

Créer `/var/lib/mysql-keyring/keyring_component_config.cnf` (ou utiliser le
chemin par défaut du composant) avec un chemin de stockage **hors** du
répertoire de données MySQL normal, mais sur le même volume — la clé de
chiffrement du keyring lui-même n'est pas chiffrée par ce mécanisme ;
seule la permission Unix (`600 mysql:mysql`) la protège. Redémarrage requis :

```bash
sudo systemctl restart mysql
mysql -e "SELECT * FROM performance_schema.keyring_component_status;"
```

### Étape 2 — chiffrer les tables existantes

Activer le chiffrement par défaut pour toute nouvelle table :

```sql
SET GLOBAL default_table_encryption = ON;
```

Puis chiffrer chaque table existante de `germ_db` (liste au 17/09/2026,
à régénérer avant exécution avec
`SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='germ_db' AND TABLE_TYPE='BASE TABLE';`
au cas où une migration aurait ajouté une table entre-temps) :

```sql
ALTER TABLE audit_log ENCRYPTION='Y';
ALTER TABLE bon_essence ENCRYPTION='Y';
ALTER TABLE carte_professionnelle ENCRYPTION='Y';
ALTER TABLE changement_cartouche ENCRYPTION='Y';
ALTER TABLE composant_materiel ENCRYPTION='Y';
ALTER TABLE conge ENCRYPTION='Y';
ALTER TABLE decision_conge ENCRYPTION='Y';
ALTER TABLE delegation ENCRYPTION='Y';
ALTER TABLE demande_carte_pro ENCRYPTION='Y';
ALTER TABLE demande_decision ENCRYPTION='Y';
ALTER TABLE demande_jouissance ENCRYPTION='Y';
ALTER TABLE direction ENCRYPTION='Y';
ALTER TABLE document_administratif ENCRYPTION='Y';
ALTER TABLE historique_affectation ENCRYPTION='Y';
ALTER TABLE historique_affectation_materiel ENCRYPTION='Y';
ALTER TABLE historique_changement_materiel ENCRYPTION='Y';
ALTER TABLE historique_changement_personnel ENCRYPTION='Y';
ALTER TABLE historique_vidange ENCRYPTION='Y';
ALTER TABLE licence_logiciel ENCRYPTION='Y';
ALTER TABLE lien_google_sheet ENCRYPTION='Y';
ALTER TABLE liste_valeur ENCRYPTION='Y';
ALTER TABLE maintenance ENCRYPTION='Y';
ALTER TABLE materiel_informatique ENCRYPTION='Y';
ALTER TABLE notification ENCRYPTION='Y';
ALTER TABLE parametre_eligibilite_conge ENCRYPTION='Y';
ALTER TABLE parametres_decision_conge ENCRYPTION='Y';
ALTER TABLE personnel ENCRYPTION='Y';
ALTER TABLE piece_justificative_decision ENCRYPTION='Y';
ALTER TABLE piece_justificative_jouissance ENCRYPTION='Y';
ALTER TABLE service ENCRYPTION='Y';
ALTER TABLE ticket_escalade ENCRYPTION='Y';
ALTER TABLE ticket_incident ENCRYPTION='Y';
ALTER TABLE user ENCRYPTION='Y';
ALTER TABLE vehicule ENCRYPTION='Y';
```

`doctrine_migration_versions` n'a volontairement pas été inclus (métadonnées
de migration, aucune donnée personnelle) mais peut être ajoutée sans risque
si l'exhaustivité est préférée.

Chaque `ALTER TABLE` réécrit la table entière (le tablespace `.ibd` est
recréé chiffré) : verrou de table le temps de l'opération, à prévoir en
heure creuse. Sur les volumes actuels de `germ_db` (parc encore modeste),
l'opération devrait rester de l'ordre de la seconde par table, mais à
vérifier avec `SHOW TABLE STATUS` avant de lancer en série.

### Étape 3 — ne pas oublier redo log et binlog

Chiffrer les tables sans chiffrer le journal de rétablissement (redo log)
ni la réplication binaire (binlog) laisse fuiter les mêmes données en clair
par un autre chemin — piège classique d'un TDE fait à moitié :

```sql
SET GLOBAL innodb_redo_log_encrypt = ON;
SET GLOBAL binlog_encryption = ON;
```

`binlog_encryption` nécessite que le binlog soit activé (à vérifier au
préalable — non confirmé lors de l'évaluation du 15/09/2026 ; si le binlog
n'est pas activé, cette ligne est sans objet mais inoffensive).

### Étape 4 — vérifier

```sql
SELECT TABLE_NAME, CREATE_OPTIONS FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'germ_db' AND CREATE_OPTIONS LIKE '%ENCRYPTION="Y"%';
```

Doit lister les mêmes tables que l'étape 2. Confirmer aussi qu'une requête
applicative normale (connexion, lecture d'une fiche personnel) fonctionne
sans erreur après le redémarrage de MySQL de l'étape 1 — le redémarrage
coupe toutes les connexions en cours.

### Ce qui reste hors de portée d'une procédure applicative

Le chiffrement InnoDB ne protège que les tablespaces de tables. Le disque
système lui-même (`lsblk` du 15/09/2026 : LVM simple sur `sda3`, aucun
volume LUKS/dm-crypt) reste en clair : un accès physique au disque (ou à
une image de VM/snapshot de l'hyperviseur — voir GERM-027) contournerait le
chiffrement InnoDB en lisant les fichiers système, sauvegardes temporaires
ou fichiers d'échange non couverts par le TDE. Le chiffrer rétroactivement
suppose soit un chiffrement natif du volume LVM (réinstallation), soit un
chiffrement au niveau de l'hyperviseur — décision d'infrastructure qui
dépasse le périmètre d'une procédure MySQL et n'a pas de solution
« appliquer à chaud » : à traiter séparément, probablement à la prochaine
réinstallation ou migration de serveur.
