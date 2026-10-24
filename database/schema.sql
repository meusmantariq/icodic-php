-- Quantum Nexis — MySQL 8 / MariaDB 10.4+ schema
-- Charset utf8mb4 for Arabic / Urdu / Hindi content.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS site_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  company_name VARCHAR(190) NOT NULL DEFAULT 'Quantum Nexis',
  company_logo_url VARCHAR(500) NULL,
  hero_title VARCHAR(500) NULL,
  hero_tagline VARCHAR(500) NULL,
  admin_email VARCHAR(190) NULL,
  whatsapp_number VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS software_products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  name VARCHAR(190) NOT NULL,
  brand ENUM('Adobe','Microsoft','Autodesk','Other') NOT NULL DEFAULT 'Other',
  category ENUM('Design','Productivity','Office Tools','CRM','ERP','Management','Security','AI Systems','Other') NOT NULL DEFAULT 'Design',
  description TEXT NULL,
  image_url VARCHAR(500) NULL,
  price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
  price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
  license_tiers VARCHAR(255) NULL,
  sku VARCHAR(64) NULL,
  in_stock TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  regions TEXT NULL COMMENT 'JSON array of region codes',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_legacy (legacy_id),
  KEY idx_products_category (category),
  KEY idx_products_brand (brand),
  KEY idx_products_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS erp_solutions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  name VARCHAR(190) NOT NULL,
  type ENUM('ERPNext','Odoo','Custom CRM','Custom ERP','Microsoft Dynamics') NOT NULL DEFAULT 'ERPNext',
  tagline VARCHAR(255) NULL,
  description TEXT NULL,
  features TEXT NULL COMMENT 'JSON array',
  image_url VARCHAR(500) NULL,
  consultation TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_legacy (legacy_id),
  KEY idx_erp_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  title VARCHAR(255) NOT NULL,
  client VARCHAR(190) NOT NULL DEFAULT '',
  region ENUM('Canada','UAE','India','Pakistan','Global') NOT NULL DEFAULT 'Global',
  year VARCHAR(60) NULL,
  description TEXT NULL,
  image_url VARCHAR(500) NULL,
  tech_stack TEXT NULL COMMENT 'JSON array',
  url VARCHAR(500) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_legacy (legacy_id),
  KEY idx_projects_region (region),
  KEY idx_projects_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT 'Server',
  image_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_legacy (legacy_id),
  KEY idx_services_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cyber_services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT 'ShieldCheck',
  image_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cyber_legacy (legacy_id),
  KEY idx_cyber_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  name VARCHAR(190) NOT NULL,
  role VARCHAR(190) NULL,
  content TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  avatar_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_testimonials_legacy (legacy_id),
  KEY idx_testimonials_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  code VARCHAR(8) NOT NULL,
  city VARCHAR(120) NOT NULL,
  name VARCHAR(190) NULL,
  country VARCHAR(120) NULL,
  address VARCHAR(500) NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  contact_name VARCHAR(190) NULL,
  role VARCHAR(190) NULL,
  tz VARCHAR(64) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offices_legacy (legacy_id),
  UNIQUE KEY uq_offices_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deployment_feed (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  region VARCHAR(16) NULL,
  item VARCHAR(255) NOT NULL,
  action VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_feed_legacy (legacy_id),
  KEY idx_feed_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  page VARCHAR(60) NOT NULL,
  section VARCHAR(60) NOT NULL,
  type ENUM('text','image','html') NOT NULL DEFAULT 'text',
  title VARCHAR(500) NULL,
  subtitle TEXT NULL,
  body MEDIUMTEXT NULL,
  image_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blocks_legacy (legacy_id),
  KEY idx_blocks_page_section (page, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  order_id VARCHAR(20) NOT NULL,
  customer_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  company VARCHAR(190) NULL,
  region VARCHAR(16) NULL,
  product_id INT UNSIGNED NULL,
  product_legacy_id VARCHAR(32) NULL,
  product_name VARCHAR(190) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  billing_period ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  notes TEXT NULL,
  comment TEXT NULL COMMENT 'Admin note shown to customer on track page',
  total_usd DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('new','confirmed','rejected','processing','dispatched','delivered','completed','cancelled') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_order_id (order_id),
  UNIQUE KEY uq_orders_legacy (legacy_id),
  KEY idx_orders_status (status),
  KEY idx_orders_email (email),
  KEY idx_orders_product (product_id),
  CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES software_products(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  company VARCHAR(190) NULL,
  region VARCHAR(16) NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_messages_legacy (legacy_id),
  KEY idx_messages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_assessments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_id VARCHAR(32) NULL,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  company VARCHAR(190) NULL,
  region ENUM('Canada','UAE','India','Pakistan','Global') NOT NULL DEFAULT 'UAE',
  service_needed ENUM('Endpoint Protection','Network Security','SOC & SIEM','Penetration Testing','Cloud Security','Compliance & GRC','General Assessment') NOT NULL DEFAULT 'General Assessment',
  urgency ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  message TEXT NOT NULL,
  status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assess_legacy (legacy_id),
  KEY idx_assess_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Authentication (replaces Base44 User entity + shared admin password)
CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_resets_token (token_hash),
  CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address VARCHAR(45) NOT NULL,
  email VARCHAR(190) NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  entity VARCHAR(60) NOT NULL,
  record_id INT UNSIGNED NULL,
  summary VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_entity (entity, record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id VARCHAR(64) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chat_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
