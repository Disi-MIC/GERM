# Audit de sécurité OWASP — GERM

**Application** : GERM — Gestion Électronique des Ressources du Ministère
**Commanditaire** : Ministère de l'Industrie et du Commerce
**Référentiels** : OWASP Top 10 (2021), OWASP API Security Top 10 (2023), OWASP ASVS 4.0
**Date de l'audit** : 14 septembre 2026
**Commits de correction** : `c70cd6f` (lot 1), `a2bd956` (lot 2)

---

## 1. Résumé exécutif

GERM manipule des données à caractère personnel de l'ensemble des agents du
Ministère : état civil, adresses, contacts, carrière, congés, documents
administratifs, cartes professionnelles. Une compromission relève de la fuite
de données personnelles à l'échelle d'une administration centrale.

L'audit a porté sur 21 240 lignes de PHP, 13 490 lignes de TypeScript,
45 contrôleurs et 31 entités.

### Ce que l'audit a révélé

**Une vulnérabilité critique** : la révocation d'un compte ne fonctionnait pas
sur l'API. Un agent muté, licencié ou compromis restait bloqué sur le
back-office `/admin` mais conservait l'accès complet au frontend web et à
l'application mobile — c'est-à-dire là où se trouvent tous les utilisateurs
réels — et se voyait délivrer un jeton SSO valide 8 heures pour l'application
tierce MIC-GAR. Vérifié empiriquement avant correction : `POST /api/login`
répondait `200` sur un compte désactivé.

**Trois vulnérabilités élevées** : contournement de la protection CSRF sur les
écritures sans corps, injection de formule dans les cinq exports CSV, et
persistance des sessions ouvertes après désactivation d'un compte.

Toutes ont été corrigées et testées (lots 1 et 2). **Elles ne sont pas encore
déployées en production.**

### Ce que l'audit a confirmé comme solide

