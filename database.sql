-- =====================================================
-- AROGYA Solar Power - Complete Production Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS arogya_solar;
USE arogya_solar;

-- -------------------------------------------------------
-- USERS (Admin accounts)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('superadmin','admin','operator') DEFAULT 'admin',
    is_active  TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- GROUPS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS groups (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- VILLAGES
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS villages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    gram_panchayat  VARCHAR(150),
    district        VARCHAR(100),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- GRAM PANCHAYATS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS gram_panchayats (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    district   VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- PRODUCTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    brand       VARCHAR(100),
    category    VARCHAR(100),
    unit        VARCHAR(50),
    price       DECIMAL(10,2) DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- CUSTOMERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    operator_name    VARCHAR(100),
    group_id         INT,
    group_name       VARCHAR(100),
    name             VARCHAR(150) NOT NULL,
    email            VARCHAR(150),
    mobile           VARCHAR(15),
    ifsc_code        VARCHAR(20),
    electricity_id   VARCHAR(50),
    kw               DECIMAL(5,2),
    account_number   VARCHAR(50),
    village_id       INT,
    gram_panchayat_id INT,
    district_name    VARCHAR(100),
    status           ENUM('active','pending','not_interested','completed') DEFAULT 'active',
    remarks          TEXT,
    followup_remarks TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL
);

-- -------------------------------------------------------
-- INSTALLATIONS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS installations (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    customer_id          INT NOT NULL,
    address              TEXT,
    district_name        VARCHAR(100),
    invoice_no           VARCHAR(50),
    material_dispatch_1st DATE,
    material_dispatch_2nd DATE,
    installation_date    DATE,
    net_metering_date    DATE,
    online_date          DATE,
    subsidy_1st_date     DATE,
    subsidy_2nd_date     DATE,
    jan_samarth_date     DATE,
    status               ENUM('pending','completed') DEFAULT 'pending',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- PAYMENTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT NOT NULL,
    total_amount     DECIMAL(12,2) DEFAULT 0.00,
    due_amount       DECIMAL(12,2) DEFAULT 0.00,
    payment_received DECIMAL(12,2) DEFAULT 0.00,
    payment_date     DATE,
    payment_mode     ENUM('cash','online','cheque','neft') DEFAULT 'online',
    transaction_no   VARCHAR(100),
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- DISPATCH (materials dispatched log)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS dispatches (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT NOT NULL,
    dispatch_no    VARCHAR(50),
    dispatch_date  DATE,
    type           ENUM('first','second') NOT NULL,
    product_id     INT,
    quantity       INT DEFAULT 1,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id)  ON DELETE SET NULL
);

-- -------------------------------------------------------
-- COMPLAINTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    complaint_type  VARCHAR(100),
    description     TEXT,
    status          ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    resolved_note   TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- LEADS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    mobile      VARCHAR(15),
    email       VARCHAR(150),
    address     TEXT,
    kw_interest DECIMAL(5,2),
    source      VARCHAR(100),
    status      ENUM('new','contacted','interested','converted','lost') DEFAULT 'new',
    notes       TEXT,
    assigned_to INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- CUSTOMER FOLLOW UP
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS followups (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    followup_by INT,
    notes       TEXT,
    next_date   DATE,
    status      ENUM('pending','done') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- ACCESS CONTROL (roles & permissions)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS access_permissions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    role        ENUM('superadmin','admin','operator') NOT NULL,
    module      VARCHAR(100) NOT NULL,
    can_view    TINYINT(1) DEFAULT 1,
    can_create  TINYINT(1) DEFAULT 0,
    can_edit    TINYINT(1) DEFAULT 0,
    can_delete  TINYINT(1) DEFAULT 0,
    UNIQUE KEY role_module (role, module)
);

-- =====================================================
-- SEED DATA
-- =====================================================

-- Admin Users (password = admin123 — bcrypt hashed)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin One',   'admin1@arogya.com', '$2y$10$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'superadmin'),
('Admin Two',   'admin2@arogya.com', '$2y$10$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'admin'),
('Admin Three', 'admin3@arogya.com', '$2y$10$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'admin');

-- Groups
INSERT IGNORE INTO groups (name) VALUES
('CPD Surya Mitr'), ('CPD SOLAR'), ('RESCO BHARIYA');

-- Sample Customers
INSERT IGNORE INTO customers (operator_name, group_name, name, email, mobile, kw, status) VALUES
('Shubham Mishra', 'CPD Surya Mitr', 'ASHOK KUMAR',  'ashok@example.com',  '7068591262', 2.00, 'active'),
('Shubham Mishra', 'CPD SOLAR',      'RAJESH KUMAR', 'rajesh@example.com', '9936884369', 2.00, 'active'),
('Shubham Mishra', 'RESCO BHARIYA',  'RAM SUMER',    'ram@example.com',    '9935316084', 2.00, 'active');

-- Sample Installations
INSERT IGNORE INTO installations (customer_id, address, district_name, invoice_no, material_dispatch_1st, material_dispatch_2nd) VALUES
(1, 'MOHAMMAD PUR MAGANPUR', 'PRAYAGRAJ', 'CPDV240001304', '2025-11-13', '2025-11-14');

-- Sample Payments
INSERT IGNORE INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES
(1, 130000.00, 130000.00, 0.00),
(2, 130000.00, 130000.00, 0.00),
(3, 130000.00, 130000.00, 0.00);
