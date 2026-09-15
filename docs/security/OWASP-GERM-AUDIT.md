# Audit de sécurité OWASP — GERM

**Application** : GERM — Gestion Électronique des Ressources du Ministère
**Commanditaire** : Ministère de l'Industrie et du Commerce
**Référentiels** : OWASP Top 10 (2021), OWASP API Security Top 10 (2023), OWASP ASVS 4.0
**Date de l'audit** : 14 septembre 2026
**Commits de correction** : `c70cd6f` (lot 1), `a2bd956` (lot 2), `0707ceb` (livrables), `d13d17e` (correctif pré-déploiement GERM-002)

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
réels. Vérifié empiriquement avant correction : `POST /api/login` répondait
`200` sur un compte désactivé.

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

La vérification de la configuration de production (14/09/2026) a écarté un
risque (le profiler Symfony n'est pas exposé) et **révélé un bug dans un
correctif déjà écrit** : le contrôle d'`Origin` de GERM-002 aurait cassé
toutes les écritures du web en production, celui-ci étant servi en même
origine que l'API — un cas que la liste CORS n'a jamais eu besoin de couvrir.
Corrigé et testé avant tout déploiement (`d13d17e`). C'est l'illustration
concrète de pourquoi cette vérification était bloquante plutôt que
formelle.

| Sujet | Portée |
|---|---|
| ~~`QR_SECRET_KEY` de production = clé versionnée~~ | ✅ Corrigé (GERM-006) — rotée en production le 15/09/2026, retirée du dépôt |
| ~~Aucun en-tête de sécurité sur le SPA~~ | ✅ Corrigé (GERM-023) — appliqué et vérifié en production le 15/09/2026 par un administrateur avec `sudo` |
| Journalisation d'audit (A09) | Socle minimal déployé — couverture partielle (§26), voir GERM-018 |
| Couverture de tests de sécurité | 24 tests de sécurité ajoutés, dont 6 inactifs faute de base de test |
| Chaîne CI/CD | Aucune. Déploiement par `git push` sans barrière qualité |
| Minimisation des données (§61-62) | ✅ Corrigée (GERM-009, GERM-024) |
| Chiffrement au repos des fichiers stockés | ✅ Corrigé (GERM-025), non rétroactif sur les fichiers déjà stockés |

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
              ┌──────────▼───┐  ┌───▼─────────────┐
              │ MySQL        │  │ SFTP / Flysystem │
              │ 10.112.26.30 │  │ 10.112.26.31     │
              └──────────────┘  └──────────────────┘
```

Aucune intégration sortante vers une application tierce : l'intégration SSO
prévue vers MIC-GAR a été abandonnée avant sa mise en service (décision du
15/09/2026, GERM-012 sans objet).

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
l'autre n'hérite du second. C'est une séparation des tâches réelle, respectée
au niveau des rôles — et désormais au niveau des données pour Personnel
(GERM-009, corrigé) et pour `MaterielInformatique::$fournisseur` (GERM-024,
corrigé selon le même principe).

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
| A02 Cryptographie | **Approfondie** | Code lu ; `QR_SECRET_KEY` de production vérifiée — non surchargée au 14/09/2026 (GERM-006), **rotée et retirée du dépôt le 15/09/2026** |
| A03 Injection | **Approfondie** | 0 SQL natif, 0 concaténation en QueryBuilder, 44 `setParameter`, 0 `\|raw` Twig, 2 `bypassSecurityTrust` justifiés |
| A04 Conception | **Partielle** | Règles métier sensibles échantillonnées, pas exhaustives |
| A05 Mauvaise configuration | **Approfondie** | `APP_ENV`, CORS et en-têtes vérifiés en production, y compris sur le SPA depuis sa correction (GERM-023). Reste non vérifié : configuration Apache/PHP au-delà des en-têtes |
| A06 Dépendances | **Approfondie** | `composer audit` + `npm audit`, avec distinction prod / build |
| A07 Authentification | **Approfondie** | Chaîne complète tracée jusque dans `vendor/` |
| A08 Intégrité | **Faible** | Aucune CI/CD à auditer ; `deploy.sh` (25 lignes) non analysé en profondeur |
| A09 Journalisation | **Approfondie** | Socle minimal implémenté et vérifié en conditions réelles (production incluse) |
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
| **A01** | Broken Access Control | ⚠️ **Corrigé partiellement** | GERM-001, GERM-008, GERM-009 (résolu), GERM-013, GERM-024 (résolu) |
| **A02** | Cryptographic Failures | ⚠️ **Corrigé partiellement** | GERM-006, GERM-025 (corrigés) ; GERM-026 (MySQL, ouvert) |
| **A03** | Injection | ⚠️ **Corrigé** | GERM-003 (CSV) — aucune injection SQL ni XSS |
| **A04** | Insecure Design | ⚠️ **Corrigé partiellement** | GERM-008 |
| **A05** | Security Misconfiguration | ⚠️ **Corrigé partiellement** | GERM-005 (résolu), GERM-015, GERM-016, GERM-023 (résolu), GERM-022 (ouvert) |
| **A06** | Vulnerable Components | ✅ **Conforme** | 0 vulnérabilité en production |
| **A07** | Identification & Auth Failures | ⚠️ **Corrigé** | GERM-001, GERM-004, GERM-011 |
| **A08** | Software & Data Integrity | ❌ **Non conforme** | GERM-017 (aucune CI/CD) |
| **A09** | Logging & Monitoring Failures | ⚠️ **Corrigé partiellement** | GERM-018 (socle minimal déployé — `47561dd`) |
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
| **API3** | Broken Object Property Level Authorization | ✅ GERM-009 corrigé · ✅ GERM-024 corrigé |
| **API4** | Unrestricted Resource Consumption | ⚠️ Corrigé (GERM-007, GERM-010) |
| **API5** | Broken Function Level Authorization | ✅ Couverture `#[IsGranted]` intégrale |
| **API6** | Unrestricted Access to Sensitive Business Flows | ⚠️ Corrigé partiellement (GERM-008) |
| **API7** | Server Side Request Forgery | ✅ Voir A10 |
| **API8** | Security Misconfiguration | ⚠️ Corrigé partiellement — GERM-005 écarté, GERM-023 corrigé, GERM-022 ouvert |
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
| V7 Journalisation | PARTIAL | Socle minimal déployé, couverture incomplète (§26) |
| V8 Protection des données | PARTIAL | GERM-009 et GERM-024 corrigés (champs sensibles filtrés au niveau du normalizer, y compris sur les réponses manuelles de contrôleur) ; aucune revue exhaustive champ par champ des 31 entités n'a été menée au-delà des cas déjà signalés |
| V9 Communications | PASS | TLS 1.2/1.3 confirmé ; en-têtes de sécurité présents sur le SPA depuis GERM-023 (corrigé et vérifié en production) |
| V10 Code malveillant | N/A | Hors périmètre |
| V11 Logique métier | PARTIAL | Règle « dossier figé après instruction » ajoutée ; les autres règles sensibles ne sont pas testées |
| V12 Fichiers & ressources | PASS | Nom aléatoire, type MIME reniflé, hors racine web, ré-encodage des images |
| V13 API & services web | PARTIAL | Voir API3 et API4 |
| V14 Configuration | PASS | `APP_ENV`, CORS, en-têtes du SPA (GERM-023) et gestion des secrets (GERM-006) vérifiés conformes |

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
| **Impact** | Un agent révoqué conserve l'accès complet au web et au mobile selon ses rôles. La désactivation d'un compte, seul mécanisme de révocation de l'application, était inopérante là où se trouvent tous les utilisateurs. |
| **Preuve** | Compte désactivé : `POST /api/login` → `200`, `GET /api/personnels` → `200`. |
| **Correction** | `user_checker` déclaré sur le pare-feu `api` ; `getRoles()` renvoie `[]` si le compte est inactif (défense en profondeur). |
| **Statut** | ✅ Corrigé — `c70cd6f` |
| **Test** | `tests/Entity/UserEtatCompteTest.php::testCompteDesactiveNaPlusAucunRole` + vérification empirique (401) |

### Élevée

| ID | Titre | OWASP | Fichier | Statut |
|---|---|---|---|---|
| **GERM-002** | Contournement CSRF sur les écritures sans corps. Le garde Content-Type sortait avant tout contrôle quand le corps était vide, laissant un site tiers déclencher les routes d'action (`valider`, `rejeter`, `prendre-en-charge`) via un `<form>` vide ou un `fetch()` sans corps — requêtes « simples », donc sans préflight CORS, avec le cookie joint puisqu'il est `SameSite=None` pour l'app Capacitor. **Correction** : l'origine est vérifiée contre la liste blanche CORS existante, le Content-Type restant en défense secondaire. *Correctif initial (`c70cd6f`) incomplet : la vérification de production a révélé que web et API sont servis en même origine, cas que la liste CORS ne couvre pas — corrigé avant tout déploiement par l'ajout d'une exception « même origine » (`d13d17e`), verrouillée par 11 tests dédiés.* | A01 · API2 · ASVS V4.2 | `src/EventListener/JsonContentTypeGuardListener.php` | ✅ `c70cd6f` + `d13d17e` |
| **GERM-003** | Injection de formule dans les 5 exports CSV. **Correction** : `AbstractController::protegerCsv()` préfixe d'une apostrophe toute valeur commençant par `=`, `+`, `-`, `@`, tabulation ou retour chariot. | A03 · ASVS V5.3 | `src/Controller/AbstractController.php` + 5 contrôleurs | ✅ `c70cd6f` |
| **GERM-004** | La désactivation d'un compte n'invalidait pas les sessions déjà ouvertes : le user checker n'agit qu'à l'authentification. **Correction** : `User` implémente `EquatableInterface`. *Point de vigilance documenté dans le code : Symfony s'appuie alors exclusivement sur `isEqualTo()` et abandonne ses comparaisons natives — le mot de passe et l'identifiant doivent y être comparés, sous peine de supprimer silencieusement l'invalidation au changement de mot de passe.* | A07 · ASVS V3.3 | `src/Entity/User.php` | ✅ `c70cd6f` |

### Moyenne

| ID | Titre | OWASP | Statut |
|---|---|---|---|
| **GERM-005** | `APP_ENV=dev` dans le `.env` versionné, et le pare-feu `dev` pose `security: false` sur `^/_profiler` et `^/_wdt`. **Vérifié le 14/09/2026** : la production surcharge bien `APP_ENV=prod`, et `WebProfilerBundle` est de toute façon réservé à `dev`/`test` dans `config/bundles.php` — le profiler ne peut pas être chargé, quelle que soit la config du pare-feu `dev`. Aucune action requise. | A05 · API8 | ✅ **Écarté** |
| **GERM-006** | `QR_SECRET_KEY` était **confirmée non surchargée en production** : la clé versionnée dans `.env` (et son historique Git) était la clé réellement utilisée pour chiffrer les jetons d'étiquette du parc informatique. Quiconque avait accès en lecture au dépôt pouvait forger un jeton valide. *Impact contenu : la résolution d'un jeton exige quand même `ROLE_IT_STOCK` — l'attaquant devait cumuler l'accès au dépôt et un compte IT.* **Correction** : nouvelle clé générée directement sur le serveur de production (jamais transitée par le dépôt ni par un canal de discussion), déposée dans `.env.local` ; `QR_SECRET_KEY` retirée de `.env` versionné (vide, en échec explicite plutôt qu'en silence si absente). Vérifié par un cycle chiffrement/déchiffrement réel avec la nouvelle clé, sans compte utilisateur réel impliqué. **Impact opérationnel assumé, confirmé avec le service informatique avant exécution** : toutes les étiquettes déjà imprimées cessent de fonctionner et doivent être réimprimées. | A02 | ✅ **Corrigé** — `f4f2129` |
| **GERM-007** | Annuaire nominatif énumérable : `/verif-carte/{numero}` est public par conception et révèle nom et fonction, sans limitation de débit. **Correction** : 20 requêtes/minute/IP. *Correction de fond (jeton opaque au lieu du numéro imprimé) non retenue : elle invaliderait les cartes en circulation.* | A01 · API4 | ✅ `a2bd956` |
| **GERM-008** | Pièces justificatives modifiables après instruction : l'appartenance était vérifiée, jamais l'état de la demande. Un agent pouvait remplacer une pièce après validation par le RH, faisant diverger le dossier officiel de ce qui avait été instruit, sans trace. **Correction** : dépôt réservé à l'état « en attente » (409 sinon). | A04 · API6 · §13 | ✅ `a2bd956` |
| **GERM-009** | Dossier RH complet (date de naissance, adresse, téléphone, e-mail, observations, service, carrière) exposé à `ROLE_IT_STOCK` et `ROLE_RH_CARTE_PRO` — pas seulement via `GET /api/personnels` direct, mais aussi **embarqué dans toute réponse référençant un agent** (ex. `GET /api/materiels-informatiques/{id}.affecteA`), y compris les réponses manuelles d'écriture (`PUT`), un vecteur découvert pendant la correction. Mesuré : ces rôles n'utilisent que `nomComplet`, `matricule`, `nom`, `prenom`, `id`. **Correction** : nouveau groupe `api:read:personnel-sensible`, retiré par `App\Serializer\PersonnelNormalizer` (décorateur du normalizer, pas d'un `SerializerContextBuilderInterface` — seul le premier couvre aussi les réponses manuelles) pour tout appelant sans `ROLE_RH_PERSONNEL`, sauf lorsque l'agent consulte sa propre fiche (self-service "Mon Profil", vérifié non régressé). | A01 · API3 · §61-62 | ✅ **Corrigé** — `1e8bd47` |
| **GERM-024** | `MaterielInformatique::$fournisseur` (groupe `api:read:rh`) était filtré par `MaterielInformatiqueContextBuilder`, un `SerializerContextBuilderInterface` — comme pour GERM-009, ce mécanisme ne couvre que le pipeline natif d'API Platform, jamais les réponses JSON manuelles (`create()`/`update()`/`uploadPhoto()` de `MaterielInformatiqueController`, qui répondent avec des groupes fixes incluant `api:read:rh`). **Vérifié : non exploitable aujourd'hui** — ces trois actions sont protégées par `#[IsGranted('ROLE_IT_STOCK')]` au niveau du contrôleur, qui bloque tout autre appelant avant la sérialisation (confirmé : `PUT` par un compte `ROLE_IT_TICKETS` seul renvoie 403). Le risque est structurel, pas actif : un futur point d'entrée réutilisant ces groupes avec un rôle plus large aurait hérité de la fuite en silence, exactement comme cela a été observé pour Personnel. **Correction** : `App\Serializer\MaterielInformatiqueNormalizer`, même principe que `PersonnelNormalizer` (décorateur du normalizer, chaîné à celui-ci sur `api_platform.serializer.normalizer.item`). *Bug rencontré en vérification et corrigé dans le même correctif* : les deux décorateurs typaient `$decorated` par la classe concrète `ItemNormalizer`, ce qui a cassé toutes les connexions (`TypeError`) dès que l'un des deux a reçu l'autre comme dépendance injectée au lieu de l'`ItemNormalizer` natif — remplacé par un type par intersection (`NormalizerInterface&DenormalizerInterface&SerializerAwareInterface`). `MaterielInformatiqueContextBuilder`, devenu redondant, a été supprimé. | A01 · API3 | ✅ **Corrigé** — `a17dd36` |
| **GERM-010** | Exports CSV sans quota ni pagination. **Correction** : 10 exports/heure/compte. *Optimisation mémoire (`toIterable()`, N+1) reportée au lot performance.* | API4 · §39 | ✅ `a2bd956` |
| **GERM-011** | Aucune contrainte de mot de passe en modification : la liste de contraintes était littéralement vide hors création, un administrateur pouvait affecter un mot de passe d'un caractère. **Correction** : contrainte de longueur appliquée aussi en édition. | A07 · ASVS V2.1 | ✅ `c70cd6f` |
| **GERM-012** | *Retiré — sans objet.* Concernait le jeton SSO émis pour l'intégration MIC-GAR (absence de `jti`/`nbf`, durée 8 h non révocable). **Décision du 15/09/2026 : ne pas intégrer MIC-GAR pour le moment.** L'émission du jeton a été entièrement supprimée (`SsoTokenService`, dépendance `firebase/php-jwt`, clés `config/jwt/`) plutôt que laissée en l'état — voir commits `be8afe8`/`ba8175e`. | A02 · API2 | ➖ **Sans objet** |
| **GERM-013** | `access_control: ^/admin → ROLE_AGENT`. Aucune route n'était exposée (les 17 contrôleurs portent `ROLE_SUPERADMIN`), mais tout nouveau contrôleur déposé sans attribut l'aurait été. **Correction** : règle portée à `ROLE_SUPERADMIN`, exception explicite pour la page d'atterrissage. | A01 | ✅ `c70cd6f` |
| **GERM-014** | Couverture de tests de sécurité quasi nulle avant l'audit : aucune garantie de non-régression sur un correctif. | §54-55 | 🔄 **En cours** |
| **GERM-025** | Aucun chiffrement au repos des fichiers stockés (photos, pièces justificatives, cartes professionnelles) : SFTP protège le transport, pas le contenu une fois posé sur le disque distant — un accès direct à ce disque (hors périmètre du modèle de menaces, §7 de `THREAT-MODEL.md`, mais en défense en profondeur) exposait ces documents en clair. **Correction** : `App\Service\FileEncryptionService`, libsodium `secretbox` (même primitive que `QrTokenService`, clé dédiée `FILE_ENCRYPTION_KEY`), branché dans `FileStorage::store()/storeContent()/readStream()`. **Portée non rétroactive, assumée** : les fichiers déjà stockés avant ce correctif restent en clair jusqu'à leur remplacement — un format enveloppé versionné (en-tête `GRM1`) permet à `readStream()` de continuer à les servir sans erreur, plutôt que de les rendre illisibles. Vérifié en direct : nouveau fichier chiffré sur le disque (`GRM1...`), servi déchiffré via l'API ; fichier historique (sans en-tête) toujours lisible. | A02 · ASVS V8 | ✅ **Corrigé** — `f162578` |
| **GERM-026** | **Aucun chiffrement des données MySQL, ni en transit ni au repos.** Évalué le 15/09/2026 depuis le serveur (`SHOW VARIABLES`) : `require_secure_transport=OFF`, et la connexion réelle de l'application à MySQL est en clair (`Ssl_cipher` vide sur la session active) — risque réseau nul puisque MySQL n'écoute que sur `127.0.0.1` (confirmé, aucune interface externe), mais non chiffré techniquement. Au repos : `default_table_encryption=OFF`, `innodb_redo_log_encrypt=OFF`, `binlog_encryption=OFF` — et le disque système lui-même n'est pas chiffré (`lsblk` : LVM simple sur `sda3`, aucun volume LUKS/dm-crypt détecté). L'intégralité de `germ_db` (état civil, mots de passe hachés, tout le reste) repose en clair sur disque. *Positif, vérifié dans la foulée* : `germ_user@localhost` est restreint aux connexions locales et scopé à `GRANT ALL PRIVILEGES ON germ_db.*` uniquement (pas de privilège global — il n'a même pas le droit de lire `mysql.user`). **Non corrigé** : activer le chiffrement nécessite une décision sur la gestion des clés et un test de compatibilité/performance, hors périmètre d'une simple évaluation. | A02 · ASVS V8 | ❌ **Ouvert, évalué** |
| **GERM-027** | **Aucune preuve d'un processus de sauvegarde de la base de données.** Recherché le 15/09/2026 : aucun crontab pour l'utilisateur de déploiement, aucune entrée `/etc/cron.d/`, aucun timer systemd, aucun script ni fichier `.sql`/`.sql.gz` trouvé sur le disque. `/var/backups` ne contient que les sauvegardes système standard d'Ubuntu (dpkg/apt), rien lié à GERM. **Limite de cette vérification** : pas d'accès aux crontabs d'autres utilisateurs (`/var/spool/cron/crontabs/`, refusé sans `sudo`) ni à un éventuel mécanisme de snapshot au niveau de l'hyperviseur, invisible depuis l'intérieur de cette machine — absence constatée, non prouvée à 100 %. Historiquement hors périmètre de cet audit (§59 du cahier des charges), mais la question se pose directement au vu du resserrement vers « les données au repos ». **Si confirmée, c'est un risque de perte de données totale, au-delà de la seule confidentialité.** | Hors OWASP (continuité) | ❌ **Ouvert, à confirmer d'urgence** |

### Faible

| ID | Titre | Statut |
|---|---|---|
| **GERM-015** | `X-Frame-Options: DENY` contredisait `CSP frame-ancestors 'self'` ; les navigateurs modernes suivant la CSP, le framing same-origin restait autorisé malgré l'intention inverse. | ✅ `a2bd956` |
| **GERM-016** | En-tête `Permissions-Policy` absent. | ✅ `a2bd956` |
| **GERM-017** | Aucune chaîne CI/CD : le déploiement se fait par `git push` vers un hook `post-receive`, sans test, sans analyse de dépendances, sans barrière qualité. Une vulnérabilité critique n'empêche rien. | ❌ **Ouvert** |
| **GERM-018** | Aucune journalisation d'audit applicative : ni connexion, ni changement de rôle, ni accès refusé, ni export, ni téléchargement de document. Une compromission serait indétectable et non reconstituable. **Correction (socle minimal, `47561dd`, déployé et vérifié en production)** : `AuditLog` (append-only) + `AuditLogService`, branché sur les connexions/échecs/déconnexions (tous pare-feu), les accès refusés, les 5 exports CSV, la validation/le rejet de décision, la validation de carte, et la création/modification de compte avec diff des rôles. Ne couvre pas encore la liste complète du §26 (téléchargement de document, création/suppression sur chaque entité RH) — extensible, pas exhaustif. | ⚠️ **Corrigé partiellement** — `47561dd` |
| **GERM-019** | `ROLE_ALLOWED_TO_SWITCH` présent dans la hiérarchie alors qu'aucun pare-feu ne déclare `switch_user` : configuration morte laissant croire à une usurpation d'identité active et non journalisée. | ✅ `a2bd956` |
| **GERM-020** | Absence de Voter : la logique « cet objet est-il à moi ? » est réécrite à la main en 14 endroits, sous 3 formes et avec 2 codes de retour différents (403 / 404). Correct aujourd'hui — vérifié une par une — mais chaque nouvelle route repose sur la vigilance du développeur. | ❌ **Dette ouverte** |
| **GERM-021** | Import CSV/XLSX sans limite de taille ni contrôle de type réel, avec évaluation des formules du classeur (`getCalculatedValue()`). Réservé au superadmin et jamais stocké : risque de déni de service auto-infligé. | ❌ **Ouvert** |
| **GERM-023** | Le SPA Angular ne portait aucun en-tête de sécurité en production. `curl -I https://10.112.26.30/app/` (14/09/2026) : ni CSP, ni `X-Frame-Options`, ni HSTS, ni `X-Content-Type-Options` — seulement `Content-Type`. `SecurityHeadersListener` ne peut structurellement pas l'atteindre : Apache sert ce fichier en statique sans jamais passer par `index.php` (confirmé — les mêmes en-têtes sont bien présents sur `/admin/login`, servi par Symfony). **Correction** : `mod_headers` activé et un bloc `<Directory /var/www/germ/public/app>` ajouté au vhost, scopé au seul répertoire statique du SPA pour ne jamais entrer en conflit avec les en-têtes déjà posés par Symfony sur `/api` et `/admin` — appliquée par un administrateur avec `sudo` le 15/09/2026 (procédure : `docs/security/deploiement/GERM-023-headers-apache.md`). **Vérifié en production** : les 6 en-têtes (CSP, `X-Frame-Options`, HSTS, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`) sont bien présents sur `/app/`, sans régression sur `/api` (401) ni `/admin`. | A05 · API8 | ✅ **Corrigé** |
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
| Fichiers de test | 8 | 11 |
| Tests exécutés | 42 | **66** |
| Tests de sécurité | **0** | **24** (dont 6 inactifs) |
| Échecs | 0 | 0 |

**Commandes** :

```bash
php vendor/bin/phpunit          # 66 tests, 0 échec, 6 ignorés
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
- `tests/EventListener/JsonContentTypeGuardListenerTest.php` — 11 tests
  verrouillant GERM-002, ajoutés après que la vérification de production a
  révélé le bug même-origine décrit en §10 : le scénario qui a failli casser
  la production (`Origin` = hôte de la requête) est désormais un test, pas
  seulement une vérification manuelle.

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

### Configuration de production — vérifiée le 14/09/2026

| Point | Résultat | Verdict |
|---|---|---|
| `APP_ENV` | `prod` | ✅ GERM-005 levé — le profiler n'est de toute façon jamais chargé (`config/bundles.php` le réserve à `dev`/`test`) |
| `CORS_ALLOW_ORIGIN` | `^(https?://(localhost\|127\.0\.0\.1)(:[0-9]+)?\|capacitor://localhost)$` — identique au local | ⚠️ A révélé un bug dans le correctif GERM-002 (voir ci-dessous), **corrigé avant tout déploiement** |
| `QR_SECRET_KEY` | **Non surchargée** au 14/09/2026 — la production utilisait la valeur versionnée dans `.env` | ✅ **GERM-006, corrigé le 15/09/2026** — rotée en production, retirée du dépôt |
| En-têtes de sécurité du SPA | **Aucun** au 14/09/2026 — `curl -I https://10.112.26.30/app/` ne renvoyait que `Content-Type`, `ETag`, `Last-Modified` | ✅ **GERM-023, corrigé le 15/09/2026** — les 6 en-têtes sont désormais présents, vérifié en production |

La vérification a évité un incident : le web et l'API de production sont
servis depuis la **même origine** (`https://10.112.26.30` pour les deux), ce
que `CORS_ALLOW_ORIGIN` n'a jamais eu besoin de couvrir puisque le navigateur
n'applique pas CORS à une requête même origine. Le correctif GERM-002 traitait
initialement tout `Origin` absent de cette liste comme suspect — sans
exception pour ce cas, **chaque écriture du web en production serait tombée
en 403 dès le déploiement**. Corrigé (`d13d17e`) : une origine strictement
égale à l'hôte de la requête est désormais acceptée avant consultation de la
liste CORS, verrouillé par 11 tests dédiés.

Les deux constats de cette vérification ont été corrigés le 15/09/2026
(GERM-006, GERM-023), détaillés en §7.

### Risques acceptés ou reportés

| Risque | Raison |
|---|---|
| Journalisation d'audit partielle (GERM-018) | Socle minimal déployé ; ne couvre pas encore tous les événements du §26 (ex. téléchargement de document) |
| Aucune CI/CD (GERM-017) | Lot ultérieur. Rien n'empêche de déployer du code non testé |
| Aucun MFA (§21) | Non demandé à ce stade ; recommandé pour `ROLE_SUPERADMIN` |
| Aucun flux de réinitialisation de mot de passe | Tout renouvellement passe par un administrateur — moins de surface, plus de friction |
| `style-src 'unsafe-inline'` dans la CSP | Nécessaire aux attributs `style=` des templates Twig. Aucun `<script>` inline, `script-src` reste strict |
| Contrôles d'appartenance sans Voter (GERM-020) | Corrects aujourd'hui, fragiles demain |
| Cartes professionnelles en circulation | Le numéro imprimé reste la clé de vérification publique ; seule la limitation de débit protège l'annuaire |
| MySQL non chiffré, ni en transit ni au repos (GERM-026) | Évalué le 15/09/2026 ; activer le chiffrement suppose une décision sur la gestion des clés et un test de compatibilité, hors périmètre d'une évaluation |

**GERM-027 n'est pas un risque accepté, mais une question ouverte à trancher
sans délai** : aucune preuve d'un processus de sauvegarde de `germ_db` n'a
été trouvée sur le serveur le 15/09/2026 (voir §7) — si confirmé, c'est un
risque de perte de données totale, pas seulement de confidentialité.

### Ce que cet audit ne dit pas

Il ne dit rien de la sécurité réseau, de la configuration d'Apache et de PHP en
production, ni de la sécurité physique des serveurs, ni de la résistance à un
test d'intrusion dynamique. Sur les sauvegardes (§59, initialement hors
périmètre) : une recherche sommaire le 15/09/2026 n'en a trouvé aucune trace
sur le serveur applicatif (GERM-027), mais un mécanisme externe (hyperviseur,
infrastructure) reste possible et n'a pas pu être vérifié depuis cette
machine.

---

## 11. Score de maturité (§68)

| Domaine | Avant | Après lots 1-2 |
|---|---|---|
| OWASP Top 10 | DEVELOPING | **DEFINED** |
| OWASP API Security | DEVELOPING | **DEFINED** |
| ASVS L2 | INITIAL | **DEVELOPING** |
| Authentification | DEVELOPING | **DEFINED** |
| Autorisation | DEFINED | **DEFINED** |
| Cryptographie | DEFINED | **DEVELOPING** (mécanismes applicatifs et gestion des secrets corrigés — GERM-006 ; MySQL non chiffré en transit ni au repos, GERM-026 ouvert) |
| Journalisation | INITIAL | **DEVELOPING** |
| Infrastructure | *non évalué* | **DEVELOPING** (en-têtes du SPA corrigés, GERM-023 ; exposition réseau et privilèges MySQL vérifiés conformes, GERM-026 ; aucune preuve de sauvegarde trouvée, GERM-027 ; configuration Apache/PHP au-delà reste non évaluée) |
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

**Périmètre resserré (décision du 15/09/2026)** : l'intégration MIC-GAR est
abandonnée pour le moment (GERM-012 sans objet, voir §7), et la mise en place
d'un SIEM/SOC (Wazuh ou équivalent) est retirée de la feuille de route. La
suite de l'audit se limite désormais à la sécurité du **code et des données**
elles-mêmes — algorithmes, protocoles et bibliothèques à utiliser pour les
protéger en transit (TLS, en-têtes) et au repos (hachage, chiffrement,
gestion des clés et secrets) — à l'exclusion de toute infrastructure de
supervision/détection externe au périmètre applicatif.

| Priorité | Chantier | Référence |
|---|---|---|
| 1 | **Déployer les lots 1, 2 et le correctif GERM-002** — plus rien ne bloque | `c70cd6f`, `a2bd956`, `d13d17e` |
| 2 | **Confirmer l'existence (ou l'absence) d'un mécanisme de sauvegarde de `germ_db`** — aucune trace trouvée sur le serveur applicatif, à vérifier au niveau infrastructure/hyperviseur avant toute autre chose | GERM-027 |
| 3 | Décider du chiffrement de MySQL (transport + repos, y compris le disque) — décision de gestion des clés à prendre avant implémentation | GERM-026 |
| 4 | Étendre la journalisation d'audit à la couverture complète du §26 (application uniquement, sans volet SIEM) | §25-27, GERM-018 |
| 5 | Base de test dédiée, activation des 6 tests inactifs, extension de la matrice §55 | §54-55, GERM-014 |
| 6 | Chaîne CI/CD avec barrière de sécurité bloquante | §58, GERM-017 |
| 7 | Lot performance | §41-47 |
| 8 | `PersonnelOwnershipVoter` pour centraliser les 14 contrôles d'appartenance | GERM-020 |

---

*Document produit dans le cadre de la mission d'audit définie par
« Audit OWASP GERM » (76 sections). Il a vocation à être révisé à chaque lot de
correction. La sécurité est un processus continu, pas un état atteint.*
