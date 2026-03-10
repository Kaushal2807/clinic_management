<?php
/**
 * Clinic Header - Reusable header for all clinic pages
 */
$clinic = ClinicContext::getClinicInfo();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= htmlspecialchars($clinic['clinic_name']) ?></title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon/favicon.svg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .modal { display: none; }
        .modal.active { display: flex; }
        #main-content {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md border-b-2 border-indigo-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Logo & Clinic Name -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <?php if ($clinic['logo_path']): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($clinic['logo_path']) ?>" 
                             alt="<?= htmlspecialchars($clinic['clinic_name']) ?>"
                             class="h-12 w-12 rounded-xl object-cover shadow-lg ring-2 ring-indigo-100">
                    <?php else: ?>
                        <div class="h-12 w-12 gradient-bg rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-xl">
                                <?= strtoupper(substr($clinic['clinic_name'], 0, 1)) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="whitespace-nowrap">
                        <h1 class="text-lg font-bold text-indigo-600 leading-tight">
                            <?= htmlspecialchars($clinic['clinic_name']) ?>
                        </h1>
                        <p class="text-xs text-gray-600 font-medium">Management System</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="hidden lg:flex space-x-1">
                    <a href="<?= BASE_URL ?>/clinic/dashboard.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= $currentPage === 'dashboard.php' ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/patients/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/patients/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-user-injured mr-2"></i>Patients
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/prescription/list.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/prescription/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-prescription mr-2"></i>Prescription
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/treatment/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/treatment/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-tooth mr-2"></i>Treatment
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/work_done/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/work_done/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-briefcase-medical mr-2"></i>Work Done
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/drugs/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/drugs/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-pills mr-2"></i>Drugs
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/expense/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/expense/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-receipt mr-2"></i>Expense
                    </a>
                    <a href="<?= BASE_URL ?>/clinic/reports/index.php" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              <?= strpos($currentPath, '/reports/') !== false ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <i class="fas fa-chart-line mr-2"></i>Reports
                    </a>
                </nav>

                <!-- User Menu -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars(Session::getUserName()) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars(Session::getEmail()) ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/public/logout.php" 
                       class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <div id="main-content">
