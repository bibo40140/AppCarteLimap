# Spécification — Module Consentements Simplifié

> Document de référence pour la nouvelle implémentation du module de consentement.
> Cette version remplace entièrement l'approche par PDF, supprime le Mode A et supprime la validation admin.

---

## 1. Objectif

Le système doit reposer sur deux règles simples :

1. Un client apparaît publiquement uniquement s'il a validé explicitement son propre consentement.
2. Un fournisseur apparaît publiquement uniquement s'il a validé lui-même son consentement via un lien reçu par email.

Le but est de réduire la friction, simplifier les règles métier et conserver une preuve technique exploitable de chaque consentement.

---

## 2. Principes fonctionnels

### 2.1 Consentement client

Le consentement client est donné depuis l'espace client.

Le client doit :
- lire un texte clair de consentement
- cocher une case non pré-cochée
- confirmer dans une seconde étape explicite

Exemple de flux :
1. case à cocher : "J'ai lu et j'accepte le texte ci-dessous"
2. bouton de confirmation : "Je confirme mon consentement"

Ce consentement ne nécessite aucune validation admin.

### 2.2 Consentement fournisseur

Le consentement fournisseur n'est plus géré par PDF et n'est plus géré par le client au nom du fournisseur.

Le seul mode valable devient :
- envoi d'un email au fournisseur
- ouverture d'un lien sécurisé à usage individuel
- validation explicite du fournisseur sur une page dédiée

Le fournisseur peut être sollicité depuis plusieurs clients, mais dès qu'il a validé son consentement directement, son statut est considéré comme validé globalement.

### 2.3 Suppression du Mode A

Le Mode A est supprimé.

Il n'existe plus de consentement global d'un client couvrant tous ses fournisseurs.

Conséquence :
- un client ne peut consentir que pour lui-même
- un fournisseur ne peut consentir que pour lui-même

Cette règle doit rester stricte dans toute l'application.

### 2.4 Suppression de la validation admin

L'admin ne valide plus les consentements.

Son rôle devient :
- suivre les statuts
- visualiser l'historique
- relancer si nécessaire
- éventuellement révoquer ou invalider un consentement en cas d'erreur ou de demande explicite

---

## 3. Règles de visibilité publique

Ces règles s'appliquent aux visiteurs non authentifiés.

### 3.1 Client visible sur la carte

Un client est visible si :
- `is_active = 1`
- son consentement client est validé

### 3.2 Fournisseur visible sur la carte

Un fournisseur est visible si :
- il est lié à au moins un client
- son consentement fournisseur direct est validé

Il n'existe plus aucune exception liée à un consentement global client.

---

## 4. Modèle de données

Le modèle doit être orienté preuve de consentement et traçabilité.

### 4.1 Table `client_consents`

Trace le consentement explicite du client pour sa propre fiche.

Colonnes recommandées :

| Colonne | Type | Notes |
|---------|------|-------|
| `id` | BIGINT AUTO_INCREMENT PK | |
| `client_id` | INT NOT NULL | client concerné |
| `status` | VARCHAR(20) NOT NULL | `approved`, `revoked` |
| `consent_text_version` | VARCHAR(40) NOT NULL | version fonctionnelle du texte affiché |
| `consent_text_snapshot` | TEXT NOT NULL | texte exact accepté |
| `consent_text_hash` | VARCHAR(64) NOT NULL | hash SHA-256 du texte affiché |
| `accepted_by_user_id` | INT NULL | utilisateur client ayant validé |
| `accepted_by_name` | VARCHAR(120) NULL | username ou nom affichable |
| `accepted_at` | DATETIME NOT NULL | date de validation |
| `accepted_ip` | VARCHAR(64) NULL | IP de validation |
| `accepted_user_agent` | VARCHAR(255) NULL | user agent |
| `revoked_at` | DATETIME NULL | date de retrait |
| `revoked_by_type` | VARCHAR(20) NULL | `client`, `admin`, `system` |
| `revoked_by_id` | INT NULL | |
| `revoke_reason` | TEXT NULL | |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | |

Index recommandés :
- `client_id`
- `status`
- `accepted_at`

Règle métier :
- on conserve un historique complet, pas seulement un booléen dans `clients`

### 4.2 Table `supplier_consent_requests`

Trace les demandes envoyées par email aux fournisseurs.