Le constat inverse mérite d'être dit, car il conditionne la suite : la base de
GERM est saine. Aucune injection SQL possible, aucun IDOR dans l'espace
libre-service (14 routes sur 14 vérifient l'appartenance), aucun path
traversal, aucune dépendance vulnérable en production, séparation des tâches
RH/IT réellement implémentée, protection SSRF par liste blanche. Les
corrections apportées ont consisté à fermer des angles morts, non à reprendre
une architecture défaillante.

### Ce qui reste ouvert

| Sujet | Portée |
|---|---|
| Vérification de la configuration de production | **Bloquant** — voir §10 |
| Journalisation d'audit (A09) | Inexistante. Attendue par le SOC du Ministère |
| Couverture de tests de sécurité | 13 tests de sécurité ajoutés, dont 6 inactifs faute de base de test |
| Chaîne CI/CD | Aucune. Déploiement par `git push` sans barrière qualité |
| Minimisation des données (§61-62) | Données RH complètes exposées aux rôles non-RH |

---

## 2. Architecture analysée

```
                    ┌──────────────────────────┐
   Navigateur ──────┤  Angular 21 (SPA)        │
                    │  servi en statique       │──┐
   App mobile ──────┤  par Apache              │  │  cookie de session
   (Capacitor iOS)  └──────────────────────────┘  │  SameSite=None
                                                   │
                    ┌──────────────────────────┐  │
                    │  Symfony 8 / PHP 8.5     │◄─┘
                    │  ├─ pare-feu `api`       │
                    │  ├─ pare-feu `main`      │──── /admin (Twig, superadmin)
                    │  ├─ API Platform         │
                    │  └─ Doctrine ORM         │
                    └────┬──────────┬──────────┘
                         │          │
              ┌──────────▼───┐  ┌───▼────────────┐   ┌──────────────┐
              │ MySQL        │  │ SFTP / Flysystem│   │ MIC-GAR      │
              │ 10.112.26.30 │  │ 10.112.26.31    │   │ (SSO, RS256) │
              └──────────────┘  └─────────────────┘   └──────────────┘
```

| Composant | Volumétrie |
|---|---|
| Contrôleurs | 45 (25 API · 17 Admin Twig · 3 autres) |
| Entités / Repositories | 31 / 31 |
| Ressources API Platform | 24, toutes avec une expression `security:` |
| Services métier | 12 |
| Voters | **0** |
| EventSubscribers / Listeners de sécurité | 3 (en-têtes, garde Content-Type/Origin, déconnexion API) |
| Migrations | 51 |
| Composants Angular | 87 · 28 services · 5 guards · 1 interceptor |

**Rôles** (hiérarchie déclarée dans `config/packages/security.yaml`) :

```
ROLE_AGENT  (socle de tout compte)
 ├── ROLE_RH_PERSONNEL ─┐
 ├── ROLE_RH_CONGE ─────┼── ROLE_RH_RESPONSABLE ─┐
 ├── ROLE_RH_CARTE_PRO ─┘                        │
 ├── ROLE_IT_STOCK ───┐                          ├── ROLE_SUPERADMIN
 ├── ROLE_IT_TICKETS ─┴── ROLE_IT_RESPONSABLE ───┤
 └── ROLE_RESPONSABLE_SERVICE                    │
      └── ROLE_DIRECTION_MINISTERIELLE           │
           └── ROLE_AUTORITE ────────────────────┘
```

Les domaines RH et informatique sont **volontairement disjoints** : ni l'un ni
l'autre n'hérite du second. C'est une séparation des tâches réelle, et elle est
respectée au niveau des rôles — mais contournée au niveau des données (voir
GERM-009).

---

## 3. Méthodologie et couverture

Audit en boîte blanche : lecture du code source, vérification empirique des
constats sur instance locale, tests de non-régression après chaque correction.

**Aucun test n'a été mené sur l'environnement de production.**

### Couverture réelle par domaine

La transparence sur ce qui n'a *pas* été audité vaut autant que les constats.

| Domaine | Couverture | Base |
|---|---|---|
| A01 Contrôle d'accès | **Approfondie** | Lecture intégrale des 45 contrôleurs, des 24 expressions `security:`, des 39 routes du périmètre `ROLE_AGENT` |
| A02 Cryptographie | **Partielle** | Code lu ; secrets de production **non vérifiés** |
| A03 Injection | **Approfondie** | 0 SQL natif, 0 concaténation en QueryBuilder, 44 `setParameter`, 0 `\|raw` Twig, 2 `bypassSecurityTrust` justifiés |
| A04 Conception | **Partielle** | Règles métier sensibles échantillonnées, pas exhaustives |
| A05 Mauvaise configuration | **Partielle** | Configuration du dépôt auditée ; **serveur et Apache non vérifiés** |
| A06 Dépendances | **Approfondie** | `composer audit` + `npm audit`, avec distinction prod / build |
| A07 Authentification | **Approfondie** | Chaîne complète tracée jusque dans `vendor/` |
| A08 Intégrité | **Faible** | Aucune CI/CD à auditer ; `deploy.sh` (25 lignes) non analysé en profondeur |
| A09 Journalisation | **Constat d'absence** | Aucun dispositif d'audit applicatif à auditer |
| A10 SSRF | **Approfondie** | Toutes les sorties réseau recensées |
| API1-API10 | **Approfondie** sauf API7 | Voir `API-SECURITY.md` |

**Non couvert** : tests d'intrusion dynamiques, revue de la configuration
Apache/PHP de production, sécurité physique et réseau, sauvegardes et
restauration (§59), revue exhaustive des 87 routes `/admin` autrement que par
leur contrôle d'accès.

---

## 4. OWASP Top 10 (2021)

| | Catégorie | Verdict | Constats |
|---|---|---|---|
| **A01** | Broken Access Control | ⚠️ **Corrigé partiellement** | GERM-001, GERM-008, GERM-009, GERM-013 |
| **A02** | Cryptographic Failures | ⚠️ **À confirmer** | GERM-006, GERM-012 |
| **A03** | Injection | ⚠️ **Corrigé** | GERM-003 (CSV) — aucune injection SQL ni XSS |
| **A04** | Insecure Design | ⚠️ **Corrigé partiellement** | GERM-008 |
| **A05** | Security Misconfiguration | ⚠️ **À confirmer** | GERM-005, GERM-015, GERM-016, GERM-022 |
| **A06** | Vulnerable Components | ✅ **Conforme** | 0 vulnérabilité en production |
| **A07** | Identification & Auth Failures | ⚠️ **Corrigé** | GERM-001, GERM-004, GERM-011 |
| **A08** | Software & Data Integrity | ❌ **Non conforme** | GERM-017 (aucune CI/CD) |
| **A09** | Logging & Monitoring Failures | ❌ **Non conforme** | GERM-018 (aucune journalisation d'audit) |
| **A10** | SSRF | ✅ **Conforme** | Liste blanche stricte sur la seule sortie réseau |

### A03 — Détail, car le verdict est contre-intuitif

L'unique faille d'injection trouvée ne concerne ni SQL ni le navigateur, mais
le **tableur du destinataire** : les exports CSV n'échappaient pas les valeurs
commençant par `=`, `+`, `-` ou `@`. Un champ libre de GERM devenait une
formule exécutée à l'ouverture du fichier. Le BOM UTF-8 en tête des exports
garantit précisément qu'Excel les ouvre en mode natif, donc interprète les
formules. Corrigé (GERM-003).

Pour le reste, Doctrine est utilisé correctement de bout en bout, Twig
n'utilise aucun `|raw`, et Angular ne désactive ses protections que pour
envelopper des URL `blob:` générées localement.

---

## 5. OWASP API Security Top 10 (2023)

Synthèse ; le détail figure dans `API-SECURITY.md`.

| | Catégorie | Verdict |
|---|---|---|
| **API1** | Broken Object Level Authorization | ✅ 14 routes `/api/me/*` sur 14 vérifient l'appartenance |
| **API2** | Broken Authentication | ⚠️ Corrigé (GERM-001, GERM-002, GERM-004) |
| **API3** | Broken Object Property Level Authorization | ⚠️ GERM-009 — sur-exposition en lecture |
| **API4** | Unrestricted Resource Consumption | ⚠️ Corrigé (GERM-007, GERM-010) |
| **API5** | Broken Function Level Authorization | ✅ Couverture `#[IsGranted]` intégrale |
| **API6** | Unrestricted Access to Sensitive Business Flows | ⚠️ Corrigé partiellement (GERM-008) |
| **API7** | Server Side Request Forgery | ✅ Voir A10 |
| **API8** | Security Misconfiguration | ⚠️ À confirmer (GERM-005) + GERM-022 |
| **API9** | Improper Inventory Management | ✅ Inventaire produit ; 0 route morte ou de debug |
| **API10** | Unsafe Consumption of APIs | ✅ Une seule API tierce, validée strictement |

---

## 6. OWASP ASVS 4.0 — contrôles applicables

Niveau visé : **L2** (application manipulant des données personnelles).

| Chapitre | État | Justification |
|---|---|---|
| V1 Architecture | PARTIAL | Séparation des tâches réelle, mais aucun modèle de menace formalisé avant cet audit |
| V2 Authentification | PARTIAL | Hachage `auto`, anti-brute-force sur les deux pare-feu, état du compte vérifié (après correction). Aucun MFA (§21), aucune politique de complexité |
| V3 Gestion de session | PASS | Régénération à la connexion, invalidation à la déconnexion, au changement de mot de passe et à la désactivation (après correction) |
| V4 Contrôle d'accès | PARTIAL | Contrôle par rôle complet ; contrôle au niveau objet correct mais réimplémenté à la main en 14 endroits, sans Voter ni test |
| V5 Validation & encodage | PASS | Doctrine paramétré, Twig auto-échappé, Angular non contourné, CSV neutralisé (après correction) |
| V7 Journalisation | **FAIL** | Aucune journalisation d'audit applicative |
| V8 Protection des données | PARTIAL | Données RH sur-exposées aux rôles non-RH (GERM-009) |
| V9 Communications | PARTIAL | TLS 1.2/1.3 confirmé en production ; en-têtes du SPA non vérifiés |
| V10 Code malveillant | N/A | Hors périmètre |
| V11 Logique métier | PARTIAL | Règle « dossier figé après instruction » ajoutée ; les autres règles sensibles ne sont pas testées |
| V12 Fichiers & ressources | PASS | Nom aléatoire, type MIME reniflé, hors racine web, ré-encodage des images |
| V13 API & services web | PARTIAL | Voir API3 et API4 |
| V14 Configuration | **À CONFIRMER** | Dépend de la configuration de production non vérifiée |

---

## 7. Vulnérabilités

Sévérité inspirée de CVSS. « Statut » au 14/09/2026.

### Critique

| | |
|---|---|
| **ID** | GERM-001 |
| **Titre** | La révocation d'un compte est sans effet sur l'API |
| **OWASP** | A07 / A01 · API2 · **ASVS V2.2** |
| **Sévérité** | **CRITICAL** |
| **Fichier** | `config/packages/security.yaml` (pare-feu `api`) · `src/Security/UserChecker.php:15` · `src/Entity/User.php` |
| **Description** | `user_checker: App\Security\UserChecker` n'était déclaré que sur le pare-feu `main`. Le pare-feu `api` retombait sur `InMemoryUserChecker`, qui sort immédiatement pour tout objet n'étant pas un `InMemoryUser` : le champ `actif` n'était donc **jamais** évalué sur `/api`. En complément, `User::getRoles()` ajoutait `ROLE_AGENT` inconditionnellement, satisfaisant la règle `^/api → ROLE_AGENT`. |
| **Impact** | Un agent révoqué conserve l'accès complet au web et au mobile selon ses rôles, et obtient un jeton SSO valide 8 h pour MIC-GAR. La désactivation d'un compte, seul mécanisme de révocation de l'application, était inopérante là où se trouvent tous les utilisateurs. |
| **Preuve** | Compte désactivé : `POST /api/login` → `200`, `GET /api/personnels` → `200`, jeton SSO émis. |
| **Correction** | `user_checker` déclaré sur le pare-feu `api` ; `getRoles()` renvoie `[]` si le compte est inactif (défense en profondeur). |
| **Statut** | ✅ Corrigé — `c70cd6f` |
| **Test** | `tests/Entity/UserEtatCompteTest.php::testCompteDesactiveNaPlusAucunRole` + vérification empirique (401) |

### Élevée

| ID | Titre | OWASP | Fichier | Statut |
|---|---|---|---|---|
| **GERM-002** | Contournement CSRF sur les écritures sans corps. Le garde Content-Type sortait avant tout contrôle quand le corps était vide, laissant un site tiers déclencher les routes d'action (`valider`, `rejeter`, `prendre-en-charge`) via un `<form>` vide ou un `fetch()` sans corps — requêtes « simples », donc sans préflight CORS, avec le cookie joint puisqu'il est `SameSite=None` pour l'app Capacitor. **Correction** : l'origine est vérifiée contre la liste blanche CORS existante, le Content-Type restant en défense secondaire. | A01 · API2 · ASVS V4.2 | `src/EventListener/JsonContentTypeGuardListener.php:45` | ✅ `c70cd6f` |
| **GERM-003** | Injection de formule dans les 5 exports CSV. **Correction** : `AbstractController::protegerCsv()` préfixe d'une apostrophe toute valeur commençant par `=`, `+`, `-`, `@`, tabulation ou retour chariot. | A03 · ASVS V5.3 | `src/Controller/AbstractController.php` + 5 contrôleurs | ✅ `c70cd6f` |
| **GERM-004** | La désactivation d'un compte n'invalidait pas les sessions déjà ouvertes : le user checker n'agit qu'à l'authentification. **Correction** : `User` implémente `EquatableInterface`. *Point de vigilance documenté dans le code : Symfony s'appuie alors exclusivement sur `isEqualTo()` et abandonne ses comparaisons natives — le mot de passe et l'identifiant doivent y être comparés, sous peine de supprimer silencieusement l'invalidation au changement de mot de passe.* | A07 · ASVS V3.3 | `src/Entity/User.php` | ✅ `c70cd6f` |

### Moyenne

| ID | Titre | OWASP | Statut |
|---|---|---|---|
| **GERM-005** | `APP_ENV=dev` dans le `.env` versionné, et le pare-feu `dev` pose `security: false` sur `^/_profiler` et `^/_wdt`. Si la production ne surcharge pas `APP_ENV`, le profiler expose sans authentification les requêtes SQL, les sessions et les variables d'environnement. **Deviendrait CRITICAL si confirmé.** | A05 · API8 | ⏸️ **À confirmer** |
| **GERM-006** | `QR_SECRET_KEY` versionnée dans `.env` avec une valeur d'aspect aléatoire. Si c'est la clé de production, elle figure dans l'historique Git et permet de forger des jetons d'étiquette. *Impact limité : la résolution d'un jeton exige `ROLE_IT_STOCK`.* | A02 | ⏸️ **À confirmer** |
| **GERM-007** | Annuaire nominatif énumérable : `/verif-carte/{numero}` est public par conception et révèle nom et fonction, sans limitation de débit. **Correction** : 20 requêtes/minute/IP. *Correction de fond (jeton opaque au lieu du numéro imprimé) non retenue : elle invaliderait les cartes en circulation.* | A01 · API4 | ✅ `a2bd956` |
| **GERM-008** | Pièces justificatives modifiables après instruction : l'appartenance était vérifiée, jamais l'état de la demande. Un agent pouvait remplacer une pièce après validation par le RH, faisant diverger le dossier officiel de ce qui avait été instruit, sans trace. **Correction** : dépôt réservé à l'état « en attente » (409 sinon). | A04 · API6 · §13 | ✅ `a2bd956` |
| **GERM-009** | Dossier RH complet (date de naissance, adresse, téléphone, e-mail, observations) exposé à `ROLE_IT_STOCK` et `ROLE_RH_CARTE_PRO` via `GET /api/personnels`, sans pagination. Mesure effectuée : ces rôles n'utilisent que `nomComplet`, `matricule`, `nom`, `prenom`. La séparation RH/IT voulue par la configuration est contournée au niveau des données. | A01 · API3 · §61-62 | ⏸️ **Arbitrage en attente** |
| **GERM-010** | Exports CSV sans quota ni pagination. **Correction** : 10 exports/heure/compte. *Optimisation mémoire (`toIterable()`, N+1) reportée au lot performance.* | API4 · §39 | ✅ `a2bd956` |
| **GERM-011** | Aucune contrainte de mot de passe en modification : la liste de contraintes était littéralement vide hors création, un administrateur pouvait affecter un mot de passe d'un caractère. **Correction** : contrainte de longueur appliquée aussi en édition. | A07 · ASVS V2.1 | ✅ `c70cd6f` |
| **GERM-012** | Jeton SSO sans `jti` ni `nbf`, durée 8 h, non révocable. Combiné à GERM-001 avant correction, un jeton émis pour un compte désactivé restait accepté par MIC-GAR 8 h durant. | A02 · API2 | ⏸️ **Nécessite MIC-GAR** |
| **GERM-013** | `access_control: ^/admin → ROLE_AGENT`. Aucune route n'était exposée (les 17 contrôleurs portent `ROLE_SUPERADMIN`), mais tout nouveau contrôleur déposé sans attribut l'aurait été. **Correction** : règle portée à `ROLE_SUPERADMIN`, exception explicite pour la page d'atterrissage. | A01 | ✅ `c70cd6f` |
| **GERM-014** | Couverture de tests de sécurité quasi nulle avant l'audit : aucune garantie de non-régression sur un correctif. | §54-55 | 🔄 **En cours** |

### Faible

| ID | Titre | Statut |
|---|---|---|
| **GERM-015** | `X-Frame-Options: DENY` contredisait `CSP frame-ancestors 'self'` ; les navigateurs modernes suivant la CSP, le framing same-origin restait autorisé malgré l'intention inverse. | ✅ `a2bd956` |
| **GERM-016** | En-tête `Permissions-Policy` absent. | ✅ `a2bd956` |
| **GERM-017** | Aucune chaîne CI/CD : le déploiement se fait par `git push` vers un hook `post-receive`, sans test, sans analyse de dépendances, sans barrière qualité. Une vulnérabilité critique n'empêche rien. | ❌ **Ouvert** |
| **GERM-018** | Aucune journalisation d'audit applicative : ni connexion, ni changement de rôle, ni accès refusé, ni export, ni téléchargement de document. Une compromission serait indétectable et non reconstituable. | ❌ **Ouvert** |
| **GERM-019** | `ROLE_ALLOWED_TO_SWITCH` présent dans la hiérarchie alors qu'aucun pare-feu ne déclare `switch_user` : configuration morte laissant croire à une usurpation d'identité active et non journalisée. | ✅ `a2bd956` |
| **GERM-020** | Absence de Voter : la logique « cet objet est-il à moi ? » est réécrite à la main en 14 endroits, sous 3 formes et avec 2 codes de retour différents (403 / 404). Correct aujourd'hui — vérifié une par une — mais chaque nouvelle route repose sur la vigilance du développeur. | ❌ **Dette ouverte** |
| **GERM-021** | Import CSV/XLSX sans limite de taille ni contrôle de type réel, avec évaluation des formules du classeur (`getCalculatedValue()`). Réservé au superadmin et jamais stocké : risque de déni de service auto-infligé. | ❌ **Ouvert** |
| **GERM-022** | `GET /api/docs` (documentation interactive API Platform) renvoie `401` sans authentification mais `200` pour **tout compte authentifié**, y compris un `ROLE_AGENT` sans aucun rôle métier. Les opérations restent protégées individuellement : ce n'est pas un accès aux données, mais la cartographie complète des 24 ressources, de leurs opérations et de leurs champs, offerte à un compte compromis de bas privilège. Le §34 demande de ne pas l'exposer en production. *Correction : `enable_swagger_ui: false` sous `when@prod`, ou règle `access_control` sur `^/api/docs` avant `^/api`.* | ❌ **Ouvert** |

---

## 8. Optimisation

Aucune optimisation de performance n'a été réalisée dans les lots 1 et 2 :
le §73 place la performance en priorité 17, après la sécurité, et mélanger un
refactor de requêtes à un correctif de sécurité rend la non-régression
indémontrable.

**Identifié, non traité** (voir `PERFORMANCE-AUDIT.md` à produire) :

| Sujet | Constat |
|---|---|
| Exports CSV | `findAll()` hydrate la table entière en mémoire avant l'envoi : le streaming est apparent, la consommation reste en O(n) |
| N+1 | `getService()->getDirection()` par ligne dans l'export des effectifs |
| `paginationEnabled: false` | Sur `Personnel`, `MaterielInformatique`, `Maintenance`, `LicenceLogiciel`, `ListeValeur` — choix assumé (filtrage côté client) mais qui ne tiendra pas à l'échelle |
| Collection `composants` | `fetchEager: false` déclaré pour éviter une boucle de jointures ; provoque une requête par matériel en lecture de collection |
| Bundle Angular | 1,04 Mo initial (229 ko transférés) — non analysé |

---

## 9. Tests

| | Avant l'audit | Après |
|---|---|---|
| Fichiers de test | 8 | 10 |
| Tests exécutés | 42 | **55** |
| Tests de sécurité | **0** | **13** (dont 6 inactifs) |
| Échecs | 0 | 0 |

**Commandes** :

```bash
php vendor/bin/phpunit          # 55 tests, 0 échec, 6 ignorés
composer audit                  # aucune alerte
cd frontend && npm audit --omit=dev   # 0 vulnérabilité
```

**Tests ajoutés** :
- `tests/Entity/UserEtatCompteTest.php` — 6 tests verrouillant GERM-001 et
  GERM-004, dont un test dédié au piège `EquatableInterface` décrit plus haut.
- `tests/Security/AccessControlTest.php` — 7 tests fonctionnels sur la barrière
  `/admin`, dont **6 actuellement ignorés** : le compte MySQL applicatif n'a pas le
  droit de créer `germ_db_test`. Ils s'activent seuls une fois la base créée
  (marche à suivre en commentaire dans le fichier).

**Matrice de test du §55 — état** :

| Scénario | Attendu | État |
|---|---|---|
| Compte désactivé | 401 | ✅ Vérifié |
| Session d'un compte désactivé | 401 | ✅ Vérifié |
| Origine tierce sur écriture | 403 | ✅ Vérifié |
| Pièce sur demande instruite | 409 | ✅ Vérifié |
| Agent A → données Agent B | 403 | ⚠️ Vérifié par revue de code, pas par test automatisé |
| Agent → administration | 403 | ⚠️ Test écrit, inactif |
| Upload exécutable | rejeté | ⚠️ Revue de code uniquement |
| Path traversal | rejeté | ⚠️ Revue de code uniquement |
| Jeton falsifié / expiré | 401 | ❌ Non testé |

---

## 10. Risques résiduels

**GERM n'est pas « 100 % sécurisé ». Aucune application ne l'est.** Ce qui suit
énumère ce qui demeure, en connaissance de cause.

### Bloquant — configuration de production non vérifiée

Trois éléments n'ont pas pu être contrôlés, et deux d'entre eux peuvent
invalider des conclusions de ce rapport :

1. **`APP_ENV` en production.** S'il n'est pas surchargé à `prod`, le profiler
   Symfony est accessible sans authentification (GERM-005) — ce serait la
   vulnérabilité la plus grave du système, devant GERM-001.
2. **`CORS_ALLOW_ORIGIN` en production.** Le correctif GERM-002 s'appuie sur
   cette liste blanche. Une regex ne couvrant pas l'origine web réelle
   **casserait toutes les écritures** ; une regex trop permissive annulerait
   la protection.
3. **`QR_SECRET_KEY`** (GERM-006) et les en-têtes de sécurité posés par Apache
   sur le SPA — que le listener Symfony ne couvre pas, par construction.

```bash
ssh germ-appdbserver "cd /var/www/germ && \
  grep -h CORS_ALLOW_ORIGIN .env.local .env 2>/dev/null | tail -1; \
  grep -h APP_ENV .env.local 2>/dev/null || echo 'APP_ENV NON SURCHARGÉ'; \
  grep -qc QR_SECRET_KEY .env.local 2>/dev/null && echo 'QR surchargée' || echo 'QR non surchargée'"
curl -skI https://10.112.26.30/app/ | grep -iE 'content-security|strict-transport|x-frame'
```

### Risques acceptés ou reportés

| Risque | Raison |
|---|---|
| Aucune journalisation d'audit (GERM-018) | Lot ultérieur. En l'état, une compromission serait indétectable |
| Aucune CI/CD (GERM-017) | Lot ultérieur. Rien n'empêche de déployer du code non testé |
| Aucun MFA (§21) | Non demandé à ce stade ; recommandé pour `ROLE_SUPERADMIN` |
| Aucun flux de réinitialisation de mot de passe | Tout renouvellement passe par un administrateur — moins de surface, plus de friction |
| `style-src 'unsafe-inline'` dans la CSP | Nécessaire aux attributs `style=` des templates Twig. Aucun `<script>` inline, `script-src` reste strict |
| Contrôles d'appartenance sans Voter (GERM-020) | Corrects aujourd'hui, fragiles demain |
| Cartes professionnelles en circulation | Le numéro imprimé reste la clé de vérification publique ; seule la limitation de débit protège l'annuaire |

### Ce que cet audit ne dit pas

Il ne dit rien de la sécurité réseau, de la configuration d'Apache et de PHP en
production, des sauvegardes et de leur restauration (§59), de la sécurité
physique des serveurs, ni de la résistance à un test d'intrusion dynamique.

---

## 11. Score de maturité (§68)

| Domaine | Avant | Après lots 1-2 |
|---|---|---|
| OWASP Top 10 | DEVELOPING | **DEFINED** |
| OWASP API Security | DEVELOPING | **DEFINED** |
| ASVS L2 | INITIAL | **DEVELOPING** |
| Authentification | DEVELOPING | **DEFINED** |
| Autorisation | DEFINED | **DEFINED** |
| Cryptographie | DEFINED | **DEFINED** (gestion des secrets : DEVELOPING) |
| Journalisation | **INITIAL** | **INITIAL** |
| Infrastructure | *non évalué* | *non évalué* |
| Dépendances | MANAGED | **MANAGED** |
| Tests | INITIAL | **DEVELOPING** |
| DevSecOps | **INITIAL** | **INITIAL** |
| Performance | DEVELOPING | DEVELOPING |

**Niveau global : DEVELOPING.**

Le plafond n'est plus fixé par des vulnérabilités applicatives — elles sont
traitées — mais par l'absence de journalisation d'audit et de chaîne CI/CD.
Tant que ces deux socles manquent, GERM ne peut ni prouver ce qui s'y est
passé, ni garantir que ce qui est déployé a été testé. Ce sont les deux
prochains chantiers structurants.

---

## 12. Suite recommandée

| Priorité | Chantier | Référence |
|---|---|---|
| 1 | Vérifier la configuration de production, puis déployer les lots 1 et 2 | §10 |
| 2 | Trancher GERM-009 (minimisation) et GERM-012 (jeton SSO, avec MIC-GAR) | §7 |
| 3 | Journalisation d'audit + préparation SIEM/Wazuh | §25-27, GERM-018 |
| 4 | Base de test dédiée, activation des 4 tests inactifs, extension de la matrice §55 | §54-55, GERM-014 |
| 5 | Chaîne CI/CD avec barrière de sécurité bloquante | §58, GERM-017 |
| 6 | Lot performance | §41-47 |
| 7 | `PersonnelOwnershipVoter` pour centraliser les 14 contrôles d'appartenance | GERM-020 |

---

*Document produit dans le cadre de la mission d'audit définie par
« Audit OWASP GERM » (76 sections). Il a vocation à être révisé à chaque lot de
correction. La sécurité est un processus continu, pas un état atteint.*
