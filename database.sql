CREATE DATABASE IF NOT EXISTS arogya_solar;
USE arogya_solar;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operator_name VARCHAR(100),
    group_name VARCHAR(100),
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    mobile VARCHAR(15),
    ifsc_code VARCHAR(20),
    electricity_id VARCHAR(50),
    kw DECIMAL(5,2),
    account_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS installations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    address TEXT,
    district_name VARCHAR(100),
    invoice_no VARCHAR(50),
    material_dispatch_1st DATE,
    material_dispatch_2nd DATE,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    total_amount DECIMAL(10,2),
    due_amount DECIMAL(10,2),
    payment_received DECIMAL(10,2),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some dummy data
INSERT INTO customers (operator_name, group_name, name, email, mobile, kw) VALUES
('Shubham Mishra', 'CPD Surya Mitr', 'ASHOK KUMAR', 'ashok@example.com', '7068591262', 2.00),
('Shubham Mishra', 'CPD SOLAR', 'RAJESH KUMAR', 'rajesh@example.com', '9936884369', 2.00),
('Shubham Mishra', 'RESCO BHARIYA', 'RAM SUMER', 'ram@example.com', '9935316084', 2.00);

INSERT INTO installations (customer_id, address, district_name, invoice_no, material_dispatch_1st, material_dispatch_2nd) VALUES
(1, 'MOHAMMAD PUR MAGANPUR', 'PRAYAGRAJ', 'CPDV240001304', '2025-11-13', '2025-11-14');

INSERT INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES
(1, 130000.00, 130000.00, 0.00),
(2, 130000.00, 130000.00, 0.00),
(3, 130000.00, 130000.00, 0.00);
