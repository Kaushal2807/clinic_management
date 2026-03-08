<?php
/**
 * MIGRATION SCRIPT
 * Migrate data from old database structure to new multi-clinic system
 * 
 * BEFORE RUNNING:
 * 1. Backup your current database
 * 2. Import database_schema.sql
 * 3. Configure settings below
 * 4. Run this script ONCE from browser
 */

// Configuration
define('OLD_DB_NAME', 'clinic_management_old'); // Your old database name
define('NEW_CLINIC_NAME', 'My Dental Clinic');
define('NEW_CLINIC_EMAIL', 'clinic@example.com');
define('NEW_CLINIC_PHONE', '+1-234-567-8900');
define('NEW_CLINIC_ADDRESS', 'Your clinic address here');

// Prevent multiple runs
if (file_exists(__DIR__ . '/.migrated')) {
    die('Migration already completed! Delete .migrated file to run again (NOT RECOMMENDED).');
}

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$errors = [];
$success = [];

echo "<h2>Clinic Management System - Data Migration</h2>";
echo "<pre>";

try {
    // Step 1: Check if old database exists
    echo "Step 1: Checking old database...\n";
    $result = $db->query("SHOW DATABASES LIKE '" . OLD_DB_NAME . "'");
    
    if ($result->num_rows === 0) {
        throw new Exception("Old database '" . OLD_DB_NAME . "' not found!");
    }
    echo "✓ Old database found\n\n";
    
    // Step 2: Create new clinic
    echo "Step 2: Creating new clinic...\n";
    
    $clinicName = NEW_CLINIC_NAME;
    $email = NEW_CLINIC_EMAIL;
    $phone = NEW_CLINIC_PHONE;
    $address = NEW_CLINIC_ADDRESS;
    $dbName = 'clinic_' . strtolower(preg_replace('/[^a-z0-9]/', '_', $clinicName)) . '_migrated';
    
    // Create clinic database
    $db->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Insert clinic record
    $stmt = $db->prepare(
        "INSERT INTO clinic_management_master.clinics (clinic_name, database_name, contact_email, contact_phone, address, is_active)
         VALUES (?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param('sssss', $clinicName, $dbName, $email, $phone, $address);
    $stmt->execute();
    
    $clinicId = $db->insert_id;
    echo "✓ Clinic created with ID: $clinicId\n";
    echo "✓ Database: $dbName\n\n";
    
    // Step 3: Create default user
    echo "Step 3: Creating clinic user...\n";
    
    $defaultPassword = Auth::hashPassword('clinic123');
    $stmt = $db->prepare(
        "INSERT INTO clinic_management_master.users (clinic_id, full_name, email, password, user_type)
         VALUES (?, ?, ?, ?, 'clinic')"
    );
    $adminName = $clinicName . " Admin";
    $stmt->bind_param('isss', $clinicId, $adminName, $email, $defaultPassword);
    $stmt->execute();
    
    echo "✓ User created: $email / clinic123\n\n";
    
    // Step 4: Create clinic schema
    echo "Step 4: Creating clinic database schema...\n";
    
    $db->select_db($dbName);
    
    $schema = file_get_contents(__DIR__ . '/database_schema.sql');
    
    // Extract only clinic schema part (between USE clinic_demo_dental and delimiter)
    preg_match('/USE clinic_demo_dental;(.+?)-- ============================================/s', $schema, $matches);
    
    if (!empty($matches[1])) {
        $db->multi_query($matches[1]);
        while ($db->next_result()) {;}
    }
    
    echo "✓ Schema created\n\n";
    
    // Step 5: Migrate data
    echo "Step 5: Migrating data from old database...\n";
    
    // Migrate patients
    echo "  - Migrating patients...\n";
    $result = $db->query("
        INSERT INTO `$dbName`.patients 
        SELECT * FROM `" . OLD_DB_NAME . "`.patients 
        WHERE 1
    ");
    $patientCount = $db->affected_rows;
    echo "    ✓ Migrated $patientCount patients\n";
    
    // Migrate prescriptions (if table exists)
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'prescriptions'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating prescriptions...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.prescriptions 
            SELECT * FROM `" . OLD_DB_NAME . "`.prescriptions 
            WHERE 1
        ");
        $presCount = $db->affected_rows;
        echo "    ✓ Migrated $presCount prescriptions\n";
    }
    
    // Migrate treatments
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'treatments'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating treatments...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.treatments 
            SELECT * FROM `" . OLD_DB_NAME . "`.treatments 
            WHERE 1
        ");
        $treatCount = $db->affected_rows;
        echo "    ✓ Migrated $treatCount treatments\n";
    }
    
    // Migrate medicine
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'medicine'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating medicine...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.medicine 
            SELECT * FROM `" . OLD_DB_NAME . "`.medicine 
            WHERE 1
        ");
        $medCount = $db->affected_rows;
        echo "    ✓ Migrated $medCount medicine records\n";
    }
    
    // Migrate expenses
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'expenses'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating expenses...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.expenses 
            SELECT * FROM `" . OLD_DB_NAME . "`.expenses 
            WHERE 1
        ");
        $expCount = $db->affected_rows;
        echo "    ✓ Migrated $expCount expenses\n";
    }
    
    // Migrate work_done
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'work_done'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating work done...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.work_done 
            SELECT * FROM `" . OLD_DB_NAME . "`.work_done 
            WHERE 1
        ");
        $workCount = $db->affected_rows;
        echo "    ✓ Migrated $workCount work done records\n";
    }
    
    // Migrate patient_work_done
    $tableCheck = $db->query("SHOW TABLES FROM `" . OLD_DB_NAME . "` LIKE 'patient_work_done'");
    if ($tableCheck->num_rows > 0) {
        echo "  - Migrating patient work done...\n";
        $result = $db->query("
            INSERT INTO `$dbName`.patient_work_done 
            SELECT * FROM `" . OLD_DB_NAME . "`.patient_work_done 
            WHERE 1
        ");
        $pwdCount = $db->affected_rows;
        echo "    ✓ Migrated $pwdCount patient work done records\n";
    }
    
    echo "\n";
    
    // Step 6: Mark migration complete
    echo "Step 6: Finalizing migration...\n";
    file_put_contents(__DIR__ . '/.migrated', date('Y-m-d H:i:s'));
    echo "✓ Migration marker created\n\n";
    
    // Summary
    echo "========================================\n";
    echo "MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    
    echo "Clinic Details:\n";
    echo "  - Name: $clinicName\n";
    echo "  - Database: $dbName\n";
    echo "  - Email: $email\n";
    echo "  - Password: clinic123\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Login at: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/public/login.php\n";
    echo "  2. Change default password\n";
    echo "  3. Upload clinic logo\n";
    echo "  4. Verify all data migrated correctly\n";
    echo "  5. Delete or rename this migration file\n\n";
    
    echo "Admin Login:\n";
    echo "  - Email: admin@clinic.com\n";
    echo "  - Password: admin123\n\n";
    
} catch (Exception $e) {
    echo "\n\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. Please check the error and try again.\n";
    
    // Rollback
    if (isset($dbName)) {
        echo "\nAttempting rollback...\n";
        $db->query("DROP DATABASE IF EXISTS `$dbName`");
        $db->query("DELETE FROM clinic_management_master.clinics WHERE id = $clinicId");
        $db->query("DELETE FROM clinic_management_master.users WHERE clinic_id = $clinicId");
        echo "✓ Rollback completed\n";
    }
}

echo "</pre>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Migration Complete</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }
        h2 {
            color: #333;
        }
    </style>
</head>
<body>
</body>
</html>
