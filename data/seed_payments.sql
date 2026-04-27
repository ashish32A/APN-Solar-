-- =====================================================
-- Seed Data: Payment Due & Received records
-- =====================================================

USE arogya_solar;

-- -------------------------------------------------------
-- Add sample customers with different operator names
-- -------------------------------------------------------
INSERT IGNORE INTO customers (operator_name, group_name, name, mobile, email, district_name, electricity_id, kw, status, remarks) VALUES
('Shubham Mishra',  'CPD Surya Mitr',  'SURESH VERMA',    '9876543210', 'suresh@example.com',   'LUCKNOW',      'LKO-2024-001', 3.00, 'active', 'Payment pending'),
('Ramesh Gupta',    'CPD SOLAR',       'MOHAN LAL',       '9812345678', 'mohan@example.com',    'PRAYAGRAJ',    'PRG-2024-002', 2.00, 'active', 'Subsidy awaited'),
('Anita Singh',     'RESCO BHARIYA',   'KAMLA DEVI',      '9988776655', 'kamla@example.com',    'VARANASI',     'VNS-2024-003', 1.00, 'active', 'Documentation complete'),
('Shubham Mishra',  'CPD Surya Mitr',  'RAVI SHANKAR',    '9001234567', 'ravi@example.com',     'ALLAHABAD',    'ALD-2024-004', 2.00, 'active', 'Bank details pending'),
('Ramesh Gupta',    'CPD SOLAR',       'GEETA BAI',       '9123456780', 'geeta@example.com',    'KANPUR',       'KNP-2024-005', 3.00, 'active', 'Installation done'),
('Anita Singh',     'CPD Surya Mitr',  'DINESH KUMAR',    '9234567801', 'dinesh@example.com',   'AGRA',         'AGR-2024-006', 1.00, 'active', 'Cash payment pending'),
('Shubham Mishra',  'RESCO BHARIYA',   'PRIYA SHARMA',    '9345678012', 'priya@example.com',    'MATHURA',      'MTH-2024-007', 2.00, 'active', 'Metering done'),
('Ramesh Gupta',    'CPD SOLAR',       'VIJAY SINGH',     '9456780123', 'vijay@example.com',    'GORAKHPUR',    'GKP-2024-008', 4.00, 'active', 'Subsidy processed'),
('Anita Singh',     'CPD Surya Mitr',  'SUNITA DEVI',     '9567801234', 'sunita@example.com',   'BAREILLY',     'BLY-2024-009', 2.00, 'active', 'Balance pending'),
('Shubham Mishra',  'CPD SOLAR',       'ANIL KUMAR',      '9678012345', 'anil@example.com',     'MEERUT',       'MRT-2024-010', 3.00, 'active', 'Fully paid');

-- -------------------------------------------------------
-- Payment records (using only columns that exist):
-- id, customer_id, total_amount, due_amount, payment_received, updated_at
-- due_amount > 0   → appears in payment_due list
-- payment_received > 0 → appears in payment_received list
-- -------------------------------------------------------

-- SURESH VERMA: fully due
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 130000.00, 0.00 FROM customers WHERE mobile = '9876543210' LIMIT 1;

-- MOHAN LAL: partial payment
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 80000.00, 50000.00 FROM customers WHERE mobile = '9812345678' LIMIT 1;

-- KAMLA DEVI: fully due
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 80000.00, 80000.00, 0.00 FROM customers WHERE mobile = '9988776655' LIMIT 1;

-- RAVI SHANKAR: partial (mostly paid)
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 30000.00, 100000.00 FROM customers WHERE mobile = '9001234567' LIMIT 1;

-- GEETA BAI: fully paid
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 0.00, 130000.00 FROM customers WHERE mobile = '9123456780' LIMIT 1;

-- DINESH KUMAR: fully due
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 80000.00, 80000.00, 0.00 FROM customers WHERE mobile = '9234567801' LIMIT 1;

-- PRIYA SHARMA: 50/50 partial
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 65000.00, 65000.00 FROM customers WHERE mobile = '9345678012' LIMIT 1;

-- VIJAY SINGH: fully paid
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 200000.00, 0.00, 200000.00 FROM customers WHERE mobile = '9456780123' LIMIT 1;

-- SUNITA DEVI: partial
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 130000.00, 50000.00, 80000.00 FROM customers WHERE mobile = '9567801234' LIMIT 1;

-- ANIL KUMAR: fully paid
INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
SELECT id, 150000.00, 0.00, 150000.00 FROM customers WHERE mobile = '9678012345' LIMIT 1;

-- Update existing 3 sample customers' payments
UPDATE payments SET payment_received = 0.00,    due_amount = 130000.00 WHERE customer_id = 1;
UPDATE payments SET payment_received = 40000.00, due_amount = 90000.00  WHERE customer_id = 2;
UPDATE payments SET payment_received = 130000.00,due_amount = 0.00      WHERE customer_id = 3;