Colonnes recommandées :

| Colonne | Type | Notes |
|---------|------|-------|
| `id` | BIGINT AUTO_INCREMENT PK | |
| `supplier_id` | INT NOT NULL | fournisseur cible |
| `source_client_id` | INT NOT NULL | client à l'origine de la sollicitation |
| `recipient_email` | VARCHAR(190) NOT NULL | email utilisé pour l'envoi |
| `request_token_hash` | VARCHAR(255) NOT NULL | hash du token envoyé par email |
| `status` | VARCHAR(20) NOT NULL | `sent`, `opened`, `approved`, `rejected`, `expired`, `cancelled` |
| `consent_text_version` | VARCHAR(40) NOT NULL | version du texte envoyé |
| `consent_text_snapshot` | TEXT NOT NULL | texte exact présenté au fournisseur |
| `consent_text_hash` | VARCHAR(64) NOT NULL | hash SHA-256 du texte |
| `requested_at` | DATETIME NOT NULL | date d'envoi |
| `opened_at` | DATETIME NULL | date d'ouverture du lien |
| `answered_at` | DATETIME NULL | date de réponse |
| `answer_ip` | VARCHAR(64) NULL | IP de réponse |
| `answer_user_agent` | VARCHAR(255) NULL | user agent |
| `expires_at` | DATETIME NOT NULL | expiration du lien |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | |

Index recommandés :
- `supplier_id`
- `source_client_id`
- `status`
- `requested_at`
- `expires_at`
- `request_token_hash` unique

### 4.3 Table `supplier_consents`

Trace l'état global effectif du consentement fournisseur.

Colonnes recommandées :

| Colonne | Type | Notes |
|---------|------|-------|
| `id` | BIGINT AUTO_INCREMENT PK | |
| `supplier_id` | INT NOT NULL | fournisseur concerné |
| `approved_from_request_id` | BIGINT NOT NULL | demande ayant mené à l'accord |
| `status` | VARCHAR(20) NOT NULL | `approved`, `revoked` |
| `consent_text_version` | VARCHAR(40) NOT NULL | version acceptée |
| `consent_text_snapshot` | TEXT NOT NULL | texte exact accepté |
| `consent_text_hash` | VARCHAR(64) NOT NULL | hash SHA-256 |
| `approved_at` | DATETIME NOT NULL | date d'accord |
| `approved_ip` | VARCHAR(64) NULL | |
| `approved_user_agent` | VARCHAR(255) NULL | |
| `revoked_at` | DATETIME NULL | |
| `revoked_by_type` | VARCHAR(20) NULL | `supplier`, `admin`, `system` |
| `revoked_by_id` | INT NULL | |
| `revoke_reason` | TEXT NULL | |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | |

Index recommandés :
- `supplier_id`
- `status`
- `approved_at`

Règle métier :
- un consentement fournisseur validé est global
- il bénéficie à tous les clients liés à ce fournisseur

### 4.4 Option possible : table `legal_texts`

Si l'on veut versionner proprement les textes juridiques dans l'admin.

Colonnes possibles :

| Colonne | Type | Notes |
|---------|------|-------|
| `id` | BIGINT AUTO_INCREMENT PK | |
| `text_key` | VARCHAR(40) NOT NULL | `client_consent`, `supplier_consent` |
| `version` | VARCHAR(40) NOT NULL | ex. `2026-04-v1` |
| `title` | VARCHAR(190) NOT NULL | |
| `body_html` | LONGTEXT NOT NULL | texte affiché |
| `body_hash` | VARCHAR(64) NOT NULL | hash du texte |
| `is_active` | TINYINT(1) NOT NULL DEFAULT 1 | |
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | |

Cette table est optionnelle mais recommandée.

---

## 5. Routes API à ajouter

### 5.1 Routes client

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `client/bootstrap` | retourne les données client, les fournisseurs et les statuts de consentement |
| POST | `client/consent/confirm` | enregistre le consentement client après double confirmation |
| POST | `client/consent/revoke` | retire le consentement client |
| POST | `client/supplier-consent/send` | envoie une demande à un fournisseur précis |
| POST | `client/supplier-consent/send-bulk` | envoie aux fournisseurs non encore validés |
| GET | `client/supplier-consent/history` | retourne l'historique des envois pour le client |

