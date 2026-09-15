# Contrôles de sécurité — GERM

Inventaire des mécanismes de protection **effectivement en place**, avec leur
emplacement dans le code. Document de référence pour la maintenance : avant de
modifier l'un de ces fichiers, vérifier ce qu'il protège.

Mis à jour au 14/09/2026 (commits `c70cd6f`, `a2bd956`).

---

## 1. Authentification

| Contrôle | Où | Note |
|---|---|---|
| Hachage des mots de passe | `config/packages/security.yaml` → `password_hashers: 'auto'` | Symfony choisit le meilleur algorithme disponible et gère la migration |
| Anti-brute-force | `security.yaml` → `login_throttling` sur les pare-feu `api` **et** `main` | 5 tentatives / 15 min, par identifiant **et** par IP |
| Blocage des comptes désactivés | `security.yaml` → `user_checker` sur les **deux** pare-feu + `src/Security/UserChecker.php` | ⚠️ Sans la déclaration sur `api`, Symfony retombe sur `InMemoryUserChecker` qui ignore les entités Doctrine — c'était la faille GERM-001 |
| Défense en profondeur sur l'état du compte | `src/Entity/User.php::getRoles()` | Renvoie `[]` si `actif` est faux |
| Longueur minimale du mot de passe | `src/Form/UserType.php` | 8 caractères, à la création **et** en modification |

**Absents, assumés** : MFA, politique de complexité, `NotCompromisedPassword`,
flux de réinitialisation en libre-service.

## 2. Session

| Contrôle | Où |
|---|---|
| Régénération de l'ID à la connexion (anti-fixation) | Comportement Symfony par défaut (`session_fixation_strategy: migrate`), non surchargé |
| Invalidation à la déconnexion | `src/EventListener/ApiLogoutListener.php` + `SessionLogoutListener` natif |
| Invalidation au changement de mot de passe | Natif Symfony, **préservé explicitement** dans `User::isEqualTo()` |
| Invalidation à la désactivation du compte | `src/Entity/User.php::isEqualTo()` (`EquatableInterface`) |
| `cookie_secure`, `cookie_samesite` | `config/packages/framework.yaml` — `lax` en dev, `secure: true` + `samesite: none` en prod |

> **Piège documenté.** Dès qu'une entité implémente `EquatableInterface`,
> Symfony s'appuie **exclusivement** sur `isEqualTo()` et abandonne ses propres
> comparaisons. Toute modification de cette méthode doit conserver la
> comparaison du mot de passe, de l'identifiant et des rôles — sinon
> l'invalidation native disparaît sans aucun symptôme visible.
> Verrouillé par `tests/Entity/UserEtatCompteTest.php`.

`samesite: none` est imposé par la WebView Capacitor (origine
`capacitor://localhost`, donc cross-site). C'est ce choix qui rend la
protection CSRF de la section 4 indispensable.

## 3. Autorisation

| Niveau | Mécanisme | Où |
|---|---|---|
| Global | `access_control` | `security.yaml` — `^/admin/login` et `^/api/login` publics, `^/admin/aucun-acces` → `ROLE_AGENT`, `^/api` → `ROLE_AGENT`, `^/admin` → **`ROLE_SUPERADMIN`** |
| Contrôleur | `#[IsGranted]` | 45 contrôleurs ; couverture intégrale hors routes publiques par conception |
| Ressource API | expression `security:` | 24 entités `#[ApiResource]`, toutes protégées |
| Objet | contrôle d'appartenance manuel | 14 emplacements dans `MeController` / `MeDemandesController` |
| Métier | contrôle d'état | `MeDemandesController::refusSiDemandeFigee()` |

**L'ordre des règles `access_control` est signifiant** : la première qui
correspond l'emporte. Les exceptions (`/admin/login`, `/admin/aucun-acces`)
doivent rester **avant** la règle générale `^/admin`.

**Aucun Voter n'existe** (GERM-020). Les trois formes de contrôle
d'appartenance utilisées :

```php
// 1. Comparaison directe → 403
if ($carte->getPersonnel() !== $personnel) { throw $this->createAccessDeniedException(); }

// 2. Helper partagé → 403
if (!$this->appartientAMoi($demande->getPersonnel())) { ... }

// 3. Requête scopée → 404 (ne révèle pas l'existence de l'objet)
$repository->findOneBy(['id' => $id, 'affecteA' => $personnel]);
```

La forme 3 est la plus sûre (elle ne divulgue pas l'existence d'un objet
appartenant à autrui) et devrait servir de modèle.

## 4. Protection CSRF

L'API s'authentifie par cookie de session, ce qui l'expose structurellement au
CSRF. Deux barrières complémentaires, dans `src/EventListener/JsonContentTypeGuardListener.php` :

1. **Origine** (barrière principale) — toute écriture `POST/PUT/PATCH/DELETE`
   sur `/api` dont l'en-tête `Origin` est présent doit soit correspondre
   exactement à l'hôte de la requête elle-même (même origine), soit figurer
   dans `CORS_ALLOW_ORIGIN`. C'est la seule barrière efficace sur les
   écritures **sans corps**, qu'un `<form>` vide ou un `fetch()` cross-origin
   peut déclencher sans préflight.
