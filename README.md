# GERM — Gestion des Ressources du Ministère

Application Symfony 7 pour la gestion des ressources du Ministère : personnel, parc informatique et parc automobile. Cette première étape fournit l'**interface superadmin** avec CRUD complet sur toutes les ressources.

## Périmètre livré

- Modèle de données Doctrine détaillé : `Service` (directions/services), `User` (comptes de connexion des agents), `Personnel` (fiches RH), `MaterielInformatique` (parc IT), `Vehicule` (parc auto).
- Authentification classique (email + mot de passe) avec hiérarchie de rôles `ROLE_AGENT` < `ROLE_ADMIN` < `ROLE_SUPERADMIN`. L'espace `/admin` est réservé à `ROLE_SUPERADMIN`.
- Interface superadmin sur-mesure (Twig + Bootstrap 5, pas de bundle d'admin externe) : tableau de bord avec statistiques et alertes d'échéances, CRUD Personnel / Parc informatique / Parc automobile / Services / Comptes agents.
- Commande console `app:create-superadmin` pour créer le premier compte superadmin.

## Pourquoi ce projet est livré sans `vendor/`

L'environnement qui a généré ce projet n'a pas accès à PHP/Composer ni à un accès réseau vers `getcomposer.org` ou Packagist. Le code (entités, contrôleurs, formulaires, templates, configuration) a donc été écrit à la main en suivant scrupuleusement les conventions Symfony 7, mais **n'a pas pu être exécuté ni testé sur cette machine**. Il faudra installer les dépendances et lancer l'application sur votre poste (ou serveur) pour vérifier que tout fonctionne, puis corriger d'éventuelles erreurs mineures (une faute de frappe, une signature de méthode qui a évolué entre versions de Symfony, etc.).

## Prérequis

- PHP 8.2+ avec extensions `ctype`, `iconv`, `pdo_mysql`
- Composer 2
- MySQL/MariaDB (8.0+ recommandé)
- Symfony CLI (optionnel mais pratique) : https://symfony.com/download

## Installation

```bash
cd GERM

# 1. Installer les dépendances (composer.json est déjà prêt)
composer install

# 2. Configurer la base de données
cp .env .env.local
# Éditez .env.local et renseignez DATABASE_URL avec vos identifiants MySQL réels

# 3. Créer la base et générer la première migration à partir des entités
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 4. Créer le compte superadmin
php bin/console app:create-superadmin

# 5. Lancer le serveur de développement
symfony server:start
# ou : php -S 127.0.0.1:8000 -t public
```

Puis ouvrez `http://127.0.0.1:8000/login` et connectez-vous avec le compte superadmin créé à l'étape 4. Vous arriverez sur `/admin`, le tableau de bord superadmin.

## Structure du modèle de données

- **Service** : code, nom, description, actif — représente une direction/service du Ministère (ex: DSI, DRH, DIRCOM).
- **User** : email, mot de passe, nom, prénom, rôles, actif — compte de connexion d'un agent. Peut être lié en 1-1 à une fiche `Personnel`.
- **Personnel** : matricule, nom, prénom, sexe, date de naissance, fonction, grade, type de contrat (fonctionnaire/contractuel/stagiaire/consultant), date d'embauche, statut, téléphone, email, adresse, service, observations.
- **MaterielInformatique** : n° d'inventaire, type, marque, modèle, n° de série, spécifications, date d'acquisition, valeur, fournisseur, garantie, état, service, agent affecté.
- **Vehicule** : immatriculation, type, marque, modèle, n° de châssis, carburant, kilométrage, date d'acquisition, valeur, assurance, visite technique, état, service, chauffeur affecté.

## Prochaines étapes suggérées

- Espace "agent" (hors superadmin) : chaque agent consulte/édite ses propres informations et le matériel qui lui est affecté.
- Historique des affectations (matériel/véhicule) et des mouvements de personnel.
- Export PDF/Excel des fiches et des listes.
- Notifications automatiques sur les échéances (garanties, assurances, visites techniques) déjà calculées côté dashboard.
- Journalisation des actions superadmin (audit trail).

## Limitations connues

- Projet non testé en exécution réelle (voir ci-dessus) : à valider après `composer install` et configuration de la base.
- Pas de gestion de photo/upload de fichiers pour l'instant (le champ `photo` de `Personnel` attend un chemin/URL).
- Pas de tests automatisés fournis dans cette première itération.
# GERM
