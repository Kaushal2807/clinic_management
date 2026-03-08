<?php
/**
 * Admin - Clinics Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get flash messages from session
$message = Session::get('flash_message') ?? '';
$messageType = Session::get('flash_type') ?? '';

// Clear flash messages after reading
if ($message) {
    Session::set('flash_message', null);
    Session::set('flash_type', null);
}

// Handle clinic creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_clinic'])) {
    $clinicName = trim($_POST['clinic_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Check if clinic with same name already exists
    $checkStmt = $db->prepare("SELECT id FROM clinics WHERE clinic_name = ?");
    $checkStmt->bind_param('s', $clinicName);
    $checkStmt->execute();
    $existingClinic = $checkStmt->get_result();
    
    if ($existingClinic->num_rows > 0) {
        Session::set('flash_message', "Error: A clinic with the name '$clinicName' already exists!");
        Session::set('flash_type', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        // Generate database name
        $dbName = 'clinic_' . strtolower(preg_replace('/[^a-z0-9]/', '_', $clinicName)) . '_' . time();
        
        // Handle logo upload
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['logo']['type'], $allowedTypes) && $_FILES['logo']['size'] <= $maxSize) {
                $uploadDir = __DIR__ . '/../assets/uploads/logos/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . uniqid() . '.' . $ext;
                $logoPath = 'assets/uploads/logos/' . $filename;
                
                move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../' . $logoPath);
            }
        }
        
        try {
            // Create clinic database
            $db->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Insert clinic record
            $stmt = $db->prepare(
                "INSERT INTO clinics (clinic_name, database_name, contact_email, contact_phone, address, logo_path, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
            );
            $stmt->bind_param('ssssss', $clinicName, $dbName, $email, $phone, $address, $logoPath);
            $stmt->execute();
            
            $clinicId = $db->insert_id;
            
            // Create clinic schema
            createClinicSchema($dbName);
            
            // Set success message and redirect
            Session::set('flash_message', "Clinic created successfully! You can now add users for this clinic.");
            Session::set('flash_type', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            Session::set('flash_message', "Error creating clinic: " . $e->getMessage());
            Session::set('flash_type', 'error');
            error_log("Clinic creation error: " . $e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Handle clinic deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $clinicId = (int)$_GET['delete'];
    
    try {
        // Get clinic database name
        $stmt = $db->prepare("SELECT database_name FROM clinics WHERE id = ?");
        $stmt->bind_param('i', $clinicId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($clinic = $result->fetch_assoc()) {
            // Drop database
            $dbName = $clinic['database_name'];
            $db->query("DROP DATABASE IF EXISTS `$dbName`");
            
            // Delete clinic record (cascade will handle users)
            $stmt = $db->prepare("DELETE FROM clinics WHERE id = ?");
            $stmt->bind_param('i', $clinicId);
            $stmt->execute();
            
            Session::set('flash_message', "Clinic deleted successfully");
            Session::set('flash_type', 'success');
        }
    } catch (Exception $e) {
        Session::set('flash_message', "Error deleting clinic: " . $e->getMessage());
        Session::set('flash_type', 'error');
    }
    
    header('Location: clinics.php');
    exit;
}

// Handle clinic toggle (activate/deactivate)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $clinicId = (int)$_GET['toggle'];
    
    try {
        $db->query("UPDATE clinics SET is_active = NOT is_active WHERE id = $clinicId");
        Session::set('flash_message', "Clinic status updated successfully");
        Session::set('flash_type', 'success');
    } catch (Exception $e) {
        Session::set('flash_message', "Error updating clinic: " . $e->getMessage());
        Session::set('flash_type', 'error');
    }
    
    header('Location: clinics.php');
    exit;
}

// Fetch all clinics
$clinics = $db->query("SELECT * FROM clinics ORDER BY created_at DESC");

/**
 * Create clinic database schema
 */
