-- ============================================================
-- TRAVEL AGENCY MANAGEMENT SYSTEM
-- MySQL Database Schema
-- Version: 1.0
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- Drop tables in reverse dependency order
-- ============================================================
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS commissions;
DROP TABLE IF EXISTS refunds;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS inquiries;
DROP TABLE IF EXISTS visa_application_status_history;
DROP TABLE IF EXISTS visa_application_documents;
DROP TABLE IF EXISTS visa_appointments;
DROP TABLE IF EXISTS visa_requirements;
DROP TABLE IF EXISTS visa_applications;
DROP TABLE IF EXISTS visa_types;
DROP TABLE IF EXISTS education_applications;
DROP TABLE IF EXISTS education_programs;
DROP TABLE IF EXISTS education_institutions;
DROP TABLE IF EXISTS customer_documents;
DROP TABLE IF EXISTS tour_bookings;
DROP TABLE IF EXISTS car_rentals;
DROP TABLE IF EXISTS hotel_bookings;
DROP TABLE IF EXISTS flight_bookings;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS package_itineraries;
DROP TABLE IF EXISTS travel_packages;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE roles (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(60)  NOT NULL,
    slug        VARCHAR(60)  NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USERS  (staff / back-office)
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id       TINYINT UNSIGNED NOT NULL,
    status        ENUM('active','inactive','suspended') DEFAULT 'active',
    avatar        VARCHAR(255),
    last_login_at TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. CUSTOMERS  (central entity)
-- ============================================================
CREATE TABLE customers (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code        VARCHAR(20)  NOT NULL UNIQUE COMMENT 'e.g. CUS-000001',
    first_name           VARCHAR(80)  NOT NULL,
    last_name            VARCHAR(80)  NOT NULL,
    email                VARCHAR(180) NOT NULL UNIQUE,
    phone                VARCHAR(30),
    date_of_birth        DATE,
    nationality          VARCHAR(80),
    national_id          VARCHAR(60),
    passport_number      VARCHAR(40),
    passport_issue_date  DATE,
    passport_expiry_date DATE,
    gender               ENUM('male','female','other','prefer_not_to_say'),
    marital_status       ENUM('single','married','divorced','widowed'),
    occupation           VARCHAR(120),
    employer             VARCHAR(160),
    address_line1        VARCHAR(200),
    address_line2        VARCHAR(200),
    city                 VARCHAR(80),
    state                VARCHAR(80),
    postal_code          VARCHAR(20),
    country              VARCHAR(80),
    emergency_contact_name  VARCHAR(120),
    emergency_contact_phone VARCHAR(30),
    notes                TEXT,
    assigned_agent_id    INT UNSIGNED,
    status               ENUM('active','inactive','blacklisted') DEFAULT 'active',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_customers_email    (email),
    INDEX idx_customers_passport (passport_number),
    INDEX idx_customers_code     (customer_code),
    INDEX idx_customers_agent    (assigned_agent_id),
    CONSTRAINT fk_customers_agent FOREIGN KEY (assigned_agent_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. CUSTOMER DOCUMENTS
-- ============================================================
CREATE TABLE customer_documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id   INT UNSIGNED NOT NULL,
    document_type ENUM(
        'passport','national_id','birth_certificate','bank_statement',
        'employment_letter','salary_slip','tax_return','academic_certificate',
        'admission_letter','accommodation_proof','photo','insurance',
        'marriage_certificate','police_clearance','medical_certificate','other'
    ) NOT NULL,
    file_name     VARCHAR(255) NOT NULL,
    file_path     VARCHAR(500) NOT NULL,
    file_size     INT UNSIGNED COMMENT 'bytes',
    mime_type     VARCHAR(80),
    status        ENUM('pending','verified','rejected','expired') DEFAULT 'pending',
    expiry_date   DATE,
    notes         TEXT,
    uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at   TIMESTAMP NULL,
    verified_by   INT UNSIGNED,

    INDEX idx_cdoc_customer (customer_id),
    INDEX idx_cdoc_status   (status),
    CONSTRAINT fk_cdoc_customer   FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_cdoc_verifiedby FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. DESTINATIONS
-- ============================================================
CREATE TABLE destinations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120) NOT NULL,
    country      VARCHAR(80)  NOT NULL,
    region       VARCHAR(80),
    description  TEXT,
    image        VARCHAR(255),
    is_popular   BOOLEAN DEFAULT FALSE,
    is_active    BOOLEAN DEFAULT TRUE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_dest_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TRAVEL PACKAGES
-- ============================================================
CREATE TABLE travel_packages (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200) NOT NULL,
    slug           VARCHAR(220) NOT NULL UNIQUE,
    destination_id INT UNSIGNED,
    description    TEXT,
    highlights     TEXT,
    inclusions     TEXT,
    exclusions     TEXT,
    duration_days   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    duration_nights TINYINT UNSIGNED NOT NULL DEFAULT 0,
    price           DECIMAL(10,2) NOT NULL,
    currency       CHAR(3) DEFAULT 'USD',
    discount       DECIMAL(5,2) DEFAULT 0.00 COMMENT 'percent',
    max_pax        TINYINT UNSIGNED,
    image          VARCHAR(255),
    status         ENUM('draft','active','archived') DEFAULT 'draft',
    created_by     INT UNSIGNED,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pkg_dest   (destination_id),
    INDEX idx_pkg_status (status),
    CONSTRAINT fk_pkg_dest    FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE SET NULL,
    CONSTRAINT fk_pkg_created FOREIGN KEY (created_by)     REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. PACKAGE ITINERARIES
-- ============================================================
CREATE TABLE package_itineraries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id    INT UNSIGNED NOT NULL,
    day_number    TINYINT UNSIGNED NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    activities    TEXT,
    meals         SET('breakfast','lunch','dinner'),
    accommodation VARCHAR(200),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_itin_package (package_id),
    CONSTRAINT fk_itin_package FOREIGN KEY (package_id) REFERENCES travel_packages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. BOOKINGS  (parent record)
-- ============================================================
CREATE TABLE bookings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code  VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. BK-000001',
    customer_id   INT UNSIGNED NOT NULL,
    booking_type  ENUM('flight','hotel','car_rental','tour','package','multi') NOT NULL,
    status        ENUM('draft','pending','confirmed','cancelled','completed','refunded') DEFAULT 'draft',
    booking_date  DATE NOT NULL,
    travel_date   DATE,
    return_date   DATE,
    adults        TINYINT UNSIGNED DEFAULT 1,
    children      TINYINT UNSIGNED DEFAULT 0,
    total_amount  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_amount   DECIMAL(12,2) DEFAULT 0.00,
    currency      CHAR(3) DEFAULT 'USD',
    package_id    INT UNSIGNED,
    notes         TEXT,
    created_by    INT UNSIGNED,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_bk_customer (customer_id),
    INDEX idx_bk_status   (status),
    INDEX idx_bk_code     (booking_code),
    CONSTRAINT fk_bk_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_bk_package  FOREIGN KEY (package_id)  REFERENCES travel_packages (id) ON DELETE SET NULL,
    CONSTRAINT fk_bk_created  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. FLIGHT BOOKINGS
-- ============================================================
CREATE TABLE flight_bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL,
    airline         VARCHAR(100),
    flight_number   VARCHAR(20),
    origin          VARCHAR(80) NOT NULL,
    destination     VARCHAR(80) NOT NULL,
    departure_at    DATETIME NOT NULL,
    arrival_at      DATETIME NOT NULL,
    cabin_class     ENUM('economy','premium_economy','business','first') DEFAULT 'economy',
    ticket_number   VARCHAR(60),
    pnr_code        VARCHAR(20),
    baggage_kg      TINYINT UNSIGNED,
    fare            DECIMAL(10,2),
    is_return       BOOLEAN DEFAULT FALSE,

    INDEX idx_fl_booking (booking_id),
    CONSTRAINT fk_fl_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. HOTEL BOOKINGS
-- ============================================================
CREATE TABLE hotel_bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL,
    hotel_name      VARCHAR(200) NOT NULL,
    city            VARCHAR(80),
    country         VARCHAR(80),
    stars           TINYINT UNSIGNED,
    room_type       VARCHAR(100),
    check_in        DATE NOT NULL,
    check_out       DATE NOT NULL,
    nights          TINYINT UNSIGNED,
    rooms           TINYINT UNSIGNED DEFAULT 1,
    confirmation    VARCHAR(80),
    rate_per_night  DECIMAL(10,2),
    total_fare      DECIMAL(10,2),
    partner_id      INT UNSIGNED,

    INDEX idx_ht_booking (booking_id),
    CONSTRAINT fk_ht_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. CAR RENTALS
-- ============================================================
CREATE TABLE car_rentals (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id       INT UNSIGNED NOT NULL,
    vendor           VARCHAR(100),
    car_type         VARCHAR(100),
    pickup_location  VARCHAR(200),
    dropoff_location VARCHAR(200),
    pickup_at        DATETIME NOT NULL,
    dropoff_at       DATETIME NOT NULL,
    days             TINYINT UNSIGNED,
    rate_per_day     DECIMAL(10,2),
    total_fare       DECIMAL(10,2),
    confirmation     VARCHAR(80),

    INDEX idx_cr_booking (booking_id),
    CONSTRAINT fk_cr_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. TOUR BOOKINGS
-- ============================================================
CREATE TABLE tour_bookings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id    INT UNSIGNED NOT NULL,
    tour_name     VARCHAR(200) NOT NULL,
    operator      VARCHAR(150),
    destination   VARCHAR(120),
    start_date    DATE NOT NULL,
    end_date      DATE,
    duration      VARCHAR(40),
    guide_lang    VARCHAR(60),
    price_per_pax DECIMAL(10,2),
    total_fare    DECIMAL(10,2),
    confirmation  VARCHAR(80),
    partner_id    INT UNSIGNED,

    INDEX idx_tb_booking (booking_id),
    CONSTRAINT fk_tb_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. VISA TYPES
-- ============================================================
CREATE TABLE visa_types (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    country          VARCHAR(80)  NOT NULL,
    category         ENUM('tourist','student','business','family','transit','work','medical','other') NOT NULL,
    description      TEXT,
    processing_time  VARCHAR(60) COMMENT 'e.g. 5-10 working days',
    validity         VARCHAR(80) COMMENT 'e.g. 1 year, multiple entry',
    max_stay         VARCHAR(60),
    fee              DECIMAL(10,2) DEFAULT 0.00,
    service_fee      DECIMAL(10,2) DEFAULT 0.00,
    currency         CHAR(3) DEFAULT 'USD',
    embassy_website  VARCHAR(255),
    notes            TEXT,
    is_active        BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_vt_country  (country),
    INDEX idx_vt_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. VISA REQUIREMENTS
-- ============================================================
CREATE TABLE visa_requirements (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visa_type_id  INT UNSIGNED NOT NULL,
    document_name VARCHAR(160) NOT NULL,
    description   TEXT,
    is_mandatory  BOOLEAN DEFAULT TRUE,
    notes         VARCHAR(255),
    sort_order    TINYINT UNSIGNED DEFAULT 0,

    INDEX idx_vreq_type (visa_type_id),
    CONSTRAINT fk_vreq_type FOREIGN KEY (visa_type_id) REFERENCES visa_types (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. VISA APPLICATIONS
-- ============================================================
CREATE TABLE visa_applications (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_code     VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. VA-000001',
    customer_id          INT UNSIGNED NOT NULL,
    visa_type_id         INT UNSIGNED NOT NULL,
    destination_country  VARCHAR(80)  NOT NULL,
    purpose              TEXT,
    travel_date          DATE,
    return_date          DATE,
    status               ENUM(
        'draft',
        'documents_pending',
        'under_review',
        'ready_for_submission',
        'submitted',
        'appointment_scheduled',
        'processing',
        'approved',
        'rejected',
        'cancelled',
        'withdrawn'
    ) DEFAULT 'draft',
    priority             ENUM('normal','urgent','express') DEFAULT 'normal',
    service_fee          DECIMAL(10,2) DEFAULT 0.00,
    visa_fee             DECIMAL(10,2) DEFAULT 0.00,
    currency             CHAR(3) DEFAULT 'USD',
    embassy_ref          VARCHAR(120) COMMENT 'Embassy reference number',
    submitted_at         DATETIME NULL,
    decision_date        DATE NULL,
    expiry_date          DATE NULL COMMENT 'Visa expiry if approved',
    rejection_reason     TEXT,
    assigned_officer_id  INT UNSIGNED,
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_va_customer (customer_id),
    INDEX idx_va_status   (status),
    INDEX idx_va_code     (application_code),
    INDEX idx_va_officer  (assigned_officer_id),
    CONSTRAINT fk_va_customer FOREIGN KEY (customer_id)        REFERENCES customers (id),
    CONSTRAINT fk_va_visatype FOREIGN KEY (visa_type_id)       REFERENCES visa_types (id),
    CONSTRAINT fk_va_officer  FOREIGN KEY (assigned_officer_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. VISA APPLICATION DOCUMENTS
-- ============================================================
CREATE TABLE visa_application_documents (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visa_application_id  INT UNSIGNED NOT NULL,
    document_id          INT UNSIGNED NOT NULL,
    requirement_id       INT UNSIGNED,
    status               ENUM('pending','submitted','verified','rejected') DEFAULT 'pending',
    notes                TEXT,
    submitted_at         TIMESTAMP NULL,
    reviewed_at          TIMESTAMP NULL,
    reviewed_by          INT UNSIGNED,

    INDEX idx_vad_app (visa_application_id),
    INDEX idx_vad_doc (document_id),
    CONSTRAINT fk_vad_app FOREIGN KEY (visa_application_id) REFERENCES visa_applications (id) ON DELETE CASCADE,
    CONSTRAINT fk_vad_doc FOREIGN KEY (document_id)         REFERENCES customer_documents (id),
    CONSTRAINT fk_vad_req FOREIGN KEY (requirement_id)      REFERENCES visa_requirements (id) ON DELETE SET NULL,
    CONSTRAINT fk_vad_rev FOREIGN KEY (reviewed_by)         REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. VISA APPLICATION STATUS HISTORY  (full audit trail)
-- ============================================================
CREATE TABLE visa_application_status_history (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visa_application_id  INT UNSIGNED NOT NULL,
    old_status           VARCHAR(60),
    new_status           VARCHAR(60) NOT NULL,
    changed_by           INT UNSIGNED,
    notes                TEXT,
    changed_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_vash_app (visa_application_id),
    CONSTRAINT fk_vash_app FOREIGN KEY (visa_application_id) REFERENCES visa_applications (id) ON DELETE CASCADE,
    CONSTRAINT fk_vash_usr FOREIGN KEY (changed_by)          REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. VISA APPOINTMENTS
-- ============================================================
CREATE TABLE visa_appointments (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visa_application_id  INT UNSIGNED NOT NULL,
    appointment_date     DATE NOT NULL,
    appointment_time     TIME NOT NULL,
    embassy_name         VARCHAR(200),
    embassy_address      TEXT,
    appointment_ref      VARCHAR(120),
    status               ENUM('scheduled','completed','cancelled','no_show','rescheduled') DEFAULT 'scheduled',
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_vappt_app  (visa_application_id),
    INDEX idx_vappt_date (appointment_date),
    CONSTRAINT fk_vappt_app FOREIGN KEY (visa_application_id) REFERENCES visa_applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. EDUCATION INSTITUTIONS
-- ============================================================
CREATE TABLE education_institutions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    country     VARCHAR(80)  NOT NULL,
    city        VARCHAR(80),
    website     VARCHAR(255),
    description TEXT,
    ranking     INT UNSIGNED,
    partner_id  INT UNSIGNED,
    status      ENUM('active','inactive') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ei_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20. EDUCATION PROGRAMS
-- ============================================================
CREATE TABLE education_programs (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id        INT UNSIGNED NOT NULL,
    name                  VARCHAR(200) NOT NULL,
    degree_level          ENUM('certificate','diploma','bachelors','masters','phd','language','professional') NOT NULL,
    field                 VARCHAR(150),
    duration_months       SMALLINT UNSIGNED,
    language              VARCHAR(60) DEFAULT 'English',
    tuition_fee           DECIMAL(12,2),
    currency              CHAR(3) DEFAULT 'USD',
    start_dates           VARCHAR(200) COMMENT 'e.g. September, January',
    requirements          TEXT,
    scholarship_available BOOLEAN DEFAULT FALSE,
    status                ENUM('active','inactive') DEFAULT 'active',
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ep_inst  (institution_id),
    INDEX idx_ep_level (degree_level),
    CONSTRAINT fk_ep_inst FOREIGN KEY (institution_id) REFERENCES education_institutions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21. EDUCATION APPLICATIONS
-- ============================================================
CREATE TABLE education_applications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_code    VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. EA-000001',
    customer_id         INT UNSIGNED NOT NULL,
    institution_id      INT UNSIGNED NOT NULL,
    program_id          INT UNSIGNED NOT NULL,
    intake              VARCHAR(60) COMMENT 'e.g. Sep 2027',
    status              ENUM(
        'new',
        'documents_pending',
        'under_review',
        'applied_to_institution',
        'admission_pending',
        'accepted',
        'rejected',
        'conditional_offer',
        'enrolled',
        'visa_applied',
        'cancelled',
        'withdrawn'
    ) DEFAULT 'new',
    application_date    DATE,
    admission_status    ENUM('not_applied','pending','offered','conditional','accepted','rejected') DEFAULT 'not_applied',
    offer_date          DATE NULL,
    enrolment_date      DATE NULL,
    student_id_number   VARCHAR(80),
    notes               TEXT,
    linked_visa_id      INT UNSIGNED COMMENT 'Education visa application',
    assigned_officer_id INT UNSIGNED,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ea_customer (customer_id),
    INDEX idx_ea_status   (status),
    INDEX idx_ea_code     (application_code),
    CONSTRAINT fk_ea_customer FOREIGN KEY (customer_id)         REFERENCES customers (id),
    CONSTRAINT fk_ea_inst     FOREIGN KEY (institution_id)      REFERENCES education_institutions (id),
    CONSTRAINT fk_ea_program  FOREIGN KEY (program_id)          REFERENCES education_programs (id),
    CONSTRAINT fk_ea_visa     FOREIGN KEY (linked_visa_id)      REFERENCES visa_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_ea_officer  FOREIGN KEY (assigned_officer_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 22. PARTNERS
-- ============================================================
CREATE TABLE partners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    type            ENUM('airline','hotel','university','tour_operator','insurance','b2b_agent','other') NOT NULL,
    country         VARCHAR(80),
    city            VARCHAR(80),
    contact_name    VARCHAR(120),
    contact_email   VARCHAR(180),
    contact_phone   VARCHAR(30),
    website         VARCHAR(255),
    commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'percent',
    contract_start  DATE,
    contract_end    DATE,
    status          ENUM('active','inactive','pending') DEFAULT 'pending',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_partners_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add deferred FKs now partners exists
ALTER TABLE hotel_bookings      ADD CONSTRAINT fk_ht_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE SET NULL;
ALTER TABLE tour_bookings       ADD CONSTRAINT fk_tb_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE SET NULL;
ALTER TABLE education_institutions ADD CONSTRAINT fk_ei_partner FOREIGN KEY (partner_id) REFERENCES partners (id) ON DELETE SET NULL;

-- ============================================================
-- 23. INVOICES
-- ============================================================
CREATE TABLE invoices (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number   VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. INV-000001',
    customer_id      INT UNSIGNED NOT NULL,
    booking_id       INT UNSIGNED,
    visa_app_id      INT UNSIGNED,
    education_app_id INT UNSIGNED,
    subtotal         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax              DECIMAL(10,2) DEFAULT 0.00,
    discount         DECIMAL(10,2) DEFAULT 0.00,
    total            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency         CHAR(3) DEFAULT 'USD',
    status           ENUM('draft','sent','paid','partially_paid','overdue','cancelled') DEFAULT 'draft',
    issue_date       DATE NOT NULL,
    due_date         DATE NOT NULL,
    paid_date        DATE,
    notes            TEXT,
    created_by       INT UNSIGNED,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_inv_customer (customer_id),
    INDEX idx_inv_status   (status),
    CONSTRAINT fk_inv_customer FOREIGN KEY (customer_id)      REFERENCES customers (id),
    CONSTRAINT fk_inv_booking  FOREIGN KEY (booking_id)       REFERENCES bookings (id) ON DELETE SET NULL,
    CONSTRAINT fk_inv_visa     FOREIGN KEY (visa_app_id)      REFERENCES visa_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_inv_edu      FOREIGN KEY (education_app_id) REFERENCES education_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_inv_created  FOREIGN KEY (created_by)       REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 24. PAYMENTS
-- ============================================================
CREATE TABLE payments (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_ref    VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. PAY-000001',
    invoice_id     INT UNSIGNED NOT NULL,
    customer_id    INT UNSIGNED NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    currency       CHAR(3) DEFAULT 'USD',
    method         ENUM('cash','bank_transfer','card','cheque','online','mobile_money','other') NOT NULL,
    status         ENUM('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(200) COMMENT 'Gateway transaction ID',
    gateway        VARCHAR(80),
    payment_date   DATE NOT NULL,
    notes          TEXT,
    recorded_by    INT UNSIGNED,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_pay_invoice  (invoice_id),
    INDEX idx_pay_customer (customer_id),
    CONSTRAINT fk_pay_invoice  FOREIGN KEY (invoice_id)  REFERENCES invoices (id),
    CONSTRAINT fk_pay_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_pay_recorded FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 25. REFUNDS
-- ============================================================
CREATE TABLE refunds (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refund_ref   VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. REF-000001',
    payment_id   INT UNSIGNED NOT NULL,
    customer_id  INT UNSIGNED NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    reason       TEXT NOT NULL,
    method       ENUM('cash','bank_transfer','card','online','mobile_money','other') NOT NULL,
    status       ENUM('requested','approved','processing','completed','rejected') DEFAULT 'requested',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by  INT UNSIGNED,
    approved_at  TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    notes        TEXT,

    INDEX idx_ref_payment  (payment_id),
    INDEX idx_ref_customer (customer_id),
    CONSTRAINT fk_ref_payment  FOREIGN KEY (payment_id)  REFERENCES payments (id),
    CONSTRAINT fk_ref_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_ref_approved FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 26. COMMISSIONS
-- ============================================================
CREATE TABLE commissions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id          INT UNSIGNED NOT NULL,
    booking_id        INT UNSIGNED,
    visa_app_id       INT UNSIGNED,
    education_app_id  INT UNSIGNED,
    partner_id        INT UNSIGNED,
    commission_type   ENUM('agent','partner','referral') DEFAULT 'agent',
    rate              DECIMAL(5,2) NOT NULL,
    base_amount       DECIMAL(12,2) NOT NULL,
    commission_amount DECIMAL(12,2) NOT NULL,
    currency          CHAR(3) DEFAULT 'USD',
    status            ENUM('pending','approved','paid','cancelled') DEFAULT 'pending',
    paid_date         DATE,
    notes             TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_com_agent (agent_id),
    CONSTRAINT fk_com_agent   FOREIGN KEY (agent_id)         REFERENCES users (id),
    CONSTRAINT fk_com_booking FOREIGN KEY (booking_id)       REFERENCES bookings (id) ON DELETE SET NULL,
    CONSTRAINT fk_com_visa    FOREIGN KEY (visa_app_id)      REFERENCES visa_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_com_edu     FOREIGN KEY (education_app_id) REFERENCES education_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_com_partner FOREIGN KEY (partner_id)       REFERENCES partners (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 27. INQUIRIES
-- ============================================================
CREATE TABLE inquiries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_ref VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. INQ-000001',
    customer_id INT UNSIGNED,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(180),
    phone       VARCHAR(30),
    subject     VARCHAR(200),
    message     TEXT NOT NULL,
    category    ENUM('visa','education','booking','travel','general','complaint','other') DEFAULT 'general',
    status      ENUM('new','open','replied','resolved','closed') DEFAULT 'new',
    assigned_to INT UNSIGNED,
    source      ENUM('website','phone','email','walk_in','social','referral') DEFAULT 'email',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_inq_customer (customer_id),
    INDEX idx_inq_status   (status),
    CONSTRAINT fk_inq_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_inq_assigned FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 28. APPOINTMENTS  (general office appointments)
-- ============================================================
CREATE TABLE appointments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_ref     VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. APT-000001',
    customer_id         INT UNSIGNED,
    staff_id            INT UNSIGNED,
    appointment_type    ENUM('visa_consult','education_consult','travel_planning','document_review','follow_up','other') DEFAULT 'other',
    visa_application_id INT UNSIGNED,
    education_app_id    INT UNSIGNED,
    appointment_date    DATE NOT NULL,
    appointment_time    TIME NOT NULL,
    duration_mins       TINYINT UNSIGNED DEFAULT 30,
    status              ENUM('scheduled','confirmed','completed','cancelled','no_show','rescheduled') DEFAULT 'scheduled',
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_apt_customer (customer_id),
    INDEX idx_apt_staff    (staff_id),
    INDEX idx_apt_date     (appointment_date),
    CONSTRAINT fk_apt_customer FOREIGN KEY (customer_id)         REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_apt_staff    FOREIGN KEY (staff_id)            REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_apt_visa     FOREIGN KEY (visa_application_id) REFERENCES visa_applications (id) ON DELETE SET NULL,
    CONSTRAINT fk_apt_edu      FOREIGN KEY (education_app_id)    REFERENCES education_applications (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 29. REVIEWS
-- ============================================================
CREATE TABLE reviews (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT UNSIGNED NOT NULL,
    service_type ENUM('visa','education','booking','general') NOT NULL,
    rating       TINYINT UNSIGNED NOT NULL COMMENT '1-5 stars',
    title        VARCHAR(200),
    body         TEXT,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_rev_customer (customer_id),
    INDEX idx_rev_service  (service_type),
    CONSTRAINT fk_rev_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 30. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    customer_id INT UNSIGNED,
    type        VARCHAR(80)  NOT NULL,
    title       VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    link        VARCHAR(500),
    is_read     BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notif_user     (user_id),
    INDEX idx_notif_customer (customer_id),
    INDEX idx_notif_read     (is_read),
    CONSTRAINT fk_notif_user     FOREIGN KEY (user_id)     REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 31. ACTIVITY LOGS  (immutable audit trail)
-- ============================================================
CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80),
    entity_id   INT UNSIGNED,
    description TEXT,
    ip_address  VARCHAR(45),
    user_agent  VARCHAR(500),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_log_user   (user_id),
    INDEX idx_log_entity (entity_type, entity_id),
    INDEX idx_log_time   (created_at),
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ██████████  SEED DATA  ██████████
-- ============================================================

-- Roles
INSERT INTO roles (name, slug, description) VALUES
('Super Admin',       'super_admin',       'Full unrestricted system access'),
('Admin',             'admin',             'Administrative access to all modules'),
('Visa Officer',      'visa_officer',      'Manages visa applications and appointments'),
('Education Officer', 'education_officer', 'Manages education applications and institutions'),
('Travel Agent',      'travel_agent',      'Manages bookings, packages and destinations'),
('Finance Officer',   'finance_officer',   'Manages invoices, payments and refunds'),
('Receptionist',      'receptionist',      'Front desk — inquiries and appointment scheduling');

-- Users (password_hash = bcrypt of "password123")
INSERT INTO users (name, email, password_hash, role_id, status) VALUES
('System Admin',    'admin@travelagency.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active'),
('Ahmed Al-Rashid', 'ahmed@travelagency.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'active'),
('Sara Johnson',    'sara@travelagency.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'active'),
('Marcus Williams', 'marcus@travelagency.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 'active'),
('Layla Hassan',    'layla@travelagency.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 'active'),
('Omar Farouk',     'omar@travelagency.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 7, 'active');

-- Visa Types
INSERT INTO visa_types (name, country, category, processing_time, validity, max_stay, fee, service_fee, currency, is_active) VALUES
('UK Tourist Visa',           'United Kingdom', 'tourist',  '15 working days', '6 months',  '180 days',          115.00, 75.00,  'USD', TRUE),
('UK Student Visa (Tier 4)',   'United Kingdom', 'student',  '3 weeks',         '1-5 years', 'Course duration',   490.00, 150.00, 'USD', TRUE),
('UK Business Visa',           'United Kingdom', 'business', '15 working days', '6 months',  '6 months',          115.00, 75.00,  'USD', TRUE),
('UK Family Visit Visa',       'United Kingdom', 'family',   '15 working days', '6 months',  '6 months',          115.00, 75.00,  'USD', TRUE),
('Schengen Tourist Visa',      'EU / Schengen',  'tourist',  '10-15 working days','90 days', '90 days',            90.00, 80.00,  'USD', TRUE),
('UAE Tourist Visa (30 days)', 'UAE',            'tourist',  '3-5 working days', '30 days',  '30 days',           105.00, 50.00,  'USD', TRUE),
('UAE Residence Visa',         'UAE',            'work',     '2-4 weeks',        '2 years',  '2 years',           350.00, 120.00, 'USD', TRUE),
('Canada Visitor Visa',        'Canada',         'tourist',  '2-8 weeks',        '10 years', '6 months',          100.00, 100.00, 'USD', TRUE),
('Canada Student Permit',      'Canada',         'student',  '4-8 weeks',        'Varies',   'Course duration',   150.00, 150.00, 'USD', TRUE),
('USA B1/B2 Tourist Visa',     'USA',            'tourist',  '4-8 weeks',        '10 years', '6 months',          185.00, 120.00, 'USD', TRUE),
('Australia Tourist Visa',     'Australia',      'tourist',  '1-8 weeks',        '12 months','90 days',           145.00, 100.00, 'USD', TRUE),
('Malaysia eVisa',             'Malaysia',       'tourist',  '1-3 days',         '30 days',  '30 days',            25.00,  30.00, 'USD', TRUE);

-- Visa Requirements (UK Tourist Visa = id 1)
INSERT INTO visa_requirements (visa_type_id, document_name, is_mandatory, sort_order) VALUES
(1, 'Valid Passport (min 6 months validity)', TRUE,  1),
(1, 'Passport-size Photographs (2)',          TRUE,  2),
(1, 'Bank Statements (last 6 months)',        TRUE,  3),
(1, 'Employment Letter / Payslips',           TRUE,  4),
(1, 'Travel Itinerary',                       TRUE,  5),
(1, 'Accommodation Proof',                    TRUE,  6),
(1, 'Travel Insurance',                       FALSE, 7),
(1, 'Return Flight Ticket',                   FALSE, 8);

-- UK Student Visa requirements (id 2)
INSERT INTO visa_requirements (visa_type_id, document_name, is_mandatory, sort_order) VALUES
(2, 'Valid Passport',                         TRUE,  1),
(2, 'CAS Number from University',             TRUE,  2),
(2, 'Proof of English Proficiency (IELTS)',   TRUE,  3),
(2, 'Financial Evidence (tuition + living)',  TRUE,  4),
(2, 'Academic Certificates / Transcripts',   TRUE,  5),
(2, 'Tuberculosis Test Results',              FALSE, 6),
(2, 'Passport Photographs',                  TRUE,  7);

-- Destinations
INSERT INTO destinations (name, country, region, is_popular, is_active) VALUES
('Bali',         'Indonesia',      'Asia Pacific',  TRUE,  TRUE),
('Paris',        'France',         'Europe',        TRUE,  TRUE),
('London',       'United Kingdom', 'Europe',        TRUE,  TRUE),
('Dubai',        'UAE',            'Middle East',   TRUE,  TRUE),
('Kyoto',        'Japan',          'Asia Pacific',  TRUE,  TRUE),
('New York',     'USA',            'North America', TRUE,  TRUE),
('Toronto',      'Canada',         'North America', FALSE, TRUE),
('Sydney',       'Australia',      'Asia Pacific',  TRUE,  TRUE),
('Maldives',     'Maldives',       'Asia Pacific',  TRUE,  TRUE),
('Istanbul',     'Turkey',         'Europe / Asia', TRUE,  TRUE),
('Kuala Lumpur', 'Malaysia',       'Asia Pacific',  FALSE, TRUE),
('Rome',         'Italy',          'Europe',        TRUE,  TRUE),
('Barcelona',    'Spain',          'Europe',        FALSE, TRUE),
('Cape Town',    'South Africa',   'Africa',        FALSE, TRUE),
('Singapore',    'Singapore',      'Asia Pacific',  FALSE, TRUE);

-- Education Institutions
INSERT INTO education_institutions (name, country, city, website, status) VALUES
('University of London',           'United Kingdom', 'London',    'https://london.ac.uk',            'active'),
('University of Toronto',          'Canada',         'Toronto',   'https://utoronto.ca',             'active'),
('University of New South Wales',  'Australia',      'Sydney',    'https://unsw.edu.au',             'active'),
('Coventry University',            'United Kingdom', 'Coventry',  'https://coventry.ac.uk',          'active'),
('University of British Columbia', 'Canada',         'Vancouver', 'https://ubc.ca',                  'active'),
('Monash University',              'Australia',      'Melbourne', 'https://monash.edu',              'active'),
('Kaplan International',           'United Kingdom', 'London',    'https://kaplaninternational.com', 'active'),
('INTO City',                      'United Kingdom', 'London',    'https://intostudy.com',           'active');

-- Sample Programs
INSERT INTO education_programs (institution_id, name, degree_level, field, duration_months, tuition_fee, currency, start_dates, status) VALUES
(1, 'MSc Data Science',              'masters',   'Computer Science',  12,  28000.00, 'GBP', 'September, January',  'active'),
(1, 'MBA International Business',    'masters',   'Business',          12,  32000.00, 'GBP', 'September',           'active'),
(2, 'BSc Computer Science',          'bachelors', 'Computer Science',  48,  55000.00, 'CAD', 'September, January',  'active'),
(3, 'Master of Engineering',         'masters',   'Engineering',       18,  42000.00, 'AUD', 'February, July',      'active'),
(4, 'BEng Mechanical Engineering',   'bachelors', 'Engineering',       36,  19500.00, 'GBP', 'September',           'active'),
(7, 'General English (B2)',          'language',  'Language',          3,    4500.00, 'GBP', 'Monthly',             'active'),
(7, 'IELTS Preparation',             'language',  'Language',          2,    3200.00, 'GBP', 'Monthly',             'active'),
(8, 'Pre-Sessional English 10 weeks','language',  'Language',          3,    5200.00, 'GBP', 'June, July, August',  'active');

-- Partners
INSERT INTO partners (name, type, country, commission_rate, status) VALUES
('Emirates Airlines',     'airline',       'UAE',            3.00, 'active'),
('Qatar Airways',         'airline',       'Qatar',          3.00, 'active'),
('Marriott Hotels',       'hotel',         'USA',            8.00, 'active'),
('Hilton Hotels',         'hotel',         'USA',            8.00, 'active'),
('G Adventures',          'tour_operator', 'Canada',        10.00, 'active'),
('Intrepid Travel',       'tour_operator', 'Australia',     10.00, 'active'),
('TravelPort B2B',        'b2b_agent',     'United Kingdom', 5.00, 'active'),
('AXA Travel Insurance',  'insurance',     'France',        12.00, 'active');

-- Sample Customers
INSERT INTO customers (customer_code, first_name, last_name, email, phone, nationality, passport_number, passport_expiry_date, gender, status, assigned_agent_id) VALUES
('CUS-000001', 'John',    'Doe',       'john.doe@email.com',     '+1-555-0101',      'American',   'US123456789', '2028-06-15', 'male',   'active', 4),
('CUS-000002', 'Sarah',   'Smith',     'sarah.smith@email.com',  '+44-7700-900123',  'British',    'GB987654321', '2027-11-20', 'female', 'active', 4),
('CUS-000003', 'Michael', 'Brown',     'mbrown@email.com',       '+61-400-123456',   'Australian', 'AU456789012', '2029-03-08', 'male',   'active', 4),
('CUS-000004', 'Aisha',   'Lawal',     'aisha.lawal@email.com',  '+234-80-12345678', 'Nigerian',   'NG111222333', '2026-12-01', 'female', 'active', 2),
('CUS-000005', 'Ravi',    'Kumar',     'ravi.kumar@email.com',   '+91-98765-43210',  'Indian',     'IN777888999', '2027-07-30', 'male',   'active', 2),
('CUS-000006', 'Ahmed',   'Al-Rashid', 'ahmed.rashid@email.com', '+971-55-1234567',  'Emirati',    'AE444555666', '2028-09-14', 'male',   'active', 2),
('CUS-000007', 'Amina',   'Diallo',    'amina.diallo@email.com', '+221-77-1234567',  'Senegalese', 'SN999000111', '2027-02-28', 'female', 'active', 3),
('CUS-000008', 'Carlos',  'Neto',      'carlos.neto@email.com',  '+55-11-91234567',  'Brazilian',  'BR222333444', '2029-05-17', 'male',   'active', 4);

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- END OF SCHEMA
-- ============================================================
