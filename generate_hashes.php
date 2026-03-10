<?php
/**
 * Generate correct password hashes
 */

$admin_password = 'admin123';
$clinic_password = 'clinic123';

$admin_hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);
$clinic_hash = password_hash($clinic_password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Admin password hash (for admin123):\n";
echo $admin_hash . "\n\n";

echo "Clinic password hash (for clinic123):\n";
echo $clinic_hash . "\n\n";

// Verify they work
echo "Admin verification: " . (password_verify($admin_password, $admin_hash) ? "✓ PASS" : "✗ FAIL") . "\n";
echo "Clinic verification: " . (password_verify($clinic_password, $clinic_hash) ? "✓ PASS" : "✗ FAIL") . "\n";
?>
