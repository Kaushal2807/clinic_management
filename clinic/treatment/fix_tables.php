<?php
/**
 * Fix Missing Tables - Add missing tables to clinic database
 */

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();
$conn = ClinicContext::getConnection();

try {
    // Fix work_done table structure
    echo "<h3>Fixing work_done table...</h3>";
    
    // Check if work_done has the old structure
    $checkWorkDone = $conn->query("SHOW COLUMNS FROM work_done LIKE 'patient_uid'");
    
    if ($checkWorkDone->num_rows == 0) {
        // Old structure - need to recreate
        echo "Old work_done structure detected. Recreating table...<br>";
        
        // Rename old table
        $conn->query("RENAME TABLE work_done TO work_done_old");
        
        // Create new work_done table with correct structure
        $sql = "CREATE TABLE IF NOT EXISTS work_done (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($sql);
        echo "✓ work_done table recreated with correct structure<br>";
        
        // Drop old table
        $conn->query("DROP TABLE IF EXISTS work_done_old");
        echo "✓ Old work_done table removed<br>";
    } else {
        echo "✓ work_done table already has correct structure<br>";
    }
    
    // Create treatment_categories table
    $sql = "CREATE TABLE IF NOT EXISTS treatment_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ treatment_categories table created<br>";
    
    // Insert default categories
    $categories = [
        ['Preventive', 'Preventive dental care including cleaning, fluoride treatment'],
        ['Restorative', 'Fillings, crowns, bridges'],
        ['Endodontics', 'Root canal treatment'],
        ['Periodontics', 'Gum disease treatment'],
        ['Orthodontics', 'Braces, aligners, retainers'],
        ['Prosthodontics', 'Dentures, implants'],
        ['Oral Surgery', 'Extractions, surgical procedures'],
        ['Cosmetic', 'Whitening, veneers, bonding'],
        ['Pediatric', 'Children dental care'],
        ['Emergency', 'Emergency dental treatment']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO treatment_categories (category_name, description) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->bind_param("ss", $cat[0], $cat[1]);
        $stmt->execute();
    }
    echo "✓ Default categories inserted<br>";
    
    // Create work_types table
    $sql = "CREATE TABLE IF NOT EXISTS work_types (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ work_types table created<br>";
    
    // Insert default work types
    $workTypes = [
        ['Consultation', 500, 'Initial dental consultation'],
        ['Cleaning', 1000, 'Professional teeth cleaning'],
        ['Extraction', 1500, 'Tooth extraction'],
        ['Root Canal', 5000, 'Root canal treatment'],
        ['Filling', 2000, 'Dental filling'],
        ['Crown', 8000, 'Dental crown placement'],
        ['Whitening', 3000, 'Teeth whitening treatment'],
        ['Implant', 25000, 'Dental implant'],
        ['Braces', 50000, 'Orthodontic braces'],
        ['X-Ray', 500, 'Dental X-ray']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO work_types (work_name, cost, description) VALUES (?, ?, ?)");
    foreach ($workTypes as $type) {
        $stmt->bind_param("sds", $type[0], $type[1], $type[2]);
        $stmt->execute();
    }
    echo "✓ Default work types inserted<br>";
    
    // Create expense_categories table
    $sql = "CREATE TABLE IF NOT EXISTS expense_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ expense_categories table created<br>";
    
    // Insert default expense categories
    $expenseCategories = [
        ['Supplies', 'Medical and dental supplies', 1],
        ['Equipment', 'Equipment purchase and maintenance', 1],
        ['Salary', 'Staff salaries and wages', 1],
        ['Rent', 'Clinic rent', 1],
        ['Utilities', 'Electricity, water, internet', 1],
        ['Marketing', 'Advertising and marketing expenses', 1],
        ['Insurance', 'Insurance premiums', 1],
        ['Laboratory', 'Laboratory services', 1],
        ['Maintenance', 'Clinic maintenance and repairs', 1],
        ['Other', 'Miscellaneous expenses', 1]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO expense_categories (category_name, description, is_active) VALUES (?, ?, ?)");
    foreach ($expenseCategories as $cat) {
        $stmt->bind_param("ssi", $cat[0], $cat[1], $cat[2]);
        $stmt->execute();
    }
    echo "✓ Default expense categories inserted<br>";
    
    // Create certificate table
    $sql = "CREATE TABLE IF NOT EXISTS certificate (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_uid VARCHAR(50) NOT NULL,
        certificate_for VARCHAR(255) NOT NULL,
        certificate_details TEXT NOT NULL,
        issue_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_uid) REFERENCES patients(patient_uid) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ certificate table created<br>";
    
    // Create doses table
    $sql = "CREATE TABLE IF NOT EXISTS doses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        dose_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ doses table created<br>";
    
    // Insert default doses
    $doses = ['1-0-0', '0-1-0', '0-0-1', '1-1-0', '1-0-1', '0-1-1', '1-1-1', '1-1-1-1', 'As needed', 'Before meals', 'After meals'];
    $stmt = $conn->prepare("INSERT IGNORE INTO doses (dose_name) VALUES (?)");
    foreach ($doses as $dose) {
        $stmt->bind_param("s", $dose);
        $stmt->execute();
    }
    echo "✓ Default doses inserted<br>";
    
    // Create durations table
    $sql = "CREATE TABLE IF NOT EXISTS durations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        duration_value VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql);
    echo "✓ durations table created<br>";
    
    // Insert default durations
    $durations = ['1 day', '2 days', '3 days', '5 days', '7 days', '10 days', '14 days', '21 days', '1 month', 'Until finished'];
    $stmt = $conn->prepare("INSERT IGNORE INTO durations (duration_value) VALUES (?)");
    foreach ($durations as $duration) {
        $stmt->bind_param("s", $duration);
        $stmt->execute();
    }
    echo "✓ Default durations inserted<br>";
    
    echo "<br><strong>All missing tables created successfully!</strong><br>";
    echo "<a href='index.php'>Go back to Treatment Management</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
