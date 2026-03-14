CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  client_type VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  postal_code VARCHAR(30) NULL,
  country VARCHAR(120) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  lundi VARCHAR(255) NULL,
  mardi VARCHAR(255) NULL,
  mercredi VARCHAR(255) NULL,
  jeudi VARCHAR(255) NULL,
  vendredi VARCHAR(255) NULL,
  samedi VARCHAR(255) NULL,
  dimanche VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  facebook_url VARCHAR(255) NULL,
  instagram_url VARCHAR(255) NULL,
  linkedin_url VARCHAR(255) NULL,
  logo_url VARCHAR(255) NULL,
  photo_cover_url VARCHAR(255) NULL,
  slug VARCHAR(190) NULL,
  description_short TEXT NULL,
  description_long TEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  public_updated_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clients_name (name),
  INDEX idx_clients_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL UNIQUE,
  family VARCHAR(190) NULL,
  icon_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_activities_family (family)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS labels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL UNIQUE,
  color VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  normalized_name VARCHAR(190) NOT NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  postal_code VARCHAR(30) NULL,
  country VARCHAR(120) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  website VARCHAR(255) NULL,
  facebook_url VARCHAR(255) NULL,
  instagram_url VARCHAR(255) NULL,
  linkedin_url VARCHAR(255) NULL,
  logo_url VARCHAR(255) NULL,
  photo_cover_url VARCHAR(255) NULL,
  slug VARCHAR(190) NULL,
  description_short TEXT NULL,
  description_long TEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  public_updated_at DATETIME NULL,
  supplier_type VARCHAR(120) NULL,
  activity_text VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_suppliers_norm_name_city (normalized_name, city),
  INDEX idx_suppliers_phone (phone),
  INDEX idx_suppliers_email (email),
  INDEX idx_suppliers_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_activities (
  supplier_id INT NOT NULL,
  activity_id INT NOT NULL,
  PRIMARY KEY (supplier_id, activity_id),
  CONSTRAINT fk_supplier_activities_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_activities_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_labels (
  supplier_id INT NOT NULL,
  label_id INT NOT NULL,
  PRIMARY KEY (supplier_id, label_id),
  CONSTRAINT fk_supplier_labels_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_labels_label FOREIGN KEY (label_id) REFERENCES labels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  supplier_id INT NOT NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'manual',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_client_supplier (client_id, supplier_id),
  INDEX idx_client_suppliers_supplier (supplier_id),
  CONSTRAINT fk_client_suppliers_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_suppliers_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_name VARCHAR(255) NOT NULL,
  client_id INT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'preview',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_import_batches_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_conflicts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  row_index INT NOT NULL,
  supplier_id INT NOT NULL,
  field_name VARCHAR(80) NOT NULL,
  existing_value TEXT NULL,
  incoming_value TEXT NULL,
  resolution VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_import_conflicts_batch FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_import_conflicts_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  username VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'client_manager',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_client_users_username (username),
  INDEX idx_client_users_client (client_id),
  CONSTRAINT fk_client_users_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_type VARCHAR(20) NOT NULL,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_lookup (user_type, user_id),
  INDEX idx_password_reset_expires (expires_at),
  UNIQUE KEY uq_password_reset_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_audit (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_type VARCHAR(20) NOT NULL,
  user_id INT NOT NULL,
  username VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  status VARCHAR(20) NOT NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_audit_created (created_at),
  INDEX idx_password_reset_audit_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_supplier_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  supplier_id INT NOT NULL,
  activity_text VARCHAR(255) NULL,
  labels_text VARCHAR(255) NULL,
  notes TEXT NULL,
  relationship_status VARCHAR(30) NOT NULL DEFAULT 'active',
  updated_by_type VARCHAR(20) NOT NULL DEFAULT 'admin',
  updated_by_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_client_supplier_profile (client_id, supplier_id),
  INDEX idx_client_supplier_profiles_supplier (supplier_id),
  CONSTRAINT fk_client_supplier_profiles_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_supplier_profiles_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_change_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  client_id INT NOT NULL,
  requested_by_user_id INT NOT NULL,
  field_name VARCHAR(80) NOT NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  reviewed_by_admin VARCHAR(120) NULL,
  reviewed_at TIMESTAMP NULL,
  review_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_supplier_change_requests_supplier (supplier_id),
  INDEX idx_supplier_change_requests_client (client_id),
  INDEX idx_supplier_change_requests_status (status),
  CONSTRAINT fk_supplier_change_requests_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_change_requests_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_change_requests_user FOREIGN KEY (requested_by_user_id) REFERENCES client_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_supplier_creation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  requested_by_user_id INT NOT NULL,
  name VARCHAR(190) NOT NULL,
  supplier_type VARCHAR(120) NULL,
  activity_text VARCHAR(255) NULL,
  labels_text VARCHAR(255) NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  postal_code VARCHAR(30) NULL,
  country VARCHAR(120) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  website VARCHAR(255) NULL,
  notes TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  approved_supplier_id INT NULL,
  reviewed_by_admin VARCHAR(120) NULL,
  reviewed_at TIMESTAMP NULL,
  review_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_client_supplier_creation_requests_client (client_id),
  INDEX idx_client_supplier_creation_requests_status (status),
  INDEX idx_client_supplier_creation_requests_approved_supplier (approved_supplier_id),
  CONSTRAINT fk_client_supplier_creation_requests_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_supplier_creation_requests_user FOREIGN KEY (requested_by_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_supplier_creation_requests_supplier FOREIGN KEY (approved_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_supplier_link_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  requested_by_user_id INT NOT NULL,
  supplier_id INT NOT NULL,
  note TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  reviewed_by_admin VARCHAR(120) NULL,
  reviewed_at TIMESTAMP NULL,
  review_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_client_supplier_link_requests_client (client_id),
  INDEX idx_client_supplier_link_requests_supplier (supplier_id),
  INDEX idx_client_supplier_link_requests_status (status),
  CONSTRAINT fk_client_supplier_link_requests_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_supplier_link_requests_user FOREIGN KEY (requested_by_user_id) REFERENCES client_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_client_supplier_link_requests_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  actor_type VARCHAR(20) NOT NULL,
  actor_id INT NULL,
  actor_name VARCHAR(120) NULL,
  action_name VARCHAR(60) NOT NULL,
  field_name VARCHAR(80) NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  meta_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_supplier_audit_supplier (supplier_id),
  INDEX idx_supplier_audit_created_at (created_at),
  CONSTRAINT fk_supplier_audit_log_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  actor_type VARCHAR(30) NOT NULL,
  actor_id INT NULL,
  actor_name VARCHAR(190) NULL,
  action_name VARCHAR(80) NOT NULL,
  target_type VARCHAR(40) NULL,
  target_id INT NULL,
  target_label VARCHAR(255) NULL,
  details_json JSON NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_audit_created_at (created_at),
  INDEX idx_admin_audit_actor (actor_type, actor_id),
  INDEX idx_admin_audit_action (action_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value)
VALUES
  ('org_name', 'Carte Fournisseurs'),
  ('org_logo_url', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);