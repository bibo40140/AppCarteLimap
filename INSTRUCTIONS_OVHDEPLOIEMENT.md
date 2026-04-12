---
title: Instructions de Déploiement Phase 3 - Consentement Simplifié
date: 2026-04-03
status: PRÊT POUR OVH
---

# Instructions de Déploiement Phase 3 — Consentement Simplifié

## 📋 Résumé

Phase 3 remplace entièrement le système de consentement Phase 1 (basé sur uploads PDF):

| Aspect | Phase 1 (Ancien) | Phase 3 (Nouveau) |
|--------|------------------|-------------------|
| Client | Upload PDF (charter) | Checkbox + Confirmation |
| Fournisseur | Mode A + Mode B (PDF uploads) | Email-token uniquement |
| Admin | Validation manuelle | Monitoring + relance |
| Mode A | Existe | SUPPRIMÉ |
| Tables | `client_consent_documents`, `supplier_consent_documents` | `client_consents`, `supplier_consent_requests`, `supplier_consents` |

---

## 🔧 Étapes de Déploiement

### 1. Sauvegarde (✅ DÉJÀ FAIT)
- Base OVH sauvegardée
- Code OVH sauvegardé sur prod

### 2. Exécuter les Migrations SQL

Connectez-vous à phpMyAdmin OVH et exécutez le fichier:
- `MIGRATIONS_PHASE3_OVH.sql`

**Actions:**
- ✅ Crée 3 nouvelles tables (client_consents, supplier_consent_requests, supplier_consents)
- ⚠️ NE supprime PAS les anciennes tables (conservation pour audit)
- ⚠️ Vérifiez que les indexes sont créés correctement

**Durée estimée:** < 1 seconde

### 3. Déployer le Code OVH

Transférez les fichiers modifiés via FTP/SFTP:

```
api/
  ├── index.php          (routes + changements de routes)
  ├── db.php             (tables Phase 3, pas Phase 1)
  ├── consent.php        (NOUVEAU - toutes les fonctions Phase 3)
```

**Vérifications:**
- [ ] `consent.php` créé et uploadé
- [ ] `index.php` inclut `require __DIR__ . '/consent.php';`
- [ ] `db.php` crée bien les 3 tables Phase 3 (pas Phase 1)

### 4. Créer une Page Publique Fournisseur (Optionnel pour MVP)

Pour MVP, vous pouvez utiliser une page HTML estatique simple:

**Fichier:** `supplier-consent.html`

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Consentement Fournisseur - LIMAP</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        button { padding: 10px 20px; margin: 10px 5px 0 0; cursor: pointer; }
        .accept { background: #4CAF50; color: white; }
        .reject { background: #f44336; color: white; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Demande de Consentement LIMAP</h1>
        <div id="loading">Chargement...</div>
        <div id="content" style="display:none;">
            <p><strong>Client:</strong> <span id="clientName"></span></p>
            <p><strong>Fournisseur:</strong> <span id="supplierName"></span></p>
            <div id="consentText" style="border: 1px solid #eee; padding: 15px; margin: 20px 0; background: #f9f9f9;"></div>
            <button class="accept" onclick="approveConsent()">J'accepte</button>
            <button class="reject" onclick="rejectConsent()">Je refuse</button>
        </div>
        <div id="error" style="color: red; display:none;"></div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (!token) {
            document.getElementById('error').textContent = 'Lien invalide (token manquant)';
            document.getElementById('error').style.display = 'block';
            document.getElementById('loading').style.display = 'none';
        } else {
            // Load consent details
            fetch('/api?action=supplier/consent/view&token=' + encodeURIComponent(token))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    if (data.ok) {
                        const req = data.request;
                        document.getElementById('clientName').textContent = req.client_name;
                        document.getElementById('supplierName').textContent = req.supplier_name;
                        document.getElementById('consentText').innerHTML = req.consent_text.replace(/\n/g, '<br>');
                        document.getElementById('content').style.display = 'block';
                        window.currentToken = req.token;
                    } else {
                        document.getElementById('error').textContent = data.error || 'Erreur inconnue';
                        document.getElementById('error').style.display = 'block';
                    }
                })
                .catch(err => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('error').textContent = 'Erreur réseau: ' + err.message;
                    document.getElementById('error').style.display = 'block';
                });
        }

        function approveConsent() {
            fetch('/api?action=supplier/consent/approve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: window.currentToken })
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    alert('Merci - Votre accord a été enregistré');
                    window.location.href = '/';
                } else {
                    alert('Erreur: ' + (data.error || 'Inconnue'));
                }
            });
        }

        function rejectConsent() {
            fetch('/api?action=supplier/consent/reject', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: window.currentToken })
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    alert('Votre refus a été enregistré');
                    window.location.href = '/';
                } else {
                    alert('Erreur: ' + (data.error || 'Inconnue'));
                }
            });
        }
    </script>
