# 🏗️ REFACTORED CODE STRUCTURE - MIGRATION GUIDE

## ✅ Completed Modules

### 1. **Core Infrastructure** ✓
- `config/ClinicContext.php` - Handles clinic database switching
- `includes/clinic_header.php` - Reusable header with logo & navigation
- `includes/clinic_footer.php` - Reusable footer

### 2. **Drug/Medicine Module** ✓ 
```
clinic/drugs/
├── index.php              ✓ Main listing with CRUD
├── doses.php              → Migrate from old add_dose.php
├── durations.php          → Migrate from old add_duration.php
└── suggest_drugs.php      → Migrate from old add_suggest_drug.php

api/drugs/
├── add.php                ✓ Create medicine
├── update.php             ✓ Update medicine  
├── delete.php             ✓ Delete medicine
└── add_quantity.php       ✓ Add stock quantity
```

### 3. **Patient Module** ✓
```
clinic/patients/
├── index.php              ✓ Main listing with stats
├── view.php               → Migrate from old view_treatment_plan.php
├── update.php             → Migrate from old update_patient.php
└── print_case.php         → Migrate from old print_case_detail.php

api/patients/
├── add.php                ✓ Create patient
├── update.php             → Migrate from old update_patient.php API
├── delete.php             → New endpoint
└── get.php                → Migrate from old get_patient.php
```

---

## 📝 MODULES TO REFACTOR (Following Same Pattern)

### 4. **Prescription Module**
```
OLD FILES TO MIGRATE:
- add_prescription.php
- doctor_add_prescription.php
- save_prescription.php
- delete_prescription.php
- view_prescription.php
- print_prescription.php

NEW STRUCTURE:
clinic/prescription/
├── index.php              → List all prescriptions
├── add.php                → Add new prescription (combine add_prescription + doctor_add_prescription)
├── view.php               → View prescription details
└── print.php              → Print prescription PDF

api/prescription/
├── add.php                → Save prescription (from save_prescription.php)
├── update.php             → Update prescription
├── delete.php             → Delete prescription (from delete_prescription.php)
└── list.php               → Get prescriptions for patient
```

### 5. **Treatment Module**
```
OLD FILES TO MIGRATE:
- add_treatment.php
- save_patient_treatment.php
- delete_patient_treatment.php
- view_treatment_plan.php

NEW STRUCTURE:
clinic/treatment/
├── index.php              → List all treatments
├── add.php                → Add treatment plan (with tooth selection)
├── view.php               → View treatment details
└── history.php            → Treatment history

api/treatment/
├── add.php                → Save treatment (from save_patient_treatment.php)
├── update.php             → Update treatment
├── delete.php             → Delete treatment (from delete_patient_treatment.php)
└── list.php               → Get treatments for patient
```

### 6. **Work Done Module**
```
OLD FILES TO MIGRATE:
- add_work_done.php
- add_patient_work_done.php
- update_work_done.php
- delete_work_done.php
- view_work_done.php
- get_work_done.php
- fetch_work_done_single.php

NEW STRUCTURE:
clinic/work_done/
├── index.php              → List all work done
├── add.php                → Add work done
├── view.php               → View work details
└── manage.php             → Manage work types

api/work_done/
├── add.php                → Save work done
├── update.php             → Update work (from update_work_done.php)
├── delete.php             → Delete work (from delete_work_done.php)
├── list.php               → Get work for patient (from get_work_done.php)
└── get_single.php         → Get single work (from fetch_work_done_single.php)
```

### 7. **Expense Module**
```
OLD FILES TO MIGRATE:
- expense.php
- add_expense.php
- add_expense_category.php
- update_expense.php
- delete_expense.php
- get_expense.php
- fetch_expenses.php
- manage_category.php
- manage_expense.php
- update_category.php
- delete_category.php
- expense_category_monthly.php
- expense_last_6_months.php

NEW STRUCTURE:
clinic/expense/
├── index.php              → List expenses (from expense.php + manage_expense.php)
├── add.php                → Add expense (from add_expense.php)
├── categories.php         → Manage categories (from manage_category.php)
└── reports.php            → Expense reports (monthly + 6 months)

api/expense/
├── add.php                → Save expense (from add_expense.php)
├── update.php             → Update expense (from update_expense.php)
├── delete.php             → Delete expense (from delete_expense.php)
├── list.php               → Get expenses (from fetch_expenses.php)
├── get.php                → Get single expense (from get_expense.php)
├── category_add.php       → Add category (from add_expense_category.php)
├── category_update.php    → Update category (from update_category.php)
├── category_delete.php    → Delete category (from delete_category.php)
└── monthly.php            → Monthly data (from expense_category_monthly.php)
```

### 8. **Reports Module**
```
OLD FILES TO MIGRATE:
- report.php
- fetch_patient_trend.php
- fetch_payment_search.php
- fetch_report_kpi.php
- payment_status_monthly.php

NEW STRUCTURE:
clinic/reports/
├── index.php              → Main dashboard (from report.php)
├── patient_trend.php      → Patient trends
├── payment.php            → Payment reports
├── kpi.php                → KPI dashboard
└── monthly.php            → Monthly summary

api/reports/
├── patient_trend.php      → Patient trend data (from fetch_patient_trend.php)
├── payment_search.php     → Payment search (from fetch_payment_search.php)
├── kpi.php                → KPI data (from fetch_report_kpi.php)
└── monthly.php            → Monthly data (from payment_status_monthly.php)
```

