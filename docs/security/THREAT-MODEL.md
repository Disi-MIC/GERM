# Modèle de menaces — GERM

Analyse de menaces simplifiée, conforme au §12 du cahier des charges :
**Actifs · Acteurs · Frontières de confiance · Menaces · Contrôles**, appliquée
aux flux métier sensibles.

Mis à jour au 14/09/2026.

---

## 1. Actifs à protéger

Classés selon la grille du §61.

| Classification | Actif | Où |
|---|---|---|
| **SENSIBLE** | État civil des agents : date de naissance, adresse postale, téléphone personnel, e-mail | `personnel` |
| **SENSIBLE** | Documents administratifs (actes, pièces justificatives, décisions) — chiffrés au repos depuis GERM-025, sauf ceux stockés avant ce correctif | SFTP `10.112.26.31` |
| **SENSIBLE** | Mots de passe (hachés), `QR_SECRET_KEY`, `FILE_ENCRYPTION_KEY`, `APP_SECRET` | `user`, `.env.local` |
| **CONFIDENTIEL** | Carrière, congés, décisions administratives, cartes professionnelles | `historique_affectation`, `conge`, `decision_conge`, `carte_professionnelle` |
| **CONFIDENTIEL** | Inventaire du parc informatique : n° de série, vulnérabilités connues, affectations | `materiel_informatique` |
| **INTERNE** | Organigramme : services, directions, responsables | `service`, `direction` |
| **PUBLIC** | Validité d'une carte professionnelle : nom, fonction, date d'expiration | `/verif-carte/{numero}` |

> La ligne PUBLIC est un choix de conception assumé — une carte doit pouvoir
> être vérifiée par un tiers — mais c'est la **seule** surface non authentifiée
> de l'application, et donc la plus exposée.

## 2. Acteurs

### Légitimes

| Acteur | Rôle technique | Périmètre |
|---|---|---|
| Agent | `ROLE_AGENT` | Ses propres données uniquement (« Mon espace ») |
| Chef de service | `ROLE_RESPONSABLE_SERVICE` | Son service, dérivé du serveur |
| Directeur | `ROLE_DIRECTION_MINISTERIELLE` | Sa direction, dérivée du serveur |
| Autorité (SG / DC / Ministre) | `ROLE_AUTORITE` | Aperçu ministériel |
| Gestionnaire RH | `ROLE_RH_PERSONNEL` / `_CONGE` / `_CARTE_PRO` | Tous les agents, par domaine |
| Responsable RH | `ROLE_RH_RESPONSABLE` | Les trois domaines RH |
| Informatique | `ROLE_IT_STOCK` / `_TICKETS` / `_RESPONSABLE` | Parc, tickets, maintenance |
| Superadmin | `ROLE_SUPERADMIN` | Tout, plus le back-office Twig |

### Adverses

| Acteur | Capacité | Motivation plausible |
|---|---|---|
| **Anonyme externe** | Atteint `/api/login`, `/admin/login`, `/verif-carte` | Reconstitution de l'annuaire, force brute, usurpation d'identité physique |
| **Agent interne curieux** | Compte valide `ROLE_AGENT` | Consulter le dossier d'un collègue, modifier son propre dossier après instruction |
| **Agent révoqué** | Identifiants encore connus, compte désactivé | Conserver un accès après son départ |
| **Rôle métier détourné** | `ROLE_IT_STOCK` légitime | Exfiltrer des données RH hors de son domaine |
| **Compte compromis** | Session ou identifiants volés | Exfiltration de masse, altération de dossiers |
| **Site tiers malveillant** | Aucune authentification, mais la victime est connectée | CSRF sur les actions de validation |
| **Poste de destination d'un export** | Ouvre un CSV produit par GERM | Exécution de code par formule |

## 3. Frontières de confiance

```
        ┌── ZONE NON FIABLE ────────────────────────────────┐
        │  Navigateur · WebView Capacitor · Internet        │
        └───────────────────┬───────────────────────────────┘
                            │  ← FRONTIÈRE 1
                            │    TLS · CORS · Origin · Content-Type
                            │    login_throttling · rate limiters
        ┌───────────────────▼───────────────────────────────┐
        │  SYMFONY — autorité de sécurité                   │
        │  pare-feu → access_control → IsGranted →          │
        │  security: ApiResource → appartenance → état      │
        └──────┬─────────────────────┬───────────────────────┘
               │ ← FRONTIÈRE 2       │ ← FRONTIÈRE 3
               │   Doctrine          │   SFTP
        ┌──────▼──────┐       ┌──────▼──────┐
        │   MySQL     │       │  Fichiers   │
        │ .26.30      │       │  .26.31     │
        └─────────────┘       └─────────────┘
```

**Frontière 1 — la seule qui compte vraiment.** Tout ce qui vient du client est
hostile par hypothèse. Le frontend Angular, ses `guards` et ses `@if` de rôle
sont de l'**ergonomie**, jamais de la sécurité : ils masquent ce que
l'utilisateur ne peut pas faire, ils ne l'en empêchent pas. Le backend Symfony
est l'unique autorité.

