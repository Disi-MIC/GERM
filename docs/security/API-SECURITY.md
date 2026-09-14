# Sécurité de l'API — GERM

Inventaire des routes (§65, API9) et analyse OWASP API Security Top 10 (§29-36).

Mis à jour au 14/09/2026 · **197 routes** sous `/api`.

---

## 1. Modèle d'authentification

L'API ne fonctionne **pas** au jeton porteur : elle s'authentifie par **cookie
de session**, posé par `json_login` sur `POST /api/login` et rejoué par chaque
requête grâce à `withCredentials` côté Angular.

Conséquence directe : l'API est structurellement exposée au CSRF, d'autant que
le cookie est `SameSite=None` en production — contrainte imposée par la WebView
Capacitor, dont l'origine `capacitor://localhost` est cross-site. C'est ce qui
rend le contrôle d'origine de la section 3 indispensable et non optionnel.

Le JWT RS256 existe bien, mais il n'authentifie **rien** dans GERM : il est
émis à la connexion à destination de l'application tierce MIC-GAR.

## 2. Inventaire des routes

Regroupé par ressource. « Audit » = la trace serait requise par le SOC (§26) ;
**aucune n'est journalisée aujourd'hui** (GERM-018).

| Ressource | Routes | Rôle requis | Sensibilité | Débit | Audit |
|---|---|---|---|---|---|
| `/api/login` | 1 | **PUBLIC** | SENSIBLE | 5 / 15 min | **Oui** |
| `/api/logout` | 1 | `ROLE_AGENT` | INTERNE | — | Oui |
| `/api/me/*` | 35 | `ROLE_AGENT` | CONFIDENTIEL | — | Partiel |
| `/api/personnels` | 8 (+1) | `ROLE_RH_PERSONNEL` · lecture élargie à `ROLE_RH_CARTE_PRO`, `ROLE_IT_STOCK` | **SENSIBLE** | export 10/h | **Oui** |
| `/api/documents-administratifs` | 6 | `ROLE_RH_PERSONNEL` | **SENSIBLE** | export 10/h | **Oui** |
| `/api/cartes-professionnelles` | 10 | `ROLE_RH_CARTE_PRO` | CONFIDENTIEL | export 10/h | **Oui** |
| `/api/demandes-carte-pro` | 8 | `ROLE_RH_CARTE_PRO` | CONFIDENTIEL | — | **Oui** |
| `/api/conges` | 5 | `ROLE_RH_CONGE` | CONFIDENTIEL | — | Oui |
| `/api/decisions-conge` | 6 | `ROLE_RH_CONGE` | CONFIDENTIEL | export 10/h | **Oui** |
| `/api/demandes-decision` | 12 | `ROLE_RH_CONGE` | CONFIDENTIEL | — | **Oui** |
| `/api/demandes-jouissance` | 7 | `ROLE_RH_CONGE` | CONFIDENTIEL | — | **Oui** |
| `/api/historique-affectations` | 4 | `ROLE_RH_PERSONNEL` | CONFIDENTIEL | — | Oui |
| `/api/materiels-informatiques` | 13 | `ROLE_IT_STOCK` (+ `ROLE_IT_TICKETS` en lecture) | CONFIDENTIEL | export 10/h | Oui |
| `/api/composants-materiel` | 5 | `ROLE_IT_STOCK` | INTERNE | — | Non |
| `/api/maintenances` · `/api/changements-cartouche` | 8 | `ROLE_IT_STOCK` | INTERNE | — | Non |
| `/api/licences-logicielles` | 4 | `ROLE_IT_STOCK` | INTERNE | — | Non |
| `/api/tickets-incident` · `/api/tickets-escalade` | 13 | `ROLE_IT_TICKETS` | INTERNE | — | Oui |
| `/api/services` · `/api/directions` | 12 (+2) | `ROLE_RH_PERSONNEL` / `ROLE_RH_RESPONSABLE` | INTERNE | — | Non |
| `/api/apercu-organisation/*` | 3 | `ROLE_AGENT` (périmètre dérivé du serveur) · `ROLE_AUTORITE` pour le ministère | CONFIDENTIEL | — | Non |
| `/api/users` | 2 | `ROLE_RH_RESPONSABLE` | **SENSIBLE** | — | **Oui** |
| `/api/delegations` | 4 | `ROLE_RH_RESPONSABLE` | **SENSIBLE** | — | **Oui** |
| `/api/parametres-*-conge` | 4 | `ROLE_RH_CONGE` | INTERNE | — | Oui |
| `/api/dashboard/*` | 4 | selon le domaine | INTERNE | — | Non |
| `/api/liste_valeurs` | 1 (+1) | `ROLE_RH_PERSONNEL` ou `ROLE_IT_STOCK` ou `ROLE_RH_CONGE` | PUBLIC-interne | — | Non |
| `/api/historiques-changement-*` | 4 | selon le domaine | CONFIDENTIEL | — | Non |
| `/api/pieces-decision` · `/api/pieces-jouissance` | 2 | `ROLE_RH_CONGE` | **SENSIBLE** | — | **Oui** |
| `/api/docs` · `/api/errors` · `/api/validation_errors` · `/api/.well-known` | 8 | `ROLE_AGENT` | INTERNE | — | Non |

