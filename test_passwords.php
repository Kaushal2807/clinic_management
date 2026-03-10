<?php
/**
 * Test password verification
 * Run this to verify the default passwords work correctly
 */

// Admin password hash from database_schema.sql
$adminHash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5jtI3zUIheDpm';
$adminPassword = 'admin123';

// Clinic password hash from database_schema.sql  
$clinicHash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$clinicPassword = 'clinic123';

echo "<h2>Password Verification Test</h2>";

echo "<h3>Admin Credentials:</h3>";
echo "Email: admin@clinic.com<br>";
echo "Password: admin123<br>";
echo "Hash verification: " . (password_verify($adminPassword, $adminHash) ? '<span style="color:green">✓ PASS</span>' : '<span style="color:red">✗ FAIL</span>') . "<br><br>";

echo "<h3>Clinic Credentials:</h3>";
echo "Email: demo@clinic.com<br>";
echo "Password: clinic123<br>";
echo "Hash verification: " . (password_verify($clinicPassword, $clinicHash) ? '<span style="color:green">✓ PASS</span>' : '<span style="color:red">✗ FAIL</span>') . "<br><br>";

// Test database connection and users
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h3>Database Users Check:</h3>";
    
    $result = $db->query("SELECT id, full_name, email, user_type, is_active FROM users ORDER BY id", [], '');
    $users = $result->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($users)) {
        echo '<span style="color:red">✗ No users found in database!</span><br>';
        echo '<p>Please run setup.php first.</p>';
    } else {
        echo '<table border="1" cellpadding="5" style="border-collapse:collapse">';
        echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Active</th></tr>';
        foreach ($users as $user) {
            $activeColor = $user['is_active'] ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td><strong>{$user['email']}</strong></td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td style='color:{$activeColor}'>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo '</table>';
    }
    
} catch (Exception $e) {
    echo '<span style="color:red">Database Error: ' . $e->getMessage() . '</span>';
}

echo "<hr>";
echo "<h3>Login Instructions:</h3>";
echo "<ul>";
echo "<li>For <strong>Admin</strong> access: Use email <code>admin@clinic.com</code> and password <code>admin123</code></li>";
echo "<li>For <strong>Clinic</strong> access: Use email <code>demo@clinic.com</code> and password <code>clinic123</code></li>";
echo "</ul>";
echo "<a href='public/login.php'>Go to Login Page</a>";
?>
