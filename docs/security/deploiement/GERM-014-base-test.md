# GERM-014 — Base de test dédiée et activation des tests de sécurité

**Volet CI ✅ implémenté** (`.github/workflows/ci.yml`, GERM-017) : chaque
push/PR fait tourner un conteneur MySQL 8 jetable, dédié, avec un accès
`root` complet — `doctrine:schema:create` puis `doctrine:fixtures:load`
(désormais peuplée, voir `src/DataFixtures/AppFixtures.php`) y créent
`germ_db_test` à chaque exécution. Les tests de
`tests/Security/AccessControlTest.php`, jusqu'ici systématiquement ignorés
(`markTestSkipped`, faute de base), s'exécutent donc réellement en CI à
chaque changement — c'est le point qui compte pour la sécurité : une
régression sur `^/admin` ou sur l'état d'un compte désactivé fait
désormais échouer le build, au lieu de passer inaperçue.

**Volet local ✅ activé le 21/09/2026.** Il ne s'agit jamais de
`germ-appdbserver` (le serveur de production, où `germ_user` reste
volontairement restreint à `GRANT ALL ON germ_db.*` — voir GERM-026) : la
base de test locale vit sur MySQL local (MAMP, `127.0.0.1:8889` — voir
`.env.local`), et un accès root y était disponible. Procédure suivie :

```sql
-- Exécuté avec un accès root MySQL local (MAMP)
CREATE DATABASE germ_db_test;
GRANT ALL PRIVILEGES ON germ_db_test.* TO 'germ_user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON germ_db_test.* TO 'germ_user'@'localhost';
FLUSH PRIVILEGES;
```

Puis, côté développeur (`.env.test.local`, gitignoré, pointe déjà vers
cette base — le nom de base à y écrire reste `germ_db`, **pas**
`germ_db_test` : le suffixe `_test` est ajouté automatiquement par
`dbname_suffix` dans `config/packages/doctrine.yaml`, l'écrire deux fois
produit `germ_db_test_test`) :

```bash
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test --no-interaction
php bin/phpunit
```

**Piège rencontré en activant ces tests pour la première fois** : deux des
neuf tests (`testDocumentationApiRefuseUnCompteNonSuperadmin`,
`testDocumentationApiResteAccessibleAuSuperadmin`) échouaient en 401 plutôt
que de donner le code attendu. Cause : `KernelBrowser::loginUser()`
authentifie pour un seul contexte de pare-feu à la fois (paramètre par
défaut `"main"`), mais `/api/docs` est derrière le pare-feu `api` (voir
`security.yaml`) — les deux pare-feux ne partagent pas de `context:`
commun. `connecter()` prend désormais un second paramètre `$firewall`,
passé explicitement à `'api'` pour ces deux tests. Les 9 tests passent
désormais réellement (voir `tests/Security/AccessControlTest.php`). Ce bug
préexistait probablement (le fichier ne comptait que 6 tests à l'écriture
de la section CI ci-dessus, ces deux-là ont été ajoutés depuis pour
GERM-022) — non vérifié si la CI GitHub Actions les avait déjà fait
échouer avant ce correctif, faute d'accès à son historique depuis cette
session.

## Ce qui a été ajouté pour rendre ces tests exécutables

`src/DataFixtures/AppFixtures.php` était un squelette vide
(`DoctrineFixturesBundle` déjà présent en dépendance de développement,
jamais utilisé). `AccessControlTest` s'appuyait sur trois comptes
(`test.agent@germ.local`, `test.adminrh@germ.local`,
`superadmin@industriecommerce.gouv.sn`) qui n'existaient nulle part — la
fixture les crée désormais avec un mot de passe de test dédié
(`mot-de-passe-de-test-ci`, sans rapport avec un compte réel), un rôle
métier non-superadmin pour l'un, aucun rôle métier pour l'autre, et
`ROLE_SUPERADMIN` pour le troisième — exactement la matrice dont le test a
besoin.

## Étendre la matrice au-delà de GERM-013

`AccessControlTest` ne couvre aujourd'hui que la barrière `^/admin` et
l'état d'un compte. Candidats naturels pour l'étendre, une fois la base
disponible partout (CI, puis local) :

- Un scénario par rôle métier (`ROLE_RH_PERSONNEL`, `ROLE_IT_STOCK`, etc.)
  contre au moins une route de son propre périmètre (doit réussir) et une
  route hors périmètre (doit échouer) — non couvert aujourd'hui, cette
  matrice ne teste que « superadmin ou rien ».
- Un compte désactivé (`actif=false`) qui tente de se reconnecter puis
  d'accéder à une route déjà autorisée avant désactivation — verrouille la
  défense en profondeur de `User::getRoles()` (voir le commentaire dans
  `src/Entity/User.php`).
- Les 14 contrôles d'appartenance centralisés par `PersonnelOwnershipVoter`
  (GERM-020) : un agent qui tente d'accéder à la fiche d'un autre agent sur
  chacune des routes concernées doit être refusé de façon uniforme.
