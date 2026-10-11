-- ============================================================
-- SCHEMA PATCH v1.1
-- Missing tables and columns identified during PHP audit
-- Run this AFTER schema.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PATCH 1: travel_packages — PHP uses `duration_nights`, `image_url`, `base_price`, `package_code`
--           Schema has `price` and no `package_code`
-- ============================================================
ALTER TABLE travel_packages
    ADD COLUMN IF NOT EXISTS package_code    VARCHAR(20) UNIQUE COMMENT 'e.g. PKG-000001' AFTER id,
    ADD COLUMN IF NOT EXISTS duration_nights TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER duration_days,
    ADD COLUMN IF NOT EXISTS image_url       VARCHAR(255) GENERATED ALWAYS AS (image) STORED,
    ADD COLUMN IF NOT EXISTS base_price      DECIMAL(10,2) GENERATED ALWAYS AS (price) STORED;

UPDATE travel_packages SET package_code = CONCAT('PKG-', LPAD(id, 6, '0')) WHERE package_code IS NULL;

-- ============================================================
-- PATCH 2: customers — PHP uses `passport_expiry`, schema has `passport_expiry_date`
-- ============================================================
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS passport_expiry DATE GENERATED ALWAYS AS (passport_expiry_date) STORED;

-- ============================================================
-- PATCH 3: customer_documents — PHP uses `document_name`, schema has `file_name`
-- ============================================================
ALTER TABLE customer_documents
    ADD COLUMN IF NOT EXISTS document_name VARCHAR(255) GENERATED ALWAYS AS (file_name) STORED;

-- ============================================================
-- PATCH 4: payments — PHP uses `payment_method`, `transaction_id`
--           Schema uses `method`, `payment_ref`
-- ============================================================
ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(60) GENERATED ALWAYS AS (method) STORED,
    ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(200) GENERATED ALWAYS AS (payment_ref) STORED;

-- ============================================================
-- PATCH 5: bookings — PHP uses `agent_id`, schema has `created_by`
-- ============================================================
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS agent_id INT UNSIGNED GENERATED ALWAYS AS (created_by) STORED;

-- ============================================================
-- PATCH 6: invoices — PHP uses `paid_amount`; schema has no paid_amount
-- ============================================================
ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER total;

-- ============================================================
-- PATCH 7: partners — PHP uses `partner_type` and `email`
--           Schema uses `type` and `contact_email`
-- ============================================================
ALTER TABLE partners
    ADD COLUMN IF NOT EXISTS partner_type VARCHAR(60) GENERATED ALWAYS AS (type) STORED,
    ADD COLUMN IF NOT EXISTS email        VARCHAR(180) GENERATED ALWAYS AS (contact_email) STORED;

-- ============================================================
-- PATCH 8: visa_appointments — PHP uses `embassy_location`
--           Schema has `embassy_name`
-- ============================================================
ALTER TABLE visa_appointments
    ADD COLUMN IF NOT EXISTS embassy_location VARCHAR(255) GENERATED ALWAYS AS (embassy_name) STORED;

-- ============================================================
-- PATCH 9: appointments — PHP uses `agent_id`, `appointment_date` (full datetime)
--           Schema uses `staff_id`, separate date+time
-- ============================================================
ALTER TABLE appointments
    ADD COLUMN IF NOT EXISTS agent_id INT UNSIGNED GENERATED ALWAYS AS (staff_id) STORED;

-- ============================================================
-- PATCH 10: reviews — PHP uses `comment`, schema uses `body`
-- ============================================================
ALTER TABLE reviews
    ADD COLUMN IF NOT EXISTS comment TEXT GENERATED ALWAYS AS (body) STORED;

-- ============================================================
-- PATCH 11: refunds — PHP uses `invoice_id`, `created_at`
-- ============================================================
ALTER TABLE refunds
    ADD COLUMN IF NOT EXISTS invoice_id INT UNSIGNED COMMENT 'Shortcut to invoice' AFTER payment_id,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP GENERATED ALWAYS AS (requested_at) STORED;

-- ============================================================
-- PATCH 12: commissions — PHP uses `amount`, `invoice_id`
--            Schema has `commission_amount`, no invoice_id
-- ============================================================
ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS amount     DECIMAL(12,2) GENERATED ALWAYS AS (commission_amount) STORED,
    ADD COLUMN IF NOT EXISTS invoice_id INT UNSIGNED COMMENT 'Optional FK to invoices';

-- ============================================================
-- PATCH 13: package_itineraries — PHP uses `meals_included`
--            Schema has `meals` SET
-- ============================================================
ALTER TABLE package_itineraries
    ADD COLUMN IF NOT EXISTS meals_included VARCHAR(100) GENERATED ALWAYS AS (meals) STORED;

-- ============================================================
-- PATCH 14: destinations — PHP uses `image_url`
--            Schema has `image`
-- ============================================================
ALTER TABLE destinations
    ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) GENERATED ALWAYS AS (image) STORED;

-- ============================================================
-- PATCH 15: visa_requirements — PHP uses `document_type`
-- ============================================================
ALTER TABLE visa_requirements
    ADD COLUMN IF NOT EXISTS document_type VARCHAR(60) DEFAULT 'document' AFTER document_name;

-- ============================================================
-- PATCH 16: Missing Marketing tables
-- ============================================================

DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS discount_codes;
DROP TABLE IF EXISTS promotions;

CREATE TABLE promotions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    description     TEXT,
    discount_type   ENUM('percent','fixed') DEFAULT 'percent',
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    applies_to      ENUM('booking','visa','education','package','all') DEFAULT 'all',
    start_date      DATE,
    end_date        DATE,
    status          ENUM('draft','active','expired','cancelled') DEFAULT 'draft',
    created_by      INT UNSIGNED,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discount_codes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(40) NOT NULL UNIQUE,
    promotion_id     INT UNSIGNED,
    discount_type    ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value   DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_uses         INT UNSIGNED COMMENT 'NULL = unlimited',
    used_count       INT UNSIGNED DEFAULT 0,
    expires_at       DATETIME NULL,
    status           ENUM('active','inactive','expired') DEFAULT 'active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dc_promo FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaigns (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(200) NOT NULL,
    type             ENUM('email','sms','whatsapp','push') DEFAULT 'email',
    subject          VARCHAR(255),
    body             TEXT,
    recipients_count INT UNSIGNED DEFAULT 0,
    open_rate        DECIMAL(5,2),
    click_rate       DECIMAL(5,2),
    status           ENUM('draft','scheduled','active','paused','completed','cancelled') DEFAULT 'draft',
    scheduled_at     DATETIME NULL,
    started_at       DATETIME NULL,
    completed_at     DATETIME NULL,
    created_by       INT UNSIGNED,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_camp_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PATCH 17: booking_date default — PHP handles booking_code via
--            generate_code(). Just set a DB-level default for booking_date.
-- ============================================================
ALTER TABLE bookings
    MODIFY COLUMN booking_date DATE NOT NULL DEFAULT (CURDATE());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF PATCH v1.1
-- ============================================================