GERM n'a aucune frontière sortante vers une application tierce : l'intégration
SSO prévue vers MIC-GAR a été abandonnée avant sa mise en service (décision du
15/09/2026).

## 4. Menaces par flux

### 4.1 Authentification

| Menace | Contrôle | État |
|---|---|---|
| Force brute / bourrage d'identifiants | `login_throttling` 5 / 15 min, par identifiant **et** par IP, sur les deux pare-feu | ✅ |
| Connexion d'un compte révoqué | `user_checker` sur les deux pare-feu + `getRoles()` vide si inactif | ✅ *(GERM-001, corrigé)* |
| Session survivant à la révocation | `EquatableInterface` sur `User` | ✅ *(GERM-004, corrigé)* |
| Fixation de session | Régénération de l'ID à la connexion | ✅ |
| Vol de session | `HttpOnly`, `Secure`, TLS | ✅ |
| Mot de passe trivial imposé par un admin | Contrainte de longueur à la création **et** en modification | ⚠️ Partiel — pas de complexité, pas de MFA |
| Énumération de comptes | Message d'erreur générique | ✅ |

### 4.2 Données RH

| Menace | Contrôle | État |
|---|---|---|
| Agent lisant le dossier d'un autre | `ROLE_AGENT` n'hérite d'aucun rôle RH ; `/api/me/*` filtre sur le personnel connecté | ✅ |
| Chef de service A lisant le service B | Périmètre dérivé du serveur (`findOneBy(['responsable' => $personnel])`), aucun paramètre client | ✅ |
| **Rôle IT lisant l'état civil complet** | `PersonnelNormalizer` retire les champs sensibles, sauf pour sa propre fiche | ✅ **GERM-009 corrigé** |
| **Rôle non-Stock lisant le fournisseur d'un matériel** | `MaterielInformatiqueNormalizer` retire `api:read:rh` pour tout appelant sans `ROLE_IT_STOCK`, y compris sur les réponses manuelles de contrôleur | ✅ **GERM-024 corrigé** |
| Exfiltration de masse | Quota d'exports 10/h/compte | ⚠️ Partiel — la collection `/api/personnels` reste non paginée |
| Altération sans trace | — | ❌ **Aucune journalisation d'audit** (GERM-018) |

### 4.3 Cartes professionnelles

| Menace | Contrôle | État |
|---|---|---|
| Énumération de l'annuaire via `/verif-carte` | 20 requêtes / min / IP | ⚠️ Atténué — le numéro imprimé reste la clé (GERM-007) |
| Fuite de données par la page publique | Nom, fonction, expiration seulement — ni matricule, ni contact, ni photo | ✅ |
| Carte annulée redevenant valide | Statut porté par l'entité, transitions côté RH | ⚠️ Non testé (§13) |
| Validation par un rôle non habilité | `ROLE_RH_CARTE_PRO` requis | ✅ |

### 4.4 Documents et pièces justificatives

| Menace | Contrôle | État |
|---|---|---|
| Téléchargement du document d'un autre agent | Contrôle d'appartenance sur chaque route de fichier | ✅ |
| Path traversal | Chemins issus de la base uniquement, noms aléatoires | ✅ |
| Upload d'exécutable / SVG piégé | Type MIME reniflé, liste blanche de 5 types, ré-encodage des images | ✅ |
| Accès direct sans passer par Symfony | Stockage hors racine web | ✅ |
| **Compromission du disque SFTP ou du transport** | Chiffrement du contenu au repos, libsodium `secretbox` | ✅ **GERM-025 corrigé** — non rétroactif sur les fichiers déjà stockés |
| **Substitution de pièce après instruction** | Dépôt réservé à l'état « en attente » | ✅ *(GERM-008, corrigé)* |
| Import malveillant (XLSX à formules) | Superadmin uniquement, jamais stocké | ⚠️ GERM-021 |

### 4.5 Congés et décisions

| Menace | Contrôle | État |
|---|---|---|
| Un agent valide son propre congé (§13) | Validation réservée à `ROLE_RH_CONGE`, routes distinctes du libre-service | ✅ |
| **CSRF sur une validation** | Contrôle de l'`Origin` sur toutes les écritures `/api` | ✅ *(GERM-002, corrigé)* |
| Modification d'une décision validée sans traçabilité | Journaux `historique_changement_*` sur personnel et matériel | ⚠️ Partiel — pas de journal d'audit transverse |
| Éligibilité contournée | `EligibiliteDecisionCongeService`, testé unitairement | ✅ |

### 4.6 Exports

| Menace | Contrôle | État |
|---|---|---|
| **Exécution de code chez le destinataire** | `protegerCsv()` sur les 5 exports | ✅ *(GERM-003, corrigé)* |
| Export par un rôle non habilité | `#[IsGranted]` au niveau classe | ✅ |
| Déni de service par exports répétés | 10 / heure / compte | ✅ *(GERM-010)* |
| Épuisement mémoire sur gros volume | — | ⚠️ `findAll()` hydrate tout — reporté au lot performance |
| Export non tracé | — | ❌ GERM-018 |

### 4.7 QR codes du parc informatique