2. **Content-Type** (défense secondaire) — un corps non vide doit être
   `application/json` ou `multipart/form-data`, deux types qu'un formulaire
   HTML tiers ne peut pas produire.

Origin absent → repli sur le Content-Type. Un client sans `Origin` n'est pas un
navigateur porteur de cookie, donc pas un vecteur CSRF.

> **Piège découvert à la vérification de production, corrigé avant tout
> déploiement (`d13d17e`).** En production, le frontend web et l'API sont
> servis depuis la **même origine** (`https://10.112.26.30` pour les deux) :
> Apache sert le SPA en statique sous `/app`, Symfony répond sous `/api` du
> même host. Un navigateur envoie `Origin` même sur une requête même origine
> pour les méthodes d'écriture — pas seulement en cross-origin. Une première
> version de ce contrôle ne reconnaissait que `CORS_ALLOW_ORIGIN`, qui ne
> couvre que `localhost`/`127.0.0.1` (dev) et `capacitor://localhost` (l'app
> mobile, seul cas réellement cross-origin) : cette regex n'a jamais eu
> besoin de couvrir le domaine de production, une requête même origine
> échappant à l'application du CORS par le navigateur. Sans l'exception
> même-origine, **chaque écriture du web en production serait tombée en 403
> dès le déploiement.** Un site tiers ne peut ni forger l'en-tête `Origin`,
> ni le faire coïncider avec l'hôte de la requête qu'il déclenche
> lui-même — l'exception n'ouvre donc aucune brèche. Verrouillé par
> `tests/EventListener/JsonContentTypeGuardListenerTest.php`, y compris deux
> tentatives d'usurpation par suffixe de domaine.

