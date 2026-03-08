-- Fix Missing Tables for Existing Clinic Database
-- Run this to add all missing tables

USE clinic____test_1772981321;

-- Create doses table
CREATE TABLE IF NOT EXISTS doses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dose_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default doses
INSERT IGNORE INTO doses (dose_name) VALUES 
('1-0-0'), ('0-1-0'), ('0-0-1'),
('1-1-0'), ('1-0-1'), ('0-1-1'),
('1-1-1'), ('1-1-1-1'),
('As needed'), ('Before meals'), ('After meals');

-- Create durations table
CREATE TABLE IF NOT EXISTS durations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    duration_value VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default durations
INSERT IGNORE INTO durations (duration_value) VALUES 
('1 day'), ('2 days'), ('3 days'), ('5 days'), ('7 days'),
('10 days'), ('14 days'), ('21 days'), ('1 month'), ('Until finished');

-- Create treatment_categories table
CREATE TABLE IF NOT EXISTS treatment_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default treatment categories
INSERT IGNORE INTO treatment_categories (category_name, description) VALUES 
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

-- Create work_types table
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
INSERT IGNORE INTO work_types (work_name, cost, description) VALUES 
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

-- Create expense_categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default expense categories
INSERT IGNORE INTO expense_categories (category_name, description, is_active) VALUES 
('Supplies', 'Medical and dental supplies', 1),
('Equipment', 'Equipment purchase and maintenance', 1),
('Salary', 'Staff salaries and wages', 1),
('Rent', 'Clinic rent', 1),
('Utilities', 'Electricity, water, internet', 1),
('Marketing', 'Advertising and marketing expenses', 1),
('Insurance', 'Insurance premiums', 1),
('Laboratory', 'Laboratory services', 1),
('Maintenance', 'Clinic maintenance and repairs', 1),
('Other', 'Miscellaneous expenses', 1);

-- Create certificate table (without foreign key first, will add later if patients table exists)
CREATE TABLE IF NOT EXISTS certificate (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_uid VARCHAR(50) NOT NULL,
    certificate_for VARCHAR(255) NOT NULL,
    certificate_details TEXT NOT NULL,
    issue_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient (patient_uid),
    INDEX idx_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