### 5.2 Routes publiques fournisseur

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `supplier/consent/view` | affiche les informations liées à un token de consentement |
| POST | `supplier/consent/approve` | validation du fournisseur via token |
| POST | `supplier/consent/reject` | refus du fournisseur via token |

### 5.3 Routes admin

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `admin/consent-overview` | tableau de bord global des consentements clients et fournisseurs |
| POST | `admin/supplier-consent/resend` | relance d'un fournisseur |
| POST | `admin/client-consent/revoke` | révocation admin d'un consentement client |
| POST | `admin/supplier-consent/revoke` | révocation admin d'un consentement fournisseur |

---

## 6. Logique métier détaillée

### 6.1 Consentement client

Le client voit un texte fixe ou versionné.

Il doit :
1. cocher une case indiquant qu'il a lu le texte
2. cliquer sur un bouton de confirmation

Le backend enregistre :
- le client concerné
- l'utilisateur client ayant validé
- la date/heure
- l'IP
- le user agent
- la version du texte
- le texte exact affiché
- le hash du texte

Si le client revalide plus tard après changement de texte, on crée une nouvelle ligne d'historique.

### 6.2 Envoi au fournisseur

Depuis l'espace client, l'utilisateur peut :
- envoyer une demande à un fournisseur précis
- envoyer à tous les fournisseurs non encore validés

Règles :
- ne pas envoyer si le fournisseur a déjà un consentement direct validé
- on peut relancer un fournisseur si sa demande précédente est encore `sent`, `opened`, `expired` ou `rejected`
- si plusieurs clients déclenchent des envois, toutes les demandes restent historisées

### 6.3 Validation fournisseur

Le fournisseur reçoit un email contenant :
- le nom du client à l'origine de la relation
- une explication claire
- un lien sécurisé

En cliquant sur le lien, il arrive sur une page simple affichant :
- son nom
- le client concerné
- le texte de consentement
- un bouton "J'accepte"
- un bouton "Je refuse"

Si le fournisseur accepte :
- la demande passe à `approved`
- une ligne est créée dans `supplier_consents`
- le fournisseur est désormais considéré comme validé globalement

Si le fournisseur refuse :
- la demande passe à `rejected`
- aucune visibilité publique n'est accordée

### 6.4 Révocation

Il faut prévoir la possibilité de retrait ou d'invalidation.

Deux cas :
- retrait par le titulaire du consentement
- révocation admin exceptionnelle

Le retrait ne supprime jamais l'historique.

---

## 7. Modifications dans `client/bootstrap`

La réponse doit contenir au minimum :

```json
{
  "client_consent": {
    "status": "approved",
    "accepted_at": "2026-04-03 11:00:00",
    "version": "2026-04-v1"
  },
  "suppliers": [
    {
      "id": 12,
      "name": "Bio Exemple",
      "supplier_consent_status": "approved",
      "supplier_consent_requested_at": "2026-04-03 10:00:00",
      "supplier_consent_answered_at": "2026-04-03 15:00:00",
      "supplier_consent_source_client_name": "Coopaz",
      "supplier_consent_can_send": false,
      "supplier_consent_can_resend": false
    }
  ]
}
```

Champs utiles par fournisseur :
- `supplier_consent_status` : `none`, `sent`, `opened`, `approved`, `rejected`, `expired`
- `supplier_consent_requested_at`
- `supplier_consent_answered_at`
- `supplier_consent_source_client_name`
- `supplier_consent_can_send`
- `supplier_consent_can_resend`

---

## 8. Règles SQL de visibilité publique

### 8.1 Client

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

### 8.2 Fournisseur

```sql
EXISTS (
  SELECT 1
  FROM supplier_consents sc
  WHERE sc.supplier_id = s.id
    AND sc.status = 'approved'
    AND sc.revoked_at IS NULL
)
```

Il n'y a plus aucune logique supplémentaire liée à un consentement global client.

---

## 9. Interface client

### 9.1 Onglet "Consentements"

Ajouter un onglet dédié dans l'espace client.

Contenu :

#### Bloc 1 — Consentement du client
- texte affiché
- case à cocher
- bouton de confirmation
- affichage du statut actuel
- affichage de la date de validation
- éventuellement bouton de retrait