> **Dépendance de configuration restante.** La liste `CORS_ALLOW_ORIGIN` sert
> toujours de source de vérité pour les origines **cross-origin** légitimes
> (l'app mobile, ou un futur sous-domaine dédié au web). L'ajouter ou la
> modifier change aussi qui peut écrire dans l'API par ce canal.

**Côté Twig** : jetons CSRF stateless (`config/packages/csrf.yaml`), vérifiés
par `isCsrfTokenValid()` sur chacune des ~20 opérations destructives. Aucune
suppression n'est accessible en `GET`.

## 5. Validation des entrées

| Vecteur | Contrôle | Où |
|---|---|---|
| SQL | Doctrine paramétré exclusivement — 0 SQL natif, 0 concaténation, 44 `setParameter` | `src/Repository/` |
| XSS (Twig) | Auto-échappement ; **aucun `\|raw`** dans tout `templates/` | — |
| XSS (Angular) | Protections natives non contournées ; 2 `bypassSecurityTrustResourceUrl` n'enveloppant que des `blob:` produits localement par `URL.createObjectURL()` | `*-preview.component.ts` |
| Injection CSV | `AbstractController::protegerCsv()` sur les 5 exports | Préfixe `'` devant `=` `+` `-` `@` `\t` `\r` |
| Désérialisation | Groupes `api:write` explicites — aucune hydratation aveugle | Tous les contrôleurs d'écriture |
| Validation métier | Contraintes Symfony sur les entités, réponse 422 uniforme | `AbstractController::violationsResponse()` |

## 6. Fichiers et documents

Tout passe par `src/Service/FileStorage.php`.

| Contrôle | Détail |
|---|---|
| Nom de stockage | `bin2hex(random_bytes(16))` + extension déduite du type **reniflé**. Le nom fourni par l'utilisateur n'entre jamais dans un chemin |
| Path traversal | Structurellement impossible : les 18 points de lecture reçoivent une valeur issue de la base, aucune route n'accepte de chemin en paramètre |
| Type réel | `getMimeType()` (analyse binaire via `fileinfo`), **pas** le `Content-Type` client. Liste blanche de 5 types |
| SVG | Absent de toutes les listes blanches → pas de XSS par SVG |
| Images | Ré-encodage complet via `imagecreatefromstring()` → tout payload embarqué est détruit |
| Emplacement | Hors racine web (SFTP, ou `var/uploads` en repli). `public/` ne contient aucun répertoire d'upload |
| En-tête de téléchargement | `Content-Disposition` réduit à `[A-Za-z0-9-]` après translittération → pas d'injection d'en-tête |

**Non protégé** : l'import CSV/XLSX (`src/Controller/Admin/ImportController.php`)
ne passe pas par ces validations — ni taille, ni type réel — et évalue les
formules du classeur. Réservé au superadmin et jamais stocké (GERM-021).

## 7. Limitation de débit

`config/packages/rate_limiter.yaml` :

| Limiteur | Quota | Clé | Protège |
|---|---|---|---|
| `login_throttling` (natif) | 5 / 15 min | identifiant + IP | Force brute |
| `verification_carte` | 20 / min | **IP** | Énumération de l'annuaire sur une route publique |
| `export_csv` | 10 / h | **compte** | Abus d'exports (API4) |

Le choix de la clé est délibéré : les agents du Ministère sortent derrière une
IP publique commune, qui ferait un compteur partagé sur les exports. À
l'inverse, `/verif-carte` étant non authentifiée, l'IP est la seule clé
disponible.

## 8. En-têtes HTTP

`src/EventListener/SecurityHeadersListener.php` :

```
Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net;
  style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net;
  img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self';
  form-action 'self'; object-src 'none'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()
Strict-Transport-Security: max-age=31536000; includeSubDomains   (HTTPS uniquement)
```

> **Portée limitée, à connaître.** Ce listener ne couvre **que** les réponses
> passant par le noyau Symfony : API, back-office Twig, `/verif-carte`. En
> production, le shell Angular (`index.html`, JS, CSS) est servi directement
> par Apache en statique — les en-têtes du SPA dépendent donc de `mod_headers`,
> configuré au niveau du vhost, **hors de ce dépôt**. Corrigé et vérifié en
> production le 15/09/2026 (GERM-023) : un bloc `<Directory>` scopé au seul
> répertoire `/app` applique les 6 mêmes en-têtes, sans toucher à ce que
> Symfony pose déjà sur `/api` et `/admin`. Procédure :
> `docs/security/deploiement/GERM-023-headers-apache.md`.

`style-src 'unsafe-inline'` est nécessaire aux attributs `style=` des templates
Twig. Aucun `<script>` inline n'existe : `script-src` reste strict.

## 9. CORS

`config/packages/nelmio_cors.yaml` — `allow_credentials: true` avec
`origin_regex`, appliqué à `^/api` uniquement. La valeur vient de
`CORS_ALLOW_ORIGIN`. **Jamais `*`** : interdit avec `credentials: true`.

## 10. Cryptographie

| Usage | Mécanisme | Où |
|---|---|---|
| Mots de passe | Hachage `auto` | `security.yaml` |
| Jetons QR de matériel | libsodium `secretbox` (XSalsa20-Poly1305) — chiffrement **authentifié** : un jeton forgé échoue au déchiffrement | `src/Service/QrTokenService.php` |
| Fichiers au repos (photos, pièces jointes, cartes) | libsodium `secretbox`, même primitive, clé dédiée (`FILE_ENCRYPTION_KEY`, distincte de `QR_SECRET_KEY`) — chiffré avant écriture sur le SFTP, déchiffré à la lecture (GERM-025) | `src/Service/FileEncryptionService.php`, `src/Service/FileStorage.php` |
| Transport | TLS 1.2 / 1.3 confirmé en production ; SFTP (SSH) vers le stockage de fichiers | — |

Le QR code encode `germ://materiel/<jeton chiffré>` : un scan avec un lecteur
générique ne révèle ni identifiant, ni URL exploitable.

**GERM-025 — portée du chiffrement au repos.** SFTP protège le transport, pas
le fichier une fois posé sur le disque distant : n'importe qui disposant d'un
accès direct à ce disque (hors périmètre applicatif, voir §7 de
`THREAT-MODEL.md`) pouvait jusqu'ici lire les photos et pièces justificatives
en clair. Chaque fichier est désormais chiffré avant écriture. **Portée
volontairement non rétroactive** : les fichiers déjà stockés avant ce
correctif restent en clair jusqu'à leur remplacement — les rechiffrer en
masse suppose de parcourir tout le SFTP de production, une opération plus
risquée et distincte de cette correction, non entreprise ici sans décision
explicite. Le format enveloppé (`FileEncryptionService`) reconnaît les
fichiers historiques et continue de les servir normalement.

*L'intégration SSO vers MIC-GAR (jeton JWT RS256) a été abandonnée avant sa
mise en service — décision du 15/09/2026, voir OWASP-GERM-AUDIT.md. GERM
n'émet ni ne consomme aucun jeton pour une application tierce.*

## 11. Protection contre le SSRF

Une seule sortie réseau dans toute l'application : l'import Google Sheets
(`src/Import/ImportRunner.php`). `GoogleSheetUrlResolver` impose `https`, un
hôte strictement égal à `docs.google.com`, extrait l'identifiant par expression
régulière puis **reconstruit** l'URL — l'entrée utilisateur n'est jamais
transmise telle quelle.

La génération PDF utilise Dompdf avec `setIsRemoteEnabled(false)` : aucune
ressource distante n'est chargeable depuis un gabarit.

## 12. Ce qui n'existe pas

À dire explicitement, pour que personne ne présume d'une protection absente :

- **Aucune journalisation d'audit** : ni connexion, ni changement de rôle, ni
  accès refusé, ni export, ni téléchargement (GERM-018).
- **Aucune usurpation d'identité** : `switch_user` n'est déclaré nulle part.
- **Aucun MFA**, aucune politique de complexité de mot de passe.
- **Aucune CI/CD** : le déploiement se fait par `git push` vers un hook
  `post-receive`, sans test ni analyse de sécurité bloquante (GERM-017).
- **Aucun Voter** : les contrôles d'appartenance sont manuels (GERM-020).
