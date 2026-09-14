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
   sur `/api` dont l'en-tête `Origin` est présent doit correspondre à
   `CORS_ALLOW_ORIGIN`. C'est la seule barrière efficace sur les écritures
   **sans corps**, qu'un `<form>` vide ou un `fetch()` cross-origin peut
   déclencher sans préflight.
2. **Content-Type** (défense secondaire) — un corps non vide doit être
   `application/json` ou `multipart/form-data`, deux types qu'un formulaire
   HTML tiers ne peut pas produire.

Origin absent → repli sur le Content-Type. Un client sans `Origin` n'est pas un
navigateur porteur de cookie, donc pas un vecteur CSRF.

> **Dépendance de configuration.** Ce contrôle réutilise `CORS_ALLOW_ORIGIN`
> comme source de vérité unique. Modifier cette variable modifie **aussi** qui
> peut écrire dans l'API. Une regex trop étroite casse l'application, une regex
> trop large annule la protection.

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
> par Apache en statique. **Les en-têtes du SPA dépendent donc entièrement de
> `mod_headers`, hors de ce dépôt, et n'ont pas été vérifiés.**

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
| Jeton SSO MIC-GAR | JWT **RS256**, clé privée lue depuis `config/jwt/sso_private.pem` (gitignoré), jamais en variable d'environnement | `src/Service/SsoTokenService.php` |
| Transport | TLS 1.2 / 1.3 confirmé en production | — |

Le QR code encode `germ://materiel/<jeton chiffré>` : un scan avec un lecteur
générique ne révèle ni identifiant, ni URL exploitable.

**Contenu du jeton SSO** : `sub`, `email`, `nom`, `prenom`, `roles`. Aucun CIN,
matricule, téléphone ni adresse — conforme au §61. Le jeton n'est jamais
journalisé. Manquent `jti` et `nbf` (GERM-012).

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
- **Aucun chiffrement au repos** des documents stockés sur le serveur SFTP.
- **Aucun Voter** : les contrôles d'appartenance sont manuels (GERM-020).
