<?php
// Generate correct password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "New password hash for 'admin123':\n";
echo $hash . "\n\n";

// Update database
$conn = new mysqli('localhost', 'root', '', 'clinic_management_master');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@clinic.com'");
$stmt->bind_param('s', $hash);

if ($stmt->execute()) {
    echo "✓ Password updated successfully!\n";
    echo "You can now login with:\n";
    echo "Email: admin@clinic.com\n";
    echo "Password: admin123\n";
} else {
    echo "✗ Error updating password: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