function createClinicSchema($dbName) {
    $db = Database::getInstance()->getConnection();
    $db->select_db($dbName);
    
    $schema = "
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
        chief_complain TEXT,
        medical_history TEXT,
        oral_diet_habit TEXT,
        family_history TEXT,
        xray_remark TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_patient_uid (patient_uid),
        INDEX idx_date (date_of_visit),
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS medicine (
        id INT PRIMARY KEY AUTO_INCREMENT,
        medicine_name VARCHAR(255) NOT NULL,
        composition TEXT,
        category VARCHAR(100),
        quantity INT DEFAULT 0,
        unit VARCHAR(50),
        price DECIMAL(10,2),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (medicine_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS prescriptions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT NOT NULL,
        prescription_date DATE NOT NULL,
        diagnosis TEXT,
        notes TEXT,
        created_by VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        INDEX idx_patient (patient_id),
        INDEX idx_date (prescription_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS prescription_medicines (
        id INT PRIMARY KEY AUTO_INCREMENT,
        prescription_id INT NOT NULL,
        medicine_id INT NOT NULL,
        dose VARCHAR(100),
        duration VARCHAR(100),
        instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
        FOREIGN KEY (medicine_id) REFERENCES medicine(id) ON DELETE CASCADE,
        INDEX idx_prescription (prescription_id),
        INDEX idx_medicine (medicine_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS treatments (
        id INT PRIMARY KEY AUTO_INCREMENT,
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
        INDEX idx_patient (patient_id),
        INDEX idx_date (treatment_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS expenses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        expense_date DATE NOT NULL,
        description TEXT,
        created_by VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_date (expense_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS work_done (
        id INT PRIMARY KEY AUTO_INCREMENT,
        work_name VARCHAR(255) NOT NULL,
        cost DECIMAL(10,2) DEFAULT 0,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS patient_work_done (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_id INT NOT NULL,
        work_done_id INT NOT NULL,
        quantity INT DEFAULT 1,
        total_cost DECIMAL(10,2) DEFAULT 0,
        work_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (work_done_id) REFERENCES work_done(id) ON DELETE CASCADE,
        INDEX idx_patient (patient_id),
        INDEX idx_work (work_done_id),
        INDEX idx_date (work_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS treatment_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS expense_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS certificate (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patient_uid VARCHAR(50) NOT NULL,
        certificate_for VARCHAR(255) NOT NULL,
        certificate_details TEXT NOT NULL,
        issue_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient (patient_uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS doses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        dose_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS durations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        duration_value VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
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
    
    INSERT INTO work_done (work_name, cost, description, is_active) VALUES 
    ('Consultation', 500, 'Initial dental consultation', 1),
    ('Cleaning', 1000, 'Professional teeth cleaning', 1),
    ('Extraction', 1500, 'Tooth extraction', 1),
    ('Root Canal', 5000, 'Root canal treatment', 1),
    ('Filling', 2000, 'Dental filling', 1),
    ('Crown', 8000, 'Dental crown placement', 1),
    ('Whitening', 3000, 'Teeth whitening treatment', 1),
    ('Implant', 25000, 'Dental implant', 1),
    ('Braces', 50000, 'Orthodontic braces', 1),
    ('X-Ray', 500, 'Dental X-ray', 1);
    
    INSERT INTO expense_categories (category_name, description, is_active) VALUES 
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
    
    INSERT INTO doses (dose_name) VALUES 
    ('1-0-0'), ('0-1-0'), ('0-0-1'),
    ('1-1-0'), ('1-0-1'), ('0-1-1'),
    ('1-1-1'), ('1-1-1-1'),
    ('As needed'), ('Before meals'), ('After meals');
    
    INSERT INTO durations (duration_value) VALUES 
    ('1 day'), ('2 days'), ('3 days'), ('5 days'), ('7 days'),
    ('10 days'), ('14 days'), ('21 days'), ('1 month'), ('Until finished');
    ";
    
    // Execute schema
    $db->multi_query($schema);
    
    // Wait for all queries to complete
    while ($db->next_result()) {;}
    
    // Switch back to master database
    Database::getInstance()->switchToMaster();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clinics - Admin Panel</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="../assets/favicon/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">Admin Panel</h1>
                    <nav class="ml-10 flex space-x-4">
                        <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Dashboard</a>
                        <a href="clinics.php" class="px-3 py-2 rounded-md text-sm font-semibold text-indigo-600 bg-indigo-50">Clinics</a>
                        <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Users</a>
                        <a href="activity.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition">Activity</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-700">
                        <span class="font-semibold"><?= htmlspecialchars(Session::getUserName()) ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>/public/logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg border-l-4 <?= $messageType === 'success' ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500' ?>">
            <p class="<?= $messageType === 'success' ? 'text-green-700' : 'text-red-700' ?> font-medium">
                <?= htmlspecialchars($message) ?>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Clinic Management</h2>
                <p class="text-gray-600 mt-1">Manage all registered clinics and their databases</p>
            </div>
            <button onclick="document.getElementById('addClinicModal').classList.remove('hidden')"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-md transition hover:shadow-lg">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add New Clinic
                </span>
            </button>
        </div>

        <!-- Clinics Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Logo</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Clinic Details</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Contact Info</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Database</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($clinics->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p class="text-lg font-semibold text-gray-900 mb-1">No clinics yet</p>
                                <p class="text-sm text-gray-600">Get started by adding your first clinic</p>                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while ($clinic = $clinics->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <?php if ($clinic['logo_path']): ?>
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($clinic['logo_path']) ?>" 
                                         class="w-14 h-14 rounded-xl object-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <span class="text-indigo-600 font-bold text-xl">
                                            <?= strtoupper(substr($clinic['clinic_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($clinic['clinic_name']) ?></div>
                                <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($clinic['address']) ?></div>
                                <div class="text-xs text-gray-400 mt-1">Created: <?= date('M d, Y', strtotime($clinic['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center text-sm text-gray-900 mb-1">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <?= htmlspecialchars($clinic['contact_email']) ?>
                                </div>
                                <?php if ($clinic['contact_phone']): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <?= htmlspecialchars($clinic['contact_phone']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-sm bg-gray-100 px-3 py-1.5 rounded-lg font-mono text-gray-700">
                                    <?= $clinic['database_name'] ?>
                                </code>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($clinic['is_active']): ?>
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="?toggle=<?= $clinic['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm px-3 py-1 hover:bg-blue-50 rounded transition"
                                       onclick="return confirm('Toggle clinic status?')">
                                        <?= $clinic['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </a>
                                    <a href="?delete=<?= $clinic['id'] ?>" 
                                       class="text-red-600 hover:text-red-800 font-medium text-sm px-3 py-1 hover:bg-red-50 rounded transition"
                                       onclick="return confirm('Are you sure? This will delete the clinic and all its data!')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Clinic Modal -->
    <div id="addClinicModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Add New Clinic</h2>
                <button onclick="document.getElementById('addClinicModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="createClinicForm" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="add_clinic" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Clinic Name *</label>
                        <input type="text" name="clinic_name" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Enter clinic name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Email *</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="email@example.com">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                        <input type="text" name="phone" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="+1 234 567 8900">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Clinic Logo</label>
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/jpg"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm">
                        <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG only</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                              placeholder="Enter full address"></textarea>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-900 font-medium">⚠️ Important Note</p>
                    <p class="text-sm text-yellow-700 mt-1">
                        After creating the clinic, you must manually add users through the User Management section.
                    </p>
                </div>
                
                <div class="flex gap-4 pt-4">
                    <button type="submit" id="createClinicBtn"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btnText">Create Clinic & Database</span>
                        <span id="btnLoading" class="hidden">
                            <svg class="animate-spin inline-block h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating...
                        </span>
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('addClinicModal').classList.add('hidden')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prevent double submission
        const clinicForm = document.getElementById('createClinicForm');
        const submitBtn = document.getElementById('createClinicBtn');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        
        clinicForm.addEventListener('submit', function(e) {
            // Disable button to prevent double submission
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
        });
    </script>

</body>
</html>
