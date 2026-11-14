-- =====================================================================================
-- Quantum Nexis — Sales module migration (customers, quotations, sales orders,
-- invoices, payments, document numbering).
--
-- Version : 2026_11_14_1200_sales_module
-- Target  : MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4)
-- Applied : automatically by database/cli_migrate.php, database/cli_install.php and the
--           web installer; recorded in `schema_migrations`. Idempotent: safe to run on an
--           existing database, never drops or rewrites existing tables/rows.
--
-- Money is stored as INTEGER minor units (BIGINT): fils/cents/paisa. No DECIMAL/float
-- arithmetic for amounts, so totals are exact. Quantities use DECIMAL(12,3) and are read
-- into integer thousandths by the application.
--
-- Statements contain no inner semicolons (no triggers / procedures), so both the
-- migration runner and the legacy `preg_split('/;\s*\n/')` loader can execute the file.
-- =====================================================================================

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  company_name VARCHAR(190) NOT NULL,
  contact_name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  trn VARCHAR(60) NULL COMMENT 'Customer tax registration number (free text, not validated against any authority)',
  website VARCHAR(190) NULL,
  address_line1 VARCHAR(190) NULL,
  address_line2 VARCHAR(190) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  default_currency CHAR(3) NOT NULL DEFAULT 'AED',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  legacy_id VARCHAR(32) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_customers_code (code),
  UNIQUE KEY uq_customers_legacy (legacy_id),
  KEY idx_customers_company (company_name),
  KEY idx_customers_email (email),
  KEY idx_customers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_sequences (
  doc_type VARCHAR(20) NOT NULL,
  period CHAR(4) NOT NULL,
  next_no INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (doc_type, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_type ENUM('quotation','sales_order','invoice') NOT NULL,
  doc_no VARCHAR(30) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL,
  status VARCHAR(20) NOT NULL,
  issue_date DATE NOT NULL,
  valid_until DATE NULL COMMENT 'Quotation only',
  due_date DATE NULL COMMENT 'Invoice only',
  customer_reference VARCHAR(120) NULL COMMENT 'Buyer PO / reference',
  notes TEXT NULL,
  subtotal_minor BIGINT NOT NULL DEFAULT 0,
  discount_minor BIGINT NOT NULL DEFAULT 0,
  tax_rate_bp SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Single reference tax rate, basis points',
  tax_minor BIGINT NOT NULL DEFAULT 0,
  total_minor BIGINT NOT NULL DEFAULT 0,
  source_type ENUM('quotation','sales_order') NULL,
  source_id INT UNSIGNED NULL,
  approved_at DATETIME NULL,
  approved_by INT UNSIGNED NULL,
  converted_at DATETIME NULL COMMENT 'Set when this document produced a child document',
  closed_at DATETIME NULL COMMENT 'Cancelled / voided / rejected timestamp',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_documents_no (doc_no),
  UNIQUE KEY uq_documents_source (doc_type, source_type, source_id),
  KEY idx_documents_type_status (doc_type, status),
  KEY idx_documents_customer (customer_id),
  KEY idx_documents_currency (currency),
  KEY idx_documents_issue_date (issue_date),
  CONSTRAINT fk_documents_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_documents_source FOREIGN KEY (source_id) REFERENCES sales_documents (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_documents_approved_by FOREIGN KEY (approved_by) REFERENCES admin_users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_documents_created_by FOREIGN KEY (created_by) REFERENCES admin_users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_documents_amounts CHECK (subtotal_minor >= 0 AND discount_minor >= 0 AND tax_minor >= 0 AND total_minor >= 0 AND discount_minor <= subtotal_minor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_document_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  document_id INT UNSIGNED NOT NULL,
  line_no SMALLINT UNSIGNED NOT NULL,
  sku VARCHAR(64) NULL,
  description VARCHAR(500) NOT NULL,
  qty_thousandths DECIMAL(12,3) NOT NULL DEFAULT 1.000 COMMENT 'Quantity ×1000, integer-safe',
  unit_price_minor BIGINT NOT NULL DEFAULT 0,
  line_total_minor BIGINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_items_document_line (document_id, line_no),
  CONSTRAINT fk_items_document FOREIGN KEY (document_id) REFERENCES sales_documents (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_items_qty CHECK (qty_thousandths > 0),
  CONSTRAINT chk_items_amounts CHECK (unit_price_minor >= 0 AND line_total_minor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_no VARCHAR(30) NOT NULL,
  invoice_id INT UNSIGNED NOT NULL,
  paid_on DATE NOT NULL,
  amount_minor BIGINT NOT NULL,
  method ENUM('bank_transfer','cash','cheque','card','other') NOT NULL DEFAULT 'bank_transfer',
  reference VARCHAR(120) NULL,
  notes VARCHAR(500) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_no (payment_no),
  KEY idx_payments_invoice (invoice_id),
  KEY idx_payments_paid_on (paid_on),
  CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES sales_documents (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_payments_created_by FOREIGN KEY (created_by) REFERENCES admin_users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_payments_amount CHECK (amount_minor > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
