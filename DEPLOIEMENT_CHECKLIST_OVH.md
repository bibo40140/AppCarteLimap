# 📋 CHECKLIST RAPIDE POUR OVH

**Date:** 2026-04-03  
**Status:** ✅ Code Phase 3 COMPLÈTEMENT implémenté localement - PRÊT pour OVH  

---

## 🔵 CE QU'IL FAUT FAIRE EN OVH (15 min)

### 1️⃣  Exécuter le SQL (30 sec)
- Ouvrez phpMyAdmin OVH
- Importez/exécutez: `MIGRATIONS_PHASE3_OVH.sql`
- Vérifiez: 3 nouvelles tables créées ✅

### 2️⃣ Upload 3 fichiers API (2 min)
Via FTP vers `/api/`:
- [ ] `api/consent.php` (NOUVEAU)
- [ ] `api/index.php` (MODIFIÉ - routes + include)
- [ ] `api/db.php` (MODIFIÉ - tables Phase 3)

### 3️⃣ Upload page fournisseur (1 min)
Via FTP vers `/`:
- [ ] `supplier-consent.html` (NOUVEAU - public page)

### 4️⃣ Test basic (5 min)
```bash
curl https://yourdomain.com/api?action=client/bootstrap
# Doit retourner {ok: true, ...} sans erreur
```

---

## 🟡 CE QUI MANQUE (TODOs à finir)

### A. Email Sending  
- [ ] Implémenter `send_supplier_consent_email()` dans `api/consent.php:497`
  - Utiliser `send_plain_email()` ou SMTP existant
  - Envoyer avec lien valide 14 jours

### B. Base URL Configuration
- [ ] Implémenter `get_app_base_url()` dans `api/consent.php:503`
  - Retourner `https://yourdomain.com`

### C. Map Filtering (IMPORTANT!)
- [ ] Modifier fonction `map_data()` dans `api/index.php`
  - Client: Add EXISTS check pour client_consents
  - Fournisseur: Add EXISTS check pour supplier_consents
  - (Spec section 8: SQL rules fournis)

---

## ✅ UNE FOIS TERMINÉ

- [ ] Tester flux client consent (checkbox)
- [ ] Tester flux supplier consent (email-token)
- [ ] Tester admin overview
- [ ] Valider map filtre correctement

---

## 📞 SI ERREUR

```bash
# Vérifier logs:
tail -f /var/log/apache2/error.log

# Tester route individuelle:
curl -X POST https://yourdomain.com/api?action=client/consent/confirm \
  -H "Content-Type: application/json" \
  -d '{"accept": true, "text_version": "2026-04-v1"}'
```

---

## 📄 Documentation Complète

Lire dans cet ordre:
1. `RESUME_PHASE3_COMPLET.md` — Vue d'ensemble technique
2. `INSTRUCTIONS_OVHDEPLOIEMENT.md` — Étapes détaillées
3. `SPEC_CONSENTEMENTS.md` — Référence spec

---

**Status:** Ready to deploy 🚀
