# Mini-app PHP de gestion carte fournisseurs

Cette version garde la carte existante dans `index.html` et ajoute une mini-app admin + API en PHP/MySQL.

## Objectif

- La carte lit d'abord la BDD (`/api/index.php?action=map-data`), puis retombe automatiquement sur l'Excel si l'API n'est pas disponible.
- Gestion admin depuis le web (`/admin/index.html`) :
  - créer/modifier clients,
  - créer/modifier utilisateurs client (rôle, activation, reset mot de passe),
  - créer/modifier activités et labels,
  - créer des fournisseurs,
  - importer un Excel massivement,
  - détecter les doublons fournisseurs,
  - gérer les conflits (téléphone/email/activité/type) en choisissant la règle.
- Espace client depuis le web (`/client/index.html`) :
  - connexion avec compte client,
  - consultation des fournisseurs liés au client,
  - édition du profil client par fournisseur (activités, labels, notes, statut relation),
  - soumission de demandes de modification des champs globaux fournisseur (validation admin),
  - suivi des demandes avec filtre par statut et compteur des demandes en attente.

- Espace admin (`/admin/index.html`) :
  - revue des demandes de modification (filtre statut/client, tri),
  - approbation/refus individuel,
  - approbation en lot des demandes filtrées en attente,
  - sélection multiple pour traitement en lot (approuver/refuser),
  - export CSV des demandes filtrées.

## Arborescence

- `index.html` : carte actuelle (modifiée minimalement pour lire la BDD en priorité)
- `database/schema.sql` : schéma MySQL
- `api/config.php` : configuration DB + identifiants admin
- `api/index.php` : API JSON
- `admin/index.html` : interface admin
- `admin/app.js` : logique front admin
- `client/index.html` : interface client
- `client/app.js` : logique front client

## Installation OVH (mutualisé)

1. Crée une base MySQL dans OVH Manager.
2. Importe `database/schema.sql` via phpMyAdmin.
3. Upload le dossier sur ton hébergement (ex: `/www/map/`).
4. Configure les variables d'environnement OVH pour DB + admin (mot de passe hashé):
  - `MAP_ENV=production`
  - `MAP_DB_HOST`, `MAP_DB_PORT`, `MAP_DB_NAME`, `MAP_DB_USER`, `MAP_DB_PASS`
  - `MAP_ADMIN_USER`, `MAP_ADMIN_PASS_HASH`

  Pour générer le hash admin en local:

```powershell
& 'C:\wamp64\bin\php\php8.3.14\php.exe' '.\scripts\generate_admin_password_hash.php' 'TonMotDePasseAdminTresFort'
```

  Puis copie la valeur retournée dans `MAP_ADMIN_PASS_HASH` (ne stocke pas le mot de passe en clair dans le code).
5. Ouvre :
   - carte: `/map/index.html`
   - admin: `/map/admin/index.html`
  - client: `/map/client/index.html`

## Sécurité minimale

- Utilise uniquement `MAP_ADMIN_PASS_HASH` pour le compte admin (pas de mot de passe en clair dans le dépôt).
- Si possible, protège aussi `/admin` par IP via `.htaccess`.
- Idéalement, déplacer les secrets vers des variables d'environnement OVH.

## Checklist de mise en prod

- Suivre `PROD_CHECKLIST.md` pour la sequence complete preprod -> prod.

## Gestion des admins

- Le compte admin défini via `MAP_ADMIN_USER` + `MAP_ADMIN_PASS_HASH` reste le compte de secours (recommandé).
- Tu peux ensuite créer d'autres admins dans l'interface: onglet `Paramètres` > bloc `Utilisateurs admin`.
- Les mots de passe admin sont stockés en hash (jamais en clair) et doivent être forts (12 caractères min).

## Préparation production (images / URLs)

Avant export final, vérifie les assets:

1. Localiser les médias externes dans `assets/`:

```powershell
& 'C:\wamp64\bin\php\php8.3.14\php.exe' '.\scripts\localize_media.php'
```

2. Basculer les chemins en mode prod (`/Carte/assets/...`):

```powershell
powershell -ExecutionPolicy Bypass -File '.\scripts\switch_assets_mode.ps1' -Mode prod
```

3. Vérifier que les URLs exportées pointent bien vers `https://limap.fr/Carte/...` dans l'admin (réglage base URL publique).

## Export / import base vers OVH

1. Export local:

```powershell
& 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqldump.exe' --default-character-set=utf8mb4 -uroot appcarte > '.\backups\appcarte_preprod.sql'
```

2. Import OVH (depuis un poste ayant accès à l'hôte OVH MySQL):

```powershell
& 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe' --default-character-set=utf8mb4 -h <OVH_HOST> -P <OVH_PORT> -u <OVH_USER> -p <OVH_DB_NAME> < '.\backups\appcarte_preprod.sql'
```

3. Contrôle post-import:
  - ouvrir `/admin/index.html` et `/client/index.html`
  - vérifier quelques logos/icônes
  - tester login admin + login client

## Format Excel import

La feuille peut s'appeler `Fournisseurs` (recommandé) sinon la première feuille est utilisée.

Colonnes reconnues (tolérance FR/EN) :

- `Nom` / `Name`
- `Activité` / `Activity`
- `Label`
- `Type`
- `Téléphone` / `Tel` / `Phone`
- `Email`
- `Adresse` / `Address`
- `Ville` / `City`
- `Code postal` / `Postal Code`
- `Pays` / `Country`
- `Latitude`
- `Longitude`

## Notes

- Le matching fournisseur est fait par priorité: `email` puis `téléphone` puis `nom normalisé + ville`.
- En cas de conflit, la prévisualisation propose un choix `garder existant` ou `remplacer par import`.

## Test local (Windows + WAMP)

1. Démarre WAMP et vérifie que MySQL est actif.
2. Crée la base locale :

```powershell
& 'C:\wamp64\bin\mysql\mysql5.7.36\bin\mysql.exe' -uroot -e "CREATE DATABASE IF NOT EXISTS appcarte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

3. Importe le schéma :

```powershell
Get-Content -Raw '.\database\schema.sql' | & 'C:\wamp64\bin\mysql\mysql5.7.36\bin\mysql.exe' -uroot appcarte
```

4. Lance le serveur PHP local (PHP 7.4+):

```powershell
& 'C:\wamp64\bin\php\php8.0.13\php.exe' -S 127.0.0.1:8080 -t 'C:\Users\lordb\Documents\AppCarteLimap'
```

5. Ouvre :
  - Carte : `http://127.0.0.1:8080/index.html`
  - Admin : `http://127.0.0.1:8080/admin/index.html`
  - Client : `http://127.0.0.1:8080/client/index.html`

Identifiants admin:

- utilisateur via `MAP_ADMIN_USER`
- mot de passe via hash `MAP_ADMIN_PASS_HASH`