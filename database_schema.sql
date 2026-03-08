-- ============================================
-- CLINIC MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Master Database + Clinic Databases
-- Version 2.0.0
-- ============================================

-- ============================================
-- CREATE MASTER DATABASE
-- ============================================

CREATE DATABASE IF NOT EXISTS clinic_management_master 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE clinic_management_master;

-- ============================================
-- TABLE: clinics
-- ============================================
CREATE TABLE IF NOT EXISTS clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_name VARCHAR(255) NOT NULL,
    database_name VARCHAR(100) UNIQUE NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    address TEXT,
    logo_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_database (database_name),
    INDEX idx_active (is_active),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: users (Admin + Clinic Users)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'clinic') DEFAULT 'clinic',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    
    INDEX idx_email (email),
    INDEX idx_clinic (clinic_id),
    INDEX idx_type (user_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: user_activity_logs
-- ============================================
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- ============================================
-- Password: admin123 (hashed with bcrypt)
INSERT INTO users (full_name, email, password, user_type, clinic_id, is_active) 
VALUES (
    'System Administrator', 
    'admin@clinic.com', 
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5jtI3zUIheDpm', 
    'admin', 
    NULL,
    1
) ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- SAMPLE CLINIC (Optional - for testing)
-- ============================================
INSERT INTO clinics (clinic_name, database_name, contact_email, contact_phone, address, is_active)
VALUES (
    'Demo Dental Clinic',
    'clinic_demo_dental',
    'demo@clinic.com',
    '+1-234-567-8900',
    '123 Main Street, Medical Plaza, Suite 200',
    1
) ON DUPLICATE KEY UPDATE clinic_name=clinic_name;

-- Get the clinic ID for the demo clinic
SET @demo_clinic_id = LAST_INSERT_ID();

-- Create demo clinic user
-- Password: clinic123
INSERT INTO users (clinic_id, full_name, email, password, user_type, is_active)
VALUES (
    @demo_clinic_id,
    'Demo Clinic Admin',
    'demo@clinic.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'clinic',
    1
) ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- CREATE DEMO CLINIC DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS clinic_demo_dental
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE clinic_demo_dental;

-- ============================================
-- CLINIC DATABASE SCHEMA
-- (This schema is replicated for each clinic)
-- ============================================

-- TABLE: patients
CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_uid VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    contact_number VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    date_of_visit DATE,
    total_visit INT DEFAULT 1,
    notes TEXT,
    total_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('paid','partial','pending') DEFAULT 'pending',
    payment_pending DECIMAL(10,2) DEFAULT 0,
    payment_received DECIMAL(10,2) DEFAULT 0,
    chief_complain TEXT,
    medical_history TEXT,
    oral_diet_habit TEXT,
    family_history TEXT,
    xray_remark TEXT,
    blood_group VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_patient_uid (patient_uid),
    INDEX idx_date (date_of_visit),
    INDEX idx_name (name),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created (created_at),
    FULLTEXT idx_search (name, contact_number, patient_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: prescriptions
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    prescription_date DATE NOT NULL,
    diagnosis TEXT,
    symptoms TEXT,
    medicines TEXT,
    dosage TEXT,
    duration TEXT,
    instructions TEXT,
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_patient (patient_id),
    INDEX idx_date (prescription_date),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: treatment_categories
CREATE TABLE IF NOT EXISTS treatment_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default treatment categories
INSERT INTO treatment_categories (category_name, description) VALUES 
('Preventive', 'Preventive dental care including cleaning, fluoride treatment'),
('Restorative', 'Fillings, crowns, bridges'),
('Endodontics', 'Root canal treatment'),
('Periodontics', 'Gum disease treatment'),
('Orthodontics', 'Braces, aligners, retainers'),
('Prosthodontics', 'Dentures, implants'),
('Oral Surgery', 'Extractions, surgical procedures'),
('Cosmetic', 'Whitening, veneers, bonding'),
('Pediatric', 'Children dental care'),
('Emergency', 'Emergency dental treatment');

-- TABLE: treatments
CREATE TABLE IF NOT EXISTS treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    treatment_name VARCHAR(255) NOT NULL,
    treatment_date DATE NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0,
    status ENUM('planned','in_progress','completed','cancelled') DEFAULT 'planned',
    tooth_number VARCHAR(10),
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_patient (patient_id),
    INDEX idx_date (treatment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: medicine
CREATE TABLE IF NOT EXISTS medicine (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medicine_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    composition TEXT,
    manufacturer VARCHAR(255),
    quantity INT DEFAULT 0,
    unit VARCHAR(50),
    price DECIMAL(10,2),
    expiry_date DATE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (medicine_name),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: doses (Prescription dosage options)
CREATE TABLE IF NOT EXISTS doses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dose_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default dose values
INSERT INTO doses (dose_name) VALUES 
('1-0-0'), ('0-1-0'), ('0-0-1'),
('1-1-0'), ('1-0-1'), ('0-1-1'),
('1-1-1'), ('1-1-1-1'),
('As needed'), ('Before meals'), ('After meals');

-- TABLE: durations (Prescription duration options)
CREATE TABLE IF NOT EXISTS durations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    duration_value VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default duration values
INSERT INTO durations (duration_value) VALUES 
('1 day'), ('2 days'), ('3 days'), ('5 days'), ('7 days'),
('10 days'), ('14 days'), ('21 days'), ('1 month'), ('Until finished');

-- TABLE: expense_categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: expenses
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    payment_method ENUM('cash','card','upi','bank_transfer') DEFAULT 'cash',
    receipt_number VARCHAR(100),
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    
    INDEX idx_category (category),
    INDEX idx_date (expense_date),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: work_types (Service/Treatment catalog)
CREATE TABLE IF NOT EXISTS work_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_name VARCHAR(255) NOT NULL,
    work_code VARCHAR(50),
    cost DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    category VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default work types
INSERT INTO work_types (work_name, cost, description) VALUES 
('Consultation', 500, 'Initial dental consultation'),
('Cleaning', 1000, 'Professional teeth cleaning'),
('Extraction', 1500, 'Tooth extraction'),
('Root Canal', 5000, 'Root canal treatment'),
('Filling', 2000, 'Dental filling'),
('Crown', 8000, 'Dental crown placement'),
('Whitening', 3000, 'Teeth whitening treatment'),
('Implant', 25000, 'Dental implant'),
('Braces', 50000, 'Orthodontic braces'),
('X-Ray', 500, 'Dental X-ray');

-- TABLE: work_done (Patient work/service records)
CREATE TABLE IF NOT EXISTS work_done (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_uid VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    work_name VARCHAR(255) NOT NULL,
    work_date DATE NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) DEFAULT 0,
    tooth_number VARCHAR(10),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_patient_uid (patient_uid),
    INDEX idx_work_date (work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: patient_work_done (Legacy - Kept for reference, use work_done instead)
CREATE TABLE IF NOT EXISTS patient_work_done (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    work_done_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    work_date DATE NOT NULL,
    tooth_number VARCHAR(10),
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (work_done_id) REFERENCES work_types(id) ON DELETE CASCADE,
    
    INDEX idx_patient (patient_id),
    INDEX idx_work (work_done_id),
    INDEX idx_date (work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: certificate (Medical certificates issued to patients)
CREATE TABLE IF NOT EXISTS certificate (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_uid VARCHAR(50) NOT NULL,
    certificate_for VARCHAR(255) NOT NULL,
    certificate_details TEXT NOT NULL,
    issue_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_uid) REFERENCES patients(patient_uid) ON DELETE CASCADE,
    
    INDEX idx_patient (patient_uid),
    INDEX idx_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    patient_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20),
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose TEXT,
    status ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    
    INDEX idx_patient (patient_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: payments
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash','card','upi','bank_transfer','cheque') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_patient (patient_id),
    INDEX idx_date (payment_date),
    INDEX idx_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA FOR DEMO CLINIC
-- ============================================

-- Default expense categories
INSERT INTO expense_categories (category_name, description, is_active) VALUES
('Salary', 'Staff salaries and wages', 1),
('Rent', 'Office/clinic rent', 1),
('Utilities', 'Electricity, water, internet', 1),
('Medical Supplies', 'Medical equipment and supplies', 1),
('Maintenance', 'Equipment and facility maintenance', 1),
('Marketing', 'Advertising and promotion', 1),
('Other', 'Miscellaneous expenses', 1);

-- Default work done types
INSERT INTO work_done (work_name, work_code, cost, category, is_active) VALUES
('Consultation', 'CONS-001', 500.00, 'Consultation', 1),
('Teeth Cleaning', 'CLEAN-001', 1500.00, 'Cleaning', 1),
('Tooth Extraction', 'EXTR-001', 2000.00, 'Surgery', 1),
('Root Canal Treatment', 'RCT-001', 5000.00, 'Endodontics', 1),
('Dental Filling', 'FILL-001', 1200.00, 'Restorative', 1),
('Crown Placement', 'CROWN-001', 8000.00, 'Restorative', 1),
('Teeth Whitening', 'WHITE-001', 3000.00, 'Cosmetic', 1),
('Dental Implant', 'IMPL-001', 25000.00, 'Surgery', 1),
('Braces', 'BRACE-001', 40000.00, 'Orthodontics', 1),
('X-Ray', 'XRAY-001', 300.00, 'Diagnostic', 1);

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View: Daily revenue
CREATE OR REPLACE VIEW daily_revenue AS
SELECT 
    DATE(created_at) as date,
    COUNT(DISTINCT id) as patient_count,
    SUM(total_amount) as total_revenue,
    SUM(payment_received) as received_amount,
    SUM(payment_pending) as pending_amount
FROM patients
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View: Payment summary
CREATE OR REPLACE VIEW payment_summary AS
SELECT 
    payment_status,
    COUNT(*) as patient_count,
    SUM(total_amount) as total_amount,
    SUM(payment_received) as received,
    SUM(payment_pending) as pending
FROM patients
GROUP BY payment_status;

-- ============================================
-- STORED PROCEDURES (Optional)
-- ============================================

DELIMITER //

-- Procedure to update patient payment
CREATE PROCEDURE IF NOT EXISTS update_patient_payment(
    IN p_patient_id INT,
    IN p_amount DECIMAL(10,2)
)
BEGIN
    UPDATE patients 
    SET 
        payment_received = payment_received + p_amount,
        payment_pending = payment_pending - p_amount,
        payment_status = CASE 
            WHEN (payment_pending - p_amount) <= 0 THEN 'paid'
            WHEN (payment_pending - p_amount) < total_amount THEN 'partial'
            ELSE 'pending'
        END
    WHERE id = p_patient_id;
END //

DELIMITER ;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 'Database schema created successfully!' as status;

-- ============================================
-- CREDENTIALS:
-- Admin: admin@clinic.com / admin123
-- Demo Clinic: demo@clinic.com / clinic123
-- ============================================
