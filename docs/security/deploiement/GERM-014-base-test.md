# GERM-014 — Base de test dédiée et activation des tests de sécurité

**Volet CI ✅ implémenté** (`.github/workflows/ci.yml`, GERM-017) : chaque
push/PR fait tourner un conteneur MySQL 8 jetable, dédié, avec un accès
`root` complet — `doctrine:schema:create` puis `doctrine:fixtures:load`
(désormais peuplée, voir `src/DataFixtures/AppFixtures.php`) y créent
`germ_db_test` à chaque exécution. Les 6 tests de
`tests/Security/AccessControlTest.php`, jusqu'ici systématiquement ignorés
(`markTestSkipped`, faute de base), s'exécutent donc réellement en CI à
chaque changement — c'est le point qui compte pour la sécurité : une
régression sur `^/admin` ou sur l'état d'un compte désactivé fait
désormais échouer le build, au lieu de passer inaperçue.

**Volet local — toujours bloqué, `sudo`/accès root MySQL requis.**
`germ_user` (le compte applicatif, volontairement restreint à
`GRANT ALL ON germ_db.*` — voir GERM-026) ne peut pas créer de base ni se
créer ses propres droits : `CREATE DATABASE` et `GRANT` exigent un accès
administrateur MySQL que je n'ai pas sur `germ-appdbserver`. Pour activer
ces mêmes tests en local (utile pour un développeur qui veut les rejouer
avant de pousser, sans attendre la CI) :

```sql
-- Exécuté par un administrateur disposant d'un accès root MySQL
CREATE DATABASE germ_db_test;
GRANT ALL PRIVILEGES ON germ_db_test.* TO 'germ_user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON germ_db_test.* TO 'germ_user'@'localhost';
FLUSH PRIVILEGES;
```

Puis, côté développeur :

```bash
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test --no-interaction
php bin/phpunit
```

Les 6 tests jusqu'ici ignorés doivent alors passer au vert (plus de
`markTestSkipped`).

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