**Route publique hors `/api`** : `GET /verif-carte/{numero}` — aucune
authentification, 20 requêtes / min / IP.

### Hygiène de l'inventaire (API9)

- **Aucune route morte, de test ou de debug** dans le périmètre applicatif.
- **Aucune API versionnée obsolète** : il n'existe qu'une version.
- Les 8 routes `docs` / `errors` / `validation_errors` / `.well-known` sont des
  points d'entrée internes d'API Platform, pas du code métier.

## 3. OWASP API Security Top 10 (2023)

### API1 — Broken Object Level Authorization ✅

**14 routes `/api/me/*` acceptent un identifiant fourni par le client. Les 14
vérifient l'appartenance côté serveur** — vérifié ligne à ligne. Les 22 routes
de liste n'acceptent aucun identifiant et filtrent sur le personnel connecté.

Le cloisonnement organisationnel est dérivé du serveur, jamais d'un paramètre :

```php
// ApercuOrganisationController — aucun paramètre client
$service = $repository->findOneBy(['responsable' => $personnel]);
```

Un chef du service A ne peut structurellement pas cibler le service B.

**Réserve** : ces 14 contrôles sont écrits à la main, sous trois formes et avec
deux codes de retour différents. Corrects aujourd'hui, non testés, et chaque
nouvelle route repose sur la vigilance du développeur (GERM-020).

### API2 — Broken Authentication ⚠️ corrigé

Trois défauts, tous corrigés dans le lot 1 :

| | Défaut | Correction |
|---|---|---|
| GERM-001 | Compte désactivé accepté sur `/api/login` | `user_checker` sur le pare-feu `api` |
| GERM-004 | Session survivant à la désactivation | `EquatableInterface` |
| GERM-002 | Contournement CSRF sur les écritures sans corps | Contrôle de l'`Origin` |

### API3 — Broken Object Property Level Authorization ⚠️

**En écriture : conforme.** Aucune hydratation aveugle — tous les contrôleurs
désérialisent avec des groupes `api:write` explicites. Les champs sensibles
(`roles`, `password`, `createdAt`) n'y figurent pas. Un client ne peut pas
s'attribuer `ROLE_SUPERADMIN` par le corps d'une requête.

**En lecture : non conforme.** `GET /api/personnels` renvoie l'intégralité du
groupe `api:read` — date de naissance, adresse, téléphone, e-mail,
observations — à `ROLE_IT_STOCK` et `ROLE_RH_CARTE_PRO`, alors que la mesure
des usages réels montre qu'ils n'exploitent que `nomComplet`, `matricule`,
`nom`, `prenom` (**GERM-009**).

Le seul cloisonnement de champ existant est `fournisseur` sur
`MaterielInformatique`, isolé dans `api:read:rh` — la démonstration que le
mécanisme est maîtrisé, simplement pas généralisé.

### API4 — Unrestricted Resource Consumption ⚠️ corrigé

| Surface | Protection |
|---|---|
| `/api/login` | 5 / 15 min (identifiant + IP) |
| `/verif-carte` | 20 / min / IP |
| Exports CSV (5) | 10 / h / **compte** |
| Uploads | 10 Mo, type reniflé |
| Collections | ⚠️ `paginationEnabled: false` sur 5 ressources |
| Génération PDF | ⚠️ aucune limite |

`paginationEnabled: false` est un choix assumé (le frontend filtre côté client)
mais ne tiendra pas à l'échelle, et signifie qu'un appel renvoie l'effectif
complet du Ministère.

### API5 — Broken Function Level Authorization ✅

Couverture `#[IsGranted]` intégrale : les seules exceptions sont
`ApiSecurityController` (login/logout, public par nature) et les deux
`DashboardController`, protégés méthode par méthode. Les 24 ressources API
Platform portent toutes une expression `security:`.