#### Bloc 2 — Consentement des fournisseurs
- bouton "Envoyer à tous les fournisseurs non validés"
- liste de tous les fournisseurs liés
- pour chaque fournisseur :
  - nom
  - email
  - statut de consentement
  - date dernier envoi
  - date de réponse
  - bouton "Envoyer" si jamais sollicité
  - bouton "Relancer" si déjà sollicité mais non validé
  - bouton désactivé si consentement déjà validé directement

Exemples de statuts UI :
- `A envoyer`
- `Mail envoye`
- `Lien ouvert`
- `Valide`
- `Refuse`
- `Expire`

---

## 10. Interface fournisseur publique

Créer une page simple accessible par token.

Exemple :
- titre clair
- rappel du client lié
- rappel du fournisseur concerné
- texte de consentement
- bouton "J'accepte"
- bouton "Je refuse"

Contraintes :
- page très simple
- lisible sur mobile
- un seul objectif par écran
- lien non réutilisable indéfiniment si expiré

---

## 11. Interface admin

Remplacer la logique de validation admin par un tableau de suivi.

### 11.1 Vue client

Colonnes utiles :
- client
- statut consentement
- validé le
- version du texte
- action éventuelle : voir l'historique, révoquer

### 11.2 Vue fournisseur

Colonnes utiles :
- fournisseur
- clients liés
- email ciblé
- statut actuel
- date dernier envoi
- date réponse
- source du consentement validé
- actions : relancer, voir l'historique, révoquer

L'admin ne doit pas avoir de bouton "Approuver / Refuser".

---

## 12. Emails fournisseur

Le mail doit être simple et clair.

Contenu minimal :
- objet explicite
- nom du client
- raison de la demande
- lien de validation
- durée de validité du lien

Exemple d'objet :
- `Demande de consentement pour affichage sur la carte LIMAP`

Exemple de règle technique :
- durée de validité d'un lien : 14 jours
- possibilité de renvoyer un nouveau lien, qui invalide l'ancien lien encore actif pour le même couple `(supplier_id, source_client_id)` si souhaité

---

## 13. Preuves et traçabilité

Le système doit permettre de prouver :
- qui a consenti
- quand
- depuis quelle IP
- avec quel user agent
- sur quelle version de texte
- avec quel texte exact affiché

Le système ne doit jamais se limiter à un simple booléen `is_consented=1` sans historique.

---

## 14. Fichiers à modifier dans l'application

### Backend
- `database/schema.sql`
- `api/db.php`
- `api/index.php`
- éventuellement `api/helpers.php` si l'on factorise la génération des liens, emails et snapshots de texte

### Frontend client
- `client/index.html`
- `client/app.js`
- `client/styles.css`

### Frontend admin
- `admin/index.html`
- `admin/app.js`
- éventuellement `admin/styles.css`

### Nouvelle page publique fournisseur
- soit une nouvelle route HTML dédiée
- soit une route pilotée par `index.php` selon l'architecture retenue

---

## 15. Décisions produit retenues

Décisions fermes pour cette version :

1. plus de PDF
2. plus de signature de document
3. plus de Mode A
4. plus de validation admin
5. consentement client uniquement par checkbox + confirmation explicite
6. consentement fournisseur uniquement par email + lien sécurisé
7. un fournisseur devient visible uniquement après son propre accord explicite

---

## 16. Plan d'implémentation recommandé

Ordre conseillé :

1. remplacer la spec actuelle par cette version
2. supprimer du code toute la logique PDF / upload / review admin / Mode A
3. créer les nouvelles tables de consentement et de demandes email
4. implémenter le consentement client
5. implémenter l'envoi d'email fournisseur
6. implémenter la page publique de validation fournisseur
7. adapter `client/bootstrap`
8. adapter `map-data`
9. ajouter le tableau de suivi admin
10. tester le flux complet en local puis sur OVH

---

## 17. Critère d'acceptation final

Le module sera considéré comme correct quand :

1. un client peut valider son consentement depuis son espace sans PDF
2. un fournisseur peut recevoir un mail puis valider son consentement via un lien
3. un fournisseur déjà validé n'est plus sollicité inutilement
4. l'admin ne valide rien manuellement mais voit tous les statuts
5. la carte publique n'affiche que :
   - les clients consentis
   - les fournisseurs consentis directement
6. toute validation est historisée proprement
