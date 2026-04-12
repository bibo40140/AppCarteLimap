-- ============================================================================
-- MIGRATIONS PHASE 3 - Consentement Simplifié (Checkbox + Email Token)
-- ============================================================================
--
-- À EXÉCUTER EN PROD OVH (Après sauvegarde base + code)
--
-- Nouveaux modèles :
--   - client_consents: historique consentement client
--   - supplier_consent_requests: demandes email fournisseur
--   - supplier_consents: état global validé fournisseur
--
-- Suppression des anciennes tables PDF/Mode A (optionnel, si présentes):
--   - client_consent_documents
--   - supplier_consent_documents
--
-- ============================================================================

-- Step 1 : (OPTIONNEL) Supprimer les anciennes tables Phase 1 si elles existent
-- Décommentez ces lignes si les tables PDF existent déjà en OVH
-- DROP TABLE IF EXISTS supplier_consent_documents;
-- DROP TABLE IF EXISTS client_consent_documents;

-- Step 2 : Créer les 3 nouvelles tables Phase 3

-- Table 2a : Historique consentement client
CREATE TABLE IF NOT EXISTS client_consents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'approved',
  consent_text_version VARCHAR(40) NOT NULL,
  consent_text_snapshot LONGTEXT NOT NULL,
  consent_text_hash VARCHAR(64) NOT NULL,
  accepted_by_user_id INT NULL,
  accepted_by_name VARCHAR(120) NULL,
  accepted_at DATETIME NOT NULL,
  accepted_ip VARCHAR(64) NULL,
  accepted_user_agent VARCHAR(255) NULL,
  revoked_at DATETIME NULL,
  revoked_by_type VARCHAR(20) NULL,
  revoked_by_id INT NULL,
  revoke_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_client_consents_client (client_id),
  INDEX idx_client_consents_status (status),
  INDEX idx_client_consents_accepted_at (accepted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table 2b : Demandes email aux fournisseurs
CREATE TABLE IF NOT EXISTS supplier_consent_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  source_client_id INT NOT NULL,
  recipient_email VARCHAR(190) NOT NULL,
  request_token_hash VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'sent',
  consent_text_version VARCHAR(40) NOT NULL,
  consent_text_snapshot LONGTEXT NOT NULL,
  consent_text_hash VARCHAR(64) NOT NULL,
  requested_at DATETIME NOT NULL,
  opened_at DATETIME NULL,
  answered_at DATETIME NULL,
  answer_ip VARCHAR(64) NULL,
  answer_user_agent VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_supplier_consent_requests_supplier (supplier_id),
  INDEX idx_supplier_consent_requests_source (source_client_id),
  INDEX idx_supplier_consent_requests_status (status),
  INDEX idx_supplier_consent_requests_requested (requested_at),
  INDEX idx_supplier_consent_requests_expires (expires_at),
  UNIQUE INDEX idx_supplier_consent_requests_token (request_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table 2c : État global validé du consentement fournisseur
CREATE TABLE IF NOT EXISTS supplier_consents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  approved_from_request_id BIGINT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'approved',
  consent_text_version VARCHAR(40) NOT NULL,
  consent_text_snapshot LONGTEXT NOT NULL,
  consent_text_hash VARCHAR(64) NOT NULL,
  approved_at DATETIME NOT NULL,
  approved_ip VARCHAR(64) NULL,
  approved_user_agent VARCHAR(255) NULL,
  revoked_at DATETIME NULL,
  revoked_by_type VARCHAR(20) NULL,
  revoked_by_id INT NULL,
  revoke_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_supplier_consents_supplier (supplier_id),
  INDEX idx_supplier_consents_status (status),
  INDEX idx_supplier_consents_approved_at (approved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Step 3 : Modifier les règles de visibilité dans `map_data`
-- ============================================================================
-- 
-- ANCIENNE RÈGLE CLIENT (à remplacer dans api/index.php fonction map_data):
--   c.is_active = 1
--
-- NOUVELLE RÈGLE CLIENT:
--   c.is_active = 1
--   AND EXISTS (
--     SELECT 1
--     FROM client_consents cc
--     WHERE cc.client_id = c.id
--       AND cc.status = 'approved'
--       AND cc.revoked_at IS NULL
--   )
--
-- ANCIENNE RÈGLE FOURNISSEUR (À REMPLACER):
--   (aucune restriction spéciale, ou logique Mode A)
--
-- NOUVELLE RÈGLE FOURNISSEUR:
--   EXISTS (
--     SELECT 1
--     FROM supplier_consents sc
--     WHERE sc.supplier_id = s.id
--       AND sc.status = 'approved'
--       AND sc.revoked_at IS NULL
--   )
--
-- ============================================================================

-- ============================================================================
-- Step 4 : Notes importantes
-- ============================================================================
--
-- 1. Les anciennes tables PDF (phase 1) peuvent rester en base temporairement
--    pour audit, puis être supprimées après vérification.
--
-- 2. Le texte de consentement doit être défini avant première utilisation.
--    Version recommandée : '2026-04-v1' ou date du jour + version numéro.
--
-- 3. Token generation en PHP : utiliser hash('sha256', random_bytes(32))
--    Sauvegarder le HASH en BDD, pas le token brut, pour sécurité.
--
-- 4. Email validity : 14 jours (TTL = 14*24*3600 = 1209600 secondes)
--
-- 5. Aucun token n'est réutilisable après expiration ou utilisation.
--
-- ============================================================================