| Menace | Contrôle | État |
|---|---|---|
| Lecture du QR par un lecteur générique | Le QR encode `germ://materiel/<jeton chiffré>` — ni identifiant, ni URL exploitable | ✅ |
| Forge d'un jeton | libsodium `secretbox`, chiffrement authentifié | ✅ |
| **Clé compromise** | — | ❌ **Confirmé** : `QR_SECRET_KEY` de production non surchargée, c'est la valeur versionnée (GERM-006) |
| Résolution d'un jeton par un tiers | `ROLE_IT_STOCK` requis même avec un jeton valide | ✅ |
| Étiquette d'un matériel qui n'est pas le sien | `/api/me/materiels/{id}/qrcode` restreint au matériel affecté | ✅ |

### 4.8 Administration

| Menace | Contrôle | État |
|---|---|---|
| Agent atteignant le back-office | `access_control ^/admin → ROLE_SUPERADMIN` **et** `#[IsGranted]` sur les 17 contrôleurs | ✅ *(GERM-013)* |
| Nouveau contrôleur exposé par oubli | Règle globale `ROLE_SUPERADMIN` comme filet | ✅ *(GERM-013)* |
| Suppression par CSRF | `isCsrfTokenValid()` sur ~20 opérations, toutes en POST | ✅ |
| Escalade de privilèges par le formulaire de compte | Groupes de désérialisation explicites, formulaire Twig typé | ✅ |
| Usurpation d'identité non tracée | `switch_user` déclaré nulle part ; rôle mort retiré | ✅ *(GERM-019)* |
| **Profiler exposé** | `APP_ENV=prod` vérifié ; bundle réservé à `dev`/`test` de toute façon | ✅ GERM-005 écarté |

## 5. Principes de conception appliqués

| Principe | Application dans GERM | Tenu ? |
|---|---|---|
| **Defense in Depth** | CSRF : Origin **puis** Content-Type. Compte désactivé : `user_checker` **puis** `getRoles()` vide. Admin : `access_control` **puis** `IsGranted` | ✅ |
| **Least Privilege** | 11 rôles distincts, RH et IT réellement disjoints | ✅ Tenu sur les rôles et désormais sur les données, pour Personnel (GERM-009) comme pour `MaterielInformatique::$fournisseur` (GERM-024) |
| **Fail Secure** | Jeton QR invalide → 404. Objet hors périmètre → 404 plutôt que 403 (ne révèle pas l'existence). Demande instruite → 409 | ✅ |
| **Secure by Default** | Ressources API protégées par défaut, `access_control` restrictif, listes blanches plutôt que listes noires | ✅ |
| **Separation of Duties** | Ni le RH ni l'informatique n'hérite de l'autre ; validation des cartes réservée au responsable RH | ✅ |

## 6. Scénarios d'attaque prioritaires

Classés par produit *probabilité × impact*, après les lots 1 et 2 et la
vérification de production du 14/09/2026.

| # | Scénario | Statut |
|---|---|---|
| 1 | ~~Profiler Symfony accessible en production~~ | ✅ **Écarté** — `APP_ENV=prod` vérifié, et le bundle n'est de toute façon jamais chargé hors `dev`/`test` |
| 2 | **Agent révoqué conservant son accès** par le web ou le mobile | ✅ Corrigé, **non déployé** |
| 3 | **CSRF sur une validation de décision** depuis un site piégé visité par un cadre RH | ✅ Corrigé (deux passes — voir §6 de `SECURITY-CONTROLS.md`), **non déployé** |
| 4 | **Exécution de code sur un poste RH** à l'ouverture d'un export CSV | ✅ Corrigé, **non déployé** |
| 5 | **Forge d'une étiquette QR** par quiconque a accès au dépôt Git | ❌ **Confirmé** — `QR_SECRET_KEY` de production = valeur versionnée (GERM-006) |
| 6 | **Clickjacking ou injection sur la page réellement chargée par les utilisateurs** : le SPA ne porte aucun en-tête de sécurité, contrairement aux routes Symfony | ❌ **Confirmé** (GERM-023) |
| 7 | **Exfiltration de l'état civil par un compte informatique** légitime ou compromis | ✅ Corrigé (GERM-009), **déployé** — `1e8bd47` |
| 8 | **Compromission indétectable** : sans journal d'audit, aucun des scénarios ci-dessus ne laisserait de trace exploitable | ❌ Ouvert (GERM-018) |

> Le scénario 6 est transverse : il ne crée pas de vulnérabilité, il rend
> toutes les autres invisibles. C'est ce qui justifie de placer la
> journalisation d'audit immédiatement après le déploiement des correctifs.

## 7. Hors périmètre de ce modèle

Sécurité physique des serveurs, sécurité du réseau ministériel, configuration
d'Apache et de PHP en production, sauvegardes et restauration (§59), postes de
travail des agents, menace d'un administrateur système malveillant disposant
d'un accès direct à la base ou au serveur de fichiers, et toute intégration
avec une application tierce (MIC-GAR, décision du 15/09/2026 de ne pas
l'intégrer pour le moment — voir OWASP-GERM-AUDIT.md).