</body>
</html>
```

### 5. Tester en Prod

#### 5.1 Vérifier que les tables existent
- Activez API bootstrap: `/api?action=client/bootstrap`
- Vérifiez qu'aucun erreur ne remonte

#### 5.2 Tester consentement client simple
- POST `/api?action=client/consent/confirm`
- Body: `{ "accept": true, "text_version": "2026-04-v1" }`
- Attendez: `{ "ok": true }`

#### 5.3 Tester envoi fournisseur
- POST `/api?action=client/supplier-consent/send`
- Body: `{ "supplier_id": 123 }`
- Attendez: `{ "ok": true }` (email non envoyé en MVP)

---

## ⚠️ Points d'Attention

### Email Sending (TODO)

Les fonctions stub `send_supplier_consent_email()` et `get_app_base_url()` doivent être implémentées:

**dans `api/consent.php` lignes 263-275 et 497-503**

```php
// À implémenter selon config OVH
function send_supplier_consent_email(...) {
    // Utiliser send_plain_email() ou send_email_smtp() depuis helpers.php
}

function get_app_base_url(PDO $pdo): string {
    // Retourner 'https://...' depuis settings ou $_SERVER
}
```

### Texte du Consentement (TODO)

Fonction `get_consent_text_snapshot()` (lignes 454-493):
- Actuellement hardcodée en MVP
- À migrer vers table `legal_texts` pour versionning propre

### Historique Phase 1

Les anciennes tables PDF restent pour audit:
- `client_consent_documents`
- `supplier_consent_documents`

À supprimer après validation en prod (optionnel).

---

## 📊 Règles de Visibilité Mises à Jour

**Dans fonction `map_data()` de `api/index.php`:**

### Client visible si :
```sql
c.is_active = 1
AND EXISTS (
  SELECT 1
  FROM client_consents cc
  WHERE cc.client_id = c.id
    AND cc.status = 'approved'
    AND cc.revoked_at IS NULL
)
```

### Fournisseur visible si :
```sql
EXISTS (
  SELECT 1
  FROM supplier_consents sc
  WHERE sc.supplier_id = s.id
    AND sc.status = 'approved'
    AND sc.revoked_at IS NULL
)
```

⚠️ Ces règles ne sont **PAS** modifiées en Phase 3 code (à faire dans étape de test final).

---

## 🔄 Rollback Plan

Si problème après déploiement:

1. **Restaurer la base OVH** (sauvegarde du 2026-04-02)
2. **Restaurer le code OVH** (push ancien code)
3. **Contacter support** pour aide

Les données Phase 3 dans les 3 nouvelles tables seront perdues (c'est acceptable pour MVP).

---

## ✅ Checklist de Validation

Après déploiement en OVH:

- [ ] Migration SQL exécutée sans erreurs
- [ ] 3 tables créées (`client_consents`, `supplier_consent_requests`, `supplier_consents`)
- [ ] `consent.php` uploadé
- [ ] `index.php` reloade `consent.php`
- [ ] Routes `/client/consent/*` répondent
- [ ] Routes `/admin/consent-*` répondent
- [ ] Routes publiques `/supplier/consent/*` répondent
- [ ] Aucun erreur 500 sur bootstrap
- [ ] Map filtringe mets à jour (TODO: dernier jour)
- [ ] Email sending configuré (TODO: dernier jour)

---

## 📞 Support

En cas d'erreur:
- Vérifiez les logs Apache/PHP `/var/log/`
- Testez directement chaque route via Postman/curl
- Vérifiez caractères UTF-8 en base (utf8mb4)

---

**Document généré:** 2026-04-03  
**Version spec:** SPEC_CONSENTEMENTS.md v1 (checkbox + email)  
**Statut:** Ready for OVH Prod
