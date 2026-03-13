# Checklist Mise En Production

## 1. Prerequis infra

- [ ] Domaine et HTTPS actifs (certificat valide)
- [ ] Base OVH creee et accessible
- [ ] Sauvegarde locale finale effectuee

## 2. Variables d'environnement (obligatoire)

- [ ] `MAP_ENV=production`
- [ ] `MAP_DB_HOST`
- [ ] `MAP_DB_PORT`
- [ ] `MAP_DB_NAME`
- [ ] `MAP_DB_USER`
- [ ] `MAP_DB_PASS`
- [ ] `MAP_ADMIN_USER`
- [ ] `MAP_ADMIN_PASS_HASH`

## 3. Donnees

- [ ] Export SQL final local (`backups/appcarte_preprod.sql`)
- [ ] Import SQL sur OVH
- [ ] Verification des tables critiques:
  - `admin_users`
  - `password_reset_tokens`
  - `password_reset_audit`
- [ ] Au moins un admin actif avec email

## 4. Assets / URLs

- [ ] Assets localises (`scripts/localize_media.php`)
- [ ] Mode assets prod active (`scripts/switch_assets_mode.ps1 -Mode prod`)
- [ ] `public_assets_base_url` configure sur URL finale (ex: `https://limap.fr/Carte`)

## 5. SMTP / Emails

- [ ] SMTP configure dans Parametres
- [ ] Mail test recu depuis l'admin
- [ ] Envoi lien reset recu
- [ ] Clic lien reset + changement mot de passe valide
- [ ] Verification dossier Spam/Promotions

## 6. Verification fonctionnelle

- [ ] Login admin OK
- [ ] Login client OK
- [ ] Import Excel OK
- [ ] Carte publique charge les donnees BDD
- [ ] Upload logo client et icone activite OK

## 7. Securite minimale

- [ ] Comptes de test desactives/supprimes
- [ ] Mots de passe admin forts
- [ ] Dossiers sensibles non accessibles en HTTP (`backups`, `database`, `scripts`)
- [ ] Execution de scripts desactivee dans `uploads`

## 8. Go-Live

- [ ] Smoke test final (admin + client + reset)
- [ ] Snapshot base post-deploiement
- [ ] Validation metier finale

## Commandes utiles

### Export SQL local

```powershell
& 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqldump.exe' --default-character-set=utf8mb4 -uroot appcarte > '.\backups\appcarte_preprod.sql'
```

### Import SQL OVH

```powershell
& 'C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql.exe' --default-character-set=utf8mb4 -h <OVH_HOST> -P <OVH_PORT> -u <OVH_USER> -p <OVH_DB_NAME> < '.\backups\appcarte_preprod.sql'
```
