<?php
/**
 * Browser-Based Database Setup
 * For users with phpMyAdmin
 * 
 * HOW TO USE:
 * 1. Open in browser: http://localhost/clinic_management/setup.php
 * 2. Click "Install Database"
 * 3. Follow on-screen instructions
 */

// Prevent running twice
if (file_exists(__DIR__ . '/.installed')) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Already Installed</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .success { color: #10b981; font-size: 48px; }
            h2 { color: #333; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
            .btn:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="success">✓</div>
            <h2>System Already Installed!</h2>
            <p>The database has already been set up. Delete the <code>.installed</code> file if you need to reinstall.</p>
            <a href="public/login.php" class="btn">Go to Login Page</a>
        </div>
    </body>
    </html>
    ');
}

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';

// Step 2: Execute installation
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to MySQL
        $conn = new mysqli($host, $user, $pass);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Read SQL file
        $sqlFile = __DIR__ . '/database_schema.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database_schema.sql file not found!");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        $failed = 0;
        
        foreach ($queries as $query) {
            if (empty($query)) continue;
            
            // Skip DELIMITER statements
            if (stripos($query, 'DELIMITER') !== false) continue;
            
            try {
                if ($conn->multi_query($query)) {
                    do {
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                    } while ($conn->next_result());
                    $executed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                // Continue on error
                $failed++;
            }
        }
        
        $conn->close();
        
        // Mark as installed
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        // Redirect to success page
        header('Location: setup.php?step=3&executed=' . $executed);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $step = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Management System - Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
        .step-content h3 {
            color: #333;
            margin-bottom: 5px;
        }
        .step-content p {
            color: #666;
            font-size: 14px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .credentials {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials h4 {
            color: #1e40af;
            margin-bottom: 15px;
        }
        .cred-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .cred-label {
            font-weight: 600;
            color: #374151;
        }
        .cred-value {
            font-family: 'Courier New', monospace;
            color: #3b82f6;
        }
        .success-icon {
            font-size: 80px;
            text-align: center;
            color: #10b981;
            margin-bottom: 20px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #ef4444;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Clinic Management System</h1>
            <p>Browser-Based Installation for phpMyAdmin Users</p>
        </div>
        
        <div class="content">
            <?php if ($step === 1): ?>
                <!-- Step 1: Pre-installation Check -->
                <h2 style="color: #333; margin-bottom: 30px;">Installation Setup</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Database Configuration</h3>
                        <p>Host: <code>localhost</code> | User: <code>root</code> | Password: <code>(empty)</code></p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>What Will Be Created</h3>
                        <p>Master database + Demo clinic with sample data</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Default Accounts</h3>
                        <p>Admin and demo clinic accounts will be created</p>
                    </div>
                </div>
                
                <form method="POST" action="setup.php?step=2">
                    <button type="submit" class="btn">
                        🚀 Install Database Now
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                    This will create all necessary databases and tables.<br>
                    Make sure MySQL is running in XAMPP/LAMPP.
                </p>
                
            <?php elseif ($step === 3): ?>
                <!-- Step 3: Success -->
                <div class="success-icon">✓</div>
                <h2 style="color: #333; margin-bottom: 20px; text-align: center;">Installation Successful!</h2>
                
                <div class="alert alert-success">
                    <strong>✓ Database Created:</strong> clinic_management_master<br>
                    <strong>✓ Demo Clinic Created:</strong> clinic_demo_dental<br>
                    <strong>✓ Queries Executed:</strong> <?= isset($_GET['executed']) ? (int)$_GET['executed'] : 'N/A' ?>
                </div>
                
                <div class="credentials">
                    <h4>📋 Login Credentials</h4>
                    
                    <div class="cred-item">
                        <span class="cred-label">Admin Email:</span>
                        <span class="cred-value">admin@clinic.com</span>
                    </div>
                    <div class="cred-item">
                        <span class="cred-label">Admin Password:</span>
                        <span class="cred-value">admin123</span>
                    </div>
                    
                    <div style="height: 15px;"></div>
                    
                    <div class="cred-item">
                        <span class="cred-label">Demo Clinic Email:</span>
                        <span class="cred-value">demo@clinic.com</span>
                    </div>
                    <div class="cred-item">
                        <span class="cred-label">Demo Clinic Password:</span>
                        <span class="cred-value">clinic123</span>
                    </div>
                </div>
                
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 10px; margin: 20px 0;">
                    <strong style="color: #92400e;">⚠️ Important:</strong>
                    <p style="color: #92400e; margin-top: 5px;">Please change these default passwords after your first login!</p>
                </div>
                
                <a href="public/login.php" class="btn" style="text-decoration: none; display: block; text-align: center;">
                    🔐 Go to Login Page
                </a>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h4 style="color: #333; margin-bottom: 15px;">📝 Next Steps:</h4>
                    <ol style="color: #666; line-height: 2;">
                        <li>Login with admin credentials</li>
                        <li>Change default passwords</li>
                        <li>Create your own clinic</li>
                        <li>Upload clinic logo</li>
                        <li>Start managing patients</li>
                    </ol>
                </div>
                
                <p style="text-align: center; margin-top: 30px; color: #999; font-size: 13px;">
                    You can delete <code>setup.php</code> file after installation for security.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            Clinic Management System v2.0.0 | Secure & Professional | © 2026
        </div>
    </div>
</body>
</html>