### API6 — Unrestricted Access to Sensitive Business Flows ⚠️ corrigé partiellement

| Flux | Protection |
|---|---|
| Dépôt de pièce après instruction | ✅ 409 hors état « en attente » (GERM-008) |
| Validation de décision / carte | ✅ Rôle dédié + contrôle d'origine |
| Export de masse | ✅ Quota 10/h |
| Suppression en masse | ✅ `bulk-etat` / `bulk-affectation` réservés à `ROLE_IT_STOCK` |
| Modification de rôle | ⚠️ `ROLE_RH_RESPONSABLE` requis, **mais non journalisée** |

### API7 — Server Side Request Forgery ✅

Une seule sortie réseau : l'import Google Sheets. `https` imposé, hôte
strictement égal à `docs.google.com`, identifiant extrait par expression
régulière puis **URL reconstruite**. Dompdf tourne avec
`setIsRemoteEnabled(false)`.

### API8 — Security Misconfiguration ⚠️

| Point | État |
|---|---|
| CORS | `origin_regex` avec `allow_credentials: true`, jamais `*`. Vérifié : la valeur de production ne couvre que `localhost`/`127.0.0.1` et l'app mobile — le web y échappe car servi en même origine que l'API. A révélé un bug dans le contrôle d'`Origin` de GERM-002, corrigé avant déploiement (`d13d17e`) |
| En-têtes | CSP, HSTS, `nosniff`, `frame-ancestors 'none'`, `Permissions-Policy`. **Ne couvrent pas le SPA servi par Apache** |
| Réponses d'erreur | Format uniforme `{errors: {champ: message}}`, sans trace technique |
| `APP_ENV` en production | ✅ Vérifié `prod` — profiler écarté (GERM-005) |
| **Documentation interactive** | ⚠️ **GERM-022** — voir ci-dessous |

#### GERM-022 — `/api/docs` accessible à tout agent authentifié · LOW

Vérifié : `GET /api/docs` renvoie `401` sans authentification, mais `200` pour
n'importe quel compte, y compris un `ROLE_AGENT` sans aucun rôle métier — la
règle `^/api → ROLE_AGENT` suffit, aucune restriction supplémentaire n'existe.

Ce n'est pas une faille d'accès aux données : chaque opération reste protégée
par son propre contrôle de rôle. C'est un **cadeau à la reconnaissance** — la
liste exhaustive des 24 ressources, de leurs opérations et de leurs champs,
offerte à un compte compromis de bas privilège. Le §34 demande explicitement de
ne pas l'exposer en production si elle n'est pas nécessaire.

*Correction proposée* : désactiver Swagger UI en production
(`api_platform.enable_swagger_ui: false` sous `when@prod`), ou réserver
`^/api/docs` à `ROLE_SUPERADMIN` par une règle `access_control` placée avant
`^/api`.

### API9 — Improper Inventory Management ✅

Inventaire produit ci-dessus. Aucune route morte, de debug ou de version
obsolète. À maintenir à jour à chaque ajout de ressource.

### API10 — Unsafe Consumption of APIs ✅

Une seule API tierce consommée (Google Sheets), avec liste blanche stricte.
GERM est en revanche **fournisseur** pour MIC-GAR : la qualité du jeton émis
engage la sécurité d'une application tierce (voir GERM-012 — absence de `jti`
et `nbf`, durée de 8 h, non révocable).

## 4. Synthèse

| | Verdict |
|---|---|
| API1 BOLA | ✅ Conforme |
| API2 Authentification | ⚠️ Corrigé, non déployé |
| API3 Propriétés d'objet | ⚠️ Écriture conforme, **lecture sur-exposée** |
| API4 Consommation | ⚠️ Corrigé ; pagination à revoir |
| API5 Fonctions | ✅ Conforme |
| API6 Flux métier | ⚠️ Corrigé partiellement |
| API7 SSRF | ✅ Conforme |
| API8 Configuration | ⚠️ Corrigé partiellement — production vérifiée (GERM-005 écarté, GERM-006/GERM-023 confirmés) ; GERM-022 ouvert |
| API9 Inventaire | ✅ Conforme |
| API10 Consommation d'API | ✅ Conforme en entrée ; à améliorer en sortie |

**Deux chantiers pour l'API** : la minimisation des données en lecture
(GERM-009) et la journalisation des 12 familles de routes marquées « Audit :
Oui » (GERM-018).
