# Mini-app PHP de gestion carte fournisseurs

Cette version garde la carte existante dans `index.html` et ajoute une mini-app admin + API en PHP/MySQL.

## Objectif

- La carte lit d'abord la BDD (`/api/index.php?action=map-data`), puis retombe automatiquement sur l'Excel si l'API n'est pas disponible.
- Gestion admin depuis le web (`/admin/index.html`) :
  - créer/modifier clients,
  - créer/modifier activités et labels,
  - créer des fournisseurs,
  - importer un Excel massivement,
  - détecter les doublons fournisseurs,
  - gérer les conflits (téléphone/email/activité/type) en choisissant la règle.

## Arborescence

- `index.html` : carte actuelle (modifiée minimalement pour lire la BDD en priorité)
- `database/schema.sql` : schéma MySQL
- `api/config.php` : configuration DB + identifiants admin
- `api/index.php` : API JSON
- `admin/index.html` : interface admin
- `admin/app.js` : logique front admin

## Installation OVH (mutualisé)

1. Crée une base MySQL dans OVH Manager.
2. Importe `database/schema.sql` via phpMyAdmin.
3. Upload le dossier sur ton hébergement (ex: `/www/map/`).
4. Édite `api/config.php` avec les accès MySQL OVH et un mot de passe admin fort.
5. Ouvre :
   - carte: `/map/index.html`
   - admin: `/map/admin/index.html`

## Sécurité minimale

- Change immédiatement le mot de passe admin dans `api/config.php`.
- Si possible, protège aussi `/admin` par IP via `.htaccess`.
- Idéalement, déplacer les secrets vers des variables d'environnement OVH.

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

Identifiants admin par défaut :

- utilisateur : `admin`
- mot de passe : `ChangeMeNow!2026`

À modifier dans `api/config.php`.