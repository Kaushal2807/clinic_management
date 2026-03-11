-- ============================================
-- CLINIC MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Single Database Architecture
-- All tables in one database: clinic_management
-- Version 4.0.0
-- ============================================

CREATE DATABASE IF NOT EXISTS clinic_management 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE clinic_management;

-- ============================================
-- TABLE: clinics
-- ============================================
CREATE TABLE IF NOT EXISTS clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_name VARCHAR(255) NOT NULL,
    clinic_prefix VARCHAR(10) UNIQUE NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    address TEXT,
    logo_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_prefix (clinic_prefix),
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
    '$2y$12$jQGLbxjd4n.b5zh7k//3UO5WPWZiLxIJhs5kUr4h0L05aOw9CWiAC', 
    'admin', 
    NULL,
    1
) ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- TABLE: patients
-- ============================================
CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
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
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient_uid (patient_uid),
    INDEX idx_date (date_of_visit),
    INDEX idx_name (name),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created (created_at),
    FULLTEXT idx_search (name, contact_number, patient_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: medicine
-- ============================================
CREATE TABLE IF NOT EXISTS medicine (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
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
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_name (medicine_name),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: prescriptions
-- ============================================
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    patient_id INT NOT NULL,
    prescription_date DATE NOT NULL,
    diagnosis TEXT,
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date (prescription_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: prescription_medicines (junction)
-- ============================================
CREATE TABLE IF NOT EXISTS prescription_medicines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    dose VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicine(id) ON DELETE CASCADE,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_prescription (prescription_id),
    INDEX idx_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: treatments
-- ============================================
CREATE TABLE IF NOT EXISTS treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    patient_id INT NOT NULL,
    selected_teeth VARCHAR(255),
    treatment_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    treatment_date DATE NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0,
    status ENUM('planned','completed','cancelled') DEFAULT 'planned',
    next_visit DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date (treatment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: treatment_categories
-- ============================================
CREATE TABLE IF NOT EXISTS treatment_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id),
    UNIQUE KEY unique_category_per_clinic (clinic_id, category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: doses
-- ============================================
CREATE TABLE IF NOT EXISTS doses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    dose_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: durations
-- ============================================
CREATE TABLE IF NOT EXISTS durations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    duration_value VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: expense_categories
-- ============================================
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id),
    UNIQUE KEY unique_expense_cat_per_clinic (clinic_id, category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: expenses
-- ============================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
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
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_category (category),
    INDEX idx_date (expense_date),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: work_types
-- ============================================
CREATE TABLE IF NOT EXISTS work_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    work_name VARCHAR(255) NOT NULL,
    work_code VARCHAR(50),
    cost DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    category VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_active (is_active),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: work_done
-- ============================================
CREATE TABLE IF NOT EXISTS work_done (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    work_name VARCHAR(255) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: patient_work_done
-- ============================================
CREATE TABLE IF NOT EXISTS patient_work_done (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    patient_id INT NOT NULL,
    work_done_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_cost DECIMAL(10,2) DEFAULT 0,
    work_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (work_done_id) REFERENCES work_done(id) ON DELETE CASCADE,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_id),
    INDEX idx_work (work_done_id),
    INDEX idx_date (work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: certificate
-- ============================================
CREATE TABLE IF NOT EXISTS certificate (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    patient_uid VARCHAR(50) NOT NULL,
    certificate_for VARCHAR(255) NOT NULL,
    certificate_details TEXT NOT NULL,
    issue_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: appointments
-- ============================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
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
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: payments
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clinic_id INT NOT NULL,
    patient_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash','card','upi','bank_transfer','cheque') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    notes TEXT,
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    
    INDEX idx_clinic (clinic_id),
    INDEX idx_patient (patient_id),
    INDEX idx_date (payment_date),
    INDEX idx_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 'clinic_management database created successfully!' as status;

-- ============================================
-- CREDENTIALS:
-- Admin: admin@clinic.com / admin123
-- ============================================