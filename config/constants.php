<?php
/**
 * Application Constants
 */

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/clinic_management');

// Upload directories
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');
define('LOGO_PATH', UPLOAD_PATH . '/logos');
define('PATIENT_DOCS_PATH', UPLOAD_PATH . '/patient_docs');

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Pagination
define('RECORDS_PER_PAGE', 20);

// File upload limits
define('MAX_LOGO_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Application info
define('APP_NAME', 'Clinic Management System');
define('APP_VERSION', '2.0.0');
define('APP_YEAR', '2026');

// Default timezone
date_default_timezone_set('Asia/Kolkata');