### 9. **Certificate Module**
```
OLD FILES TO MIGRATE:
- save_certificate.php
- generate_certificate_pdf.php
- prepare_print.php

NEW STRUCTURE:
clinic/certificate/
└── generate.php           → Generate & print certificate

api/certificate/
├── save.php               → Save certificate (from save_certificate.php)
└── generate_pdf.php       → Generate PDF (from generate_certificate_pdf.php)
```

---

## 🎨 CODE PATTERN FOR ALL MODULES

### 🟦 Frontend Page Template (clinic/MODULE/index.php)
```php
<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';
require_once __DIR__ . '/../../config/constants.php';

Auth::requireClinic();
ClinicContext::init();

$pageTitle = 'MODULE NAME';
$clinic = ClinicContext::getClinicInfo();
$conn = ClinicContext::getConnection();

// Your logic here (fetch data, pagination, etc.)

include __DIR__ . '/../../includes/clinic_header.php';
?>

    <!-- Your HTML Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page content -->
    </div>

    <!-- Modals if needed -->

    <!-- Scripts -->
    <script>
        // Your JavaScript
    </script>

<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
```

### 🟩 API Endpoint Template (api/MODULE/action.php)
```php
<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/ClinicContext.php';

Auth::requireClinic();
ClinicContext::init();

header('Content-Type: application/json');

try {
    $conn = ClinicContext::getConnection();
    
    // Validate input
    // Process request
    // Execute database operations
    
    echo json_encode([
        'success' => true,
        'message' => 'Operation successful',
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
```

---

## 🔧 MIGRATION STEPS FOR EACH OLD FILE

### Step 1: Identify Module
Determine which module the old file belongs to:
- Medicine/Drug operations → `clinic/drugs/`
- Patient operations → `clinic/patients/`
- Prescription → `clinic/prescription/`
- Treatment → `clinic/treatment/`
- Work done → `clinic/work_done/`
- Expense → `clinic/expense/`
- Reports → `clinic/reports/`

### Step 2: Identify File Type
- **Page file** (with HTML) → Goes to `clinic/MODULE/`
- **API/Action file** (JSON response or processing) → Goes to `api/MODULE/`
- **Shared function** → Goes to `includes/` or stays in `config/`

### Step 3: Update Database Calls
**OLD:**
```php
include 'db_con.php'; // Old connection
$sql = "SELECT * FROM patients";
$result = $conn->query($sql);
```

**NEW:**
```php
require_once __DIR__ . '/../../config/ClinicContext.php';
ClinicContext::init(); // Switches to clinic database
$conn = ClinicContext::getConnection();
$sql = "SELECT * FROM patients";
$result = $conn->query($sql);
```

### Step 4: Update Auth Checks
**OLD:**
```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
}
```

**NEW:**
```php
require_once __DIR__ . '/../../core/Auth.php';
Auth::requireClinic(); // Auto checks & redirects
```

### Step 5: Update Header/Footer
**OLD:**
```php
<html>
<head>...</head>
<body>
<!-- Hard-coded header -->
```

**NEW:**
```php
<?php 
$pageTitle = 'Page Name';
include __DIR__ . '/../../includes/clinic_header.php'; 
?>
<!-- Content -->
<?php include __DIR__ . '/../../includes/clinic_footer.php'; ?>
```

### Step 6: Update Styling
**OLD:**
```html
<div style="padding:20px">
<table border="1">
```

**NEW:**
```html
<div class="max-w-7xl mx-auto px-4 py-8">
<table class="w-full rounded-xl shadow-md">
```

Use Tailwind CSS classes for all styling.

---

## 🚀 QUICK START FOR NEW MODULE

1. **Create folder structure:**
```bash
mkdir -p clinic/MODULE_NAME
mkdir -p api/MODULE_NAME
```

2. **Create index.php** in `clinic/MODULE_NAME/`
   - Copy template from above
   - Add module-specific logic

3. **Create API endpoints** in `api/MODULE_NAME/`
   - add.php
   - update.php
   - delete.php
   - list.php

4. **Test with clinic user login**

---

## 📊 PROGRESS TRACKER

- [x] Core Infrastructure
- [x] ClinicContext Helper
- [x] Header/Footer Includes  
- [x] Drug/Medicine Module
- [x] Patient Module
- [ ] Prescription Module
- [ ] Treatment Module
- [ ] Work Done Module
- [ ] Expense Module
- [ ] Reports Module
- [ ] Certificate Module

---

## 💡 KEY BENEFITS OF NEW STRUCTURE

✅ **Organized** - Files grouped by module, easy to find
✅ **Multi-clinic** - Each clinic has isolated database
✅ **Consistent** - All modules follow same pattern
✅ **Secure** - Prepared statements, auth checks
✅ **Modern UI** - Tailwind CSS, responsive design
✅ **Maintainable** - Clear separation of concerns
✅ **Scalable** - Easy to add new modules

---

## 📞 NEED HELP?

Follow the patterns shown in the completed modules:
- **Drug Module**: `clinic/drugs/index.php`
- **Patient Module**: `clinic/patients/index.php`

All other modules follow the SAME pattern! 🎯
