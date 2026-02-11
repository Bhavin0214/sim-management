<?php
// dashboard.php - Professional Dashboard with Dark Header and Clean UI
require_once 'config.php';
require_once 'form_structure.php';

checkAuth();

$user_id = getUserId();
$user_role = getUserRole();

// Redirect admin to admin dashboard if not filling a form
if ($user_role == 'admin' && !isset($_GET['form'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Get user data
$user_query = "SELECT * FROM master_employee WHERE ID = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);




// Define role permissions
$role_permissions = [
    'Employee' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => false,
        'emp_request_types' => []
    ],
    'HR' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => true,
        'emp_request_types' => ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp']
    ],
    'HOD' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => true,
        'emp_request_types' => ['isd_activision']
    ],
    'Head HR' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => true,
        'emp_request_types' => ['air_for_personal_use']
    ],
    'CEO' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => true,
        'emp_request_types' => ['air_offical_usage']
    ],
    'admin' => [
        'my_requests' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
        'emp_requests' => true,
        'emp_request_types' => ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp', 
                               'activision_e_sim', 'sim_replacement', 'temporary_suspension', 'air_offical_usage', 
                               'air_for_personal_use', 'isd_activision']
    ]
];


// Get rental plans for SIM Issuance form
$rental_plans_query = "SELECT * FROM rental_plan ORDER BY level_id";
$rental_plans_result = mysqli_query($conn, $rental_plans_query);
$rental_plans = [];
while($plan = mysqli_fetch_assoc($rental_plans_result)) {
    $rental_plans[] = $plan;
}

// Request type names
$request_names = [
    'sim_issue' => 'SIM Issuance',
    'activision_of_new_sim' => 'New SIM Activation',
    'deactivision_of_sim' => 'SIM Deactivation',
    'porting_cocp' => 'Porting COCP',
    'activision_e_sim' => 'E-SIM Activation',
    'sim_replacement' => 'SIM Replacement',
    'temporary_suspension' => 'Temporary Suspension',
    'air_offical_usage' => 'Intl Roaming (Official)',
    'air_for_personal_use' => 'Intl Roaming (Personal)',
    'isd_activision' => 'ISD Activation'
];

// Get permissions
$permissions = $role_permissions[$user_role];
$has_emp_requests = $permissions['emp_requests'];

// Get active form from URL or default
$active_form = $_GET['form'] ?? '';
$active_mode = $_GET['mode'] ?? 'my_request';

// Get request structure if form is active
$form_structure = null;
if ($active_form && isset($form_structures['request_types'][$active_form])) {
    $form_structure = $form_structures['request_types'][$active_form];
}

// Process form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $request_type = $_POST['request_type'] ?? '';
    $mode = $_POST['mode'] ?? 'my_request';
    
    if ($request_type && canAccessRequestType($user_role, $request_type, $mode)) {
        // Prepare data for backend
        $post_data = $_POST;
        
        // Handle file upload for POI
        $poi_file_path = '';
        if ($request_type == 'sim_issue' && isset($_FILES['POI']) && $_FILES['POI']['error'] == 0) {
            $upload_dir = 'uploads/poi/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['POI']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['POI']['tmp_name'], $file_path)) {
                $post_data['POI_FILE_PATH'] = $file_path;
                $post_data['POI_ORIGINAL_NAME'] = $_FILES['POI']['name'];
            }
        }
        
        // Add ajax flag for backend
        $post_data['ajax'] = 1;
        $post_data['action'] = 'submit_request';
        
        // Include files
        require_once 'backend.php';
        
        // Call submitRequest function directly
        $result = submitRequest();
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Request submitted successfully! Request ID: " . $result['request_id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error: " . ($result['message'] ?? "Submission failed");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NORA INFOTECH - SIM Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        :root {
            --primary: #004d4d;
            --primary-light: #006666;
            --primary-dark: #003333;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --accent: #00b4d8;
            --text: #333333;
            --text-light: #666666;
            --text-lighter: #999999;
            --border: #e5e7eb;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --radius: 8px;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: #f8fafc;
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
            min-height: 100vh;
        }
        
        /* Header - Dark Theme */
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            border-bottom: 2px solid var(--secondary);
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-placeholder {
            width: 40px;
            height: 20px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .logo-img{
            width: 100px;
            height: 100px;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-main {
            font-weight: 800;
            font-size: 20px;
            color: white;
            letter-spacing: 0.5px;
            line-height: 1;
        }
        
        .logo-sub {
            font-size: 12px;
            color: #ffffff;
            opacity: 0.9;
            letter-spacing: 0.3px;
        }
        
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: var(--radius);
            transition: 0.3s;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-menu:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.3);
        }
        
        .avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 13px;
            color: white;
        }
        
        .user-role {
            font-size: 11px;
            color: #b0b0b0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            min-width: 180px;
            display: none;
            overflow: hidden;
            z-index: 1001;
            border: 1px solid var(--border);
        }
        
        .dropdown.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text);
            text-decoration: none;
            border-bottom: 1px solid var(--border);
            transition: 0.2s;
            font-size: 13px;
        }
        
        .dropdown-item:hover {
            background: var(--bg-light);
            color: var(--primary);
        }
        
        .dropdown-item i {
            width: 16px;
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Main Layout */
        .main-container {
            margin-top: 65px;
            display: flex;
            min-height: calc(100vh - 65px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 240px;
            background: var(--bg-white);
            border-right: 1px solid var(--border);
            padding: 20px 0;
            position: fixed;
            top: 65px;
            bottom: 0;
            overflow-y: auto;
            box-shadow: var(--shadow);
        }
        
        .sidebar-section {
            margin-bottom: 24px;
        }
        
        .sidebar-title {
            padding: 0 20px 10px;
            color: var(--text-light);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 12px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            cursor: pointer;
            margin: 2px 0;
        }
        
        .sidebar-link:hover {
            background: var(--bg-light);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .sidebar-link.active {
            background: #e6fffa;
            color: var(--primary);
            border-left-color: var(--secondary);
            font-weight: 600;
        }
        
        .sidebar-link i {
            width: 16px;
            font-size: 12px;
            color: var(--text-light);
        }
        
        .sidebar-link.active i {
            color: var(--secondary);
        }
        
        /* Content */
        .content {
            flex: 1;
            margin-left: 240px;
            padding: 24px;
            background: #f8fafc;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        
        .page-subtitle {
            color: var(--text-light);
            font-size: 13px;
        }
        
        .close-form-btn {
            padding: 10px 18px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: 0.3s;
            box-shadow: var(--shadow);
        }
        
        .close-form-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        /* Form Container */
        .form-container {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            border: 1px solid var(--border);
            display: none;
        }
        
        .form-container.active {
            display: block;
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: var(--radius) var(--radius) 0 0;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 700;
        }
        
        .form-content {
            padding: 24px;
        }
        
        /* Messages */
        .message {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: var(--success);
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: var(--danger);
            border: 1px solid #fecaca;
        }
        
        /* Toast Notifications */
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2000;
            transform: translateX(150%);
            transition: transform 0.3s ease;
            max-width: 350px;
            border-left: 4px solid;
        }
        
        .toast-message.show {
            transform: translateX(0);
        }
        
        .toast-success {
            border-left-color: var(--success);
            background: #d1fae5;
            color: #065f46;
        }
        
        .toast-error {
            border-left-color: var(--danger);
            background: #fee2e2;
            color: #991b1b;
        }
        
        .toast-info {
            border-left-color: var(--info);
            background: #dbeafe;
            color: #1e40af;
        }
        
        .toast-warning {
            border-left-color: var(--warning);
            background: #fef3c7;
            color: #92400e;
        }
        
        .toast-message i {
            font-size: 18px;
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }
        
        .form-label .required {
            color: var(--danger);
            margin-left: 4px;
        }
        
        /* Fix for select list labels - make them visible */
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: var(--radius);
            font-size: 14px;
            transition: all 0.3s;
            background: var(--bg-white);
            color: var(--text);
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 77, 77, 0.1);
            background: var(--bg-white);
        }
        
        .form-input[readonly] {
            background: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
            border-color: #d1d5db;
        }
        
        /* Ensure select dropdown options are visible */
        .form-select option {
            color: var(--text);
            background: white;
            padding: 10px;
            font-size: 14px;
        }
        
        /* Style for select dropdown */
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            line-height: 1.5;
        }
        
        .auto-fill-note {
            font-size: 12px;
            color: var(--text-lighter);
            margin-top: 6px;
            font-style: italic;
        }
        
        .employee-id-input {
            position: relative;
        }
        
        .fetch-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 12px;
            cursor: pointer;
            display: none;
            transition: 0.2s;
        }
        
        .fetch-btn.show {
            display: block;
        }
        
        .fetch-btn:hover {
            background: var(--primary-light);
        }
        
        /* File Upload */
        .file-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: var(--radius);
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #f9fafb;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary);
            background: #f0fdfa;
        }
        
        .file-upload-container i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 12px;
        }
        
        .file-input {
            display: none;
        }
        
        .file-preview {
            margin-top: 20px;
            display: none;
        }
        
        .file-preview.show {
            display: block;
        }
        
        .preview-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px;
            background: #f9fafb;
            border-radius: var(--radius);
            margin-bottom: 10px;
            border: 1px solid var(--border);
        }
        
        .preview-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .preview-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }
        
        .preview-size {
            font-size: 12px;
            color: var(--text-lighter);
        }
        
        .delete-file {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .delete-file:hover {
            background: #dc2626;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        .form-btn {
            padding: 12px 28px;
            border: none;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: var(--shadow);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 77, 77, 0.2);
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: var(--text);
            border: 1px solid #d1d5db;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
        }
        
        /* Request Tables */
        .request-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        .card-header {
            padding: 16px 20px;
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .card-count {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            min-width: 28px;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .card-body {
            padding: 0;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 0 0 var(--radius) var(--radius);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        th {
            background: #f9fafb;
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-light);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            vertical-align: middle;
            color: var(--text);
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        /* Status Badges */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .status-complete {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-reject {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-view {
            background: var(--primary);
            color: white;
        }
        
        .btn-view:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-approve {
            background: var(--success);
            color: white;
        }
        
        .btn-approve:hover {
            background: #0da271;
        }
        
        .btn-reject {
            background: var(--danger);
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .btn-small {
            padding: 6px 10px;
            font-size: 11px;
        }
        
        /* Search Card */
        .search-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }
        
        .search-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }
        
        .search-title i {
            color: var(--secondary);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .filter-select, .filter-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 13px;
            background: white;
            color: var(--text);
            transition: 0.3s;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 77, 77, 0.1);
        }
        
        /* Fix filter select dropdown */
        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .filter-select option {
            color: var(--text);
            background: white;
            padding: 10px;
            font-size: 13px;
        }
        
        .search-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 16px;
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            background: var(--primary-light);
            box-shadow: 0 6px 20px rgba(0, 77, 77, 0.2);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: var(--text);
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-white);
            border-radius: var(--radius);
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow: hidden;
            animation: modalSlide 0.3s ease;
            box-shadow: var(--shadow-md);
        }
        
        @keyframes modalSlide {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 16px;
            font-weight: 700;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: 0.2s;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .modal-body {
            padding: 24px;
            max-height: 65vh;
            overflow-y: auto;
            font-size: 13px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .detail-label {
            width: 160px;
            font-weight: 600;
            color: var(--text-light);
            font-size: 12px;
        }
        
        .detail-value {
            flex: 1;
            font-size: 13px;
            color: var(--text);
        }
        
        /* Profile Modal */
        #profileModal .modal-content {
            max-width: 500px;
        }
        
        /* Admin Action Modal */
        #actionModal .modal-content {
            max-width: 450px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            
            .content {
                margin-left: 220px;
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                width: 280px;
                z-index: 999;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
                padding: 20px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                max-height: 90vh;
            }
            
            .header {
                padding: 0 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-btn {
                width: 100%;
                justify-content: center;
            }
            
            .search-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: 0.2s;
        }
        
        .mobile-toggle:hover {
            background: rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
            }
            
            .logo-text {
                font-size: 16px;
            }
            
            .logo-main {
                font-size: 16px;
            }
            
            .user-name {
                display: none;
            }
            
            .user-role {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 16px;
            }
            
            .content {
                padding: 16px;
            }
            
            .form-content {
                padding: 20px;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .modal-body {
                padding: 20px;
            }
            /* Add responsive styles */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -24px;
                padding: 0 24px;
            }
            
            table {
                min-width: 800px;
            }
            
            .request-card {
                margin: 10px -24px;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            
            .card-body {
                padding: 0;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                margin: 0 -24px;
                border-radius: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-actions, .form-actions {
                flex-direction: column;
            }
            
            .btn, .form-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 1024px) {
            .content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            
            .search-card {
                padding: 20px;
            }
        }

        /* Ensure tables are responsive */
        .dataTables_wrapper {
            overflow-x: auto;
        }

        /* Better table display on mobile */
        @media (max-width: 768px) {
            th, td {
                padding: 10px 12px;
                font-size: 12px;
            }
            
            .action-btn {
                padding: 6px 10px;
                font-size: 11px;
            }
            
            .status {
                padding: 4px 8px;
                font-size: 10px;
            }
        }

        /* Fix modal on mobile */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-section">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container">
                <div><img class= "logo-img" src="data/logo.png" alt=""></div>
                <div class="logo-text">
                    
                    <div class="logo-main">NORA INFOTECH</div>
                    <div class="logo-sub">SIM Management System</div>
                    <!-- <img src="data/logo.png" alt=""> -->
                </div>
            </div>
        </div>
        
        <div class="user-menu" onclick="toggleDropdown()">
            <div class="avatar"><?= strtoupper(substr($user_data['FULL_NAME'], 0, 2)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user_data['FULL_NAME']) ?></div>
                <div class="user-role"><?= $user_role ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            
            <div class="dropdown" id="dropdown">
                <a href="#" class="dropdown-item" onclick="showProfileModal()">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="#" class="dropdown-item" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- My Requests -->
            <div class="sidebar-section">
                <div class="sidebar-title">My New Requests</div>
                <?php foreach($permissions['my_requests'] as $request): ?>
                    <?php $title = $request_names[$request] ?? $request; ?>
                    <a href="dashboard.php?form=<?= $request ?>&mode=my_request" class="sidebar-link <?= ($active_form == $request && $active_mode == 'my_request') ? 'active' : '' ?>">
                        <i class="fas fa-<?= 
                            $request == 'activision_e_sim' ? 'sim-card' : 
                            ($request == 'sim_replacement' ? 'exchange-alt' : 'pause-circle') ?>"></i>
                        <?= $title ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Employee Requests -->
            <?php if($has_emp_requests): ?>
            <div class="sidebar-section">
                <div class="sidebar-title">Employee New Requests</div>
                <?php foreach($permissions['emp_request_types'] as $request): ?>
                    <?php if($request == 'all') continue; ?>
                    <?php $title = $request_names[$request] ?? $request; ?>
                    <a href="dashboard.php?form=<?= $request ?>&mode=emp_request" class="sidebar-link <?= ($active_form == $request && $active_mode == 'emp_request') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <?= $title ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </aside>
        
        <!-- Content -->
        <main class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?= htmlspecialchars($user_data['FULL_NAME']) ?></p>
                </div>
                <?php if($active_form): ?>
                    <button class="close-form-btn" onclick="window.location.href='dashboard.php'">
                        <i class="fas fa-times"></i> Close Form
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Messages -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success_message'] ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error_message'] ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Active Form -->
            <?php if($active_form && $form_structure): ?>
                <div class="form-container active" id="formContainer">
                    <div class="form-header">
                        <div class="form-title"><?= getRequestTypeName($active_form) ?> Request</div>
                    </div>
                    <div class="form-content">
                        <form method="POST" id="requestForm" enctype="multipart/form-data">
                            <!-- Employee Details Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user"></i> Employee Details
                                </h3>
                                
                                <div class="form-grid">
                                    <!-- Employee ID Field -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            Employee ID
                                            <span class="required">*</span>
                                        </label>
                                        <div class="employee-id-input">
                                            <input type="number" 
                                                   name="EMP_ID" 
                                                   id="EMP_ID" 
                                                   class="form-input" 
                                                   required
                                                   <?php if($active_mode === 'my_request'): ?>readonly value="<?= $user_id ?>"<?php endif; ?>
                                                   placeholder="Enter Employee ID"
                                                   oninput="checkEmployeeId()"
                                                   onchange="onEmployeeIdChange()"
                                                   onkeyup="onEmployeeIdKeyUp(event)">
                                            <button type="button" class="fetch-btn" id="fetchEmpBtn" onclick="fetchEmployeeDetails()">
                                                <i class="fas fa-search"></i> Fetch
                                            </button>
                                        </div>
                                        <?php if($active_mode === 'emp_request'): ?>
                                        <div class="auto-fill-note">
                                            Enter Employee ID and click Fetch to auto-fill details (or press Enter)
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Other Employee Fields -->
                                    <?php 
                                    $employee_fields = ['FULL_NAME', 'EMAIL_ID', 'MOBILE_NO', 'DESGINATION', 'LOCATION'];
                                    foreach($employee_fields as $field_name): 
                                        $field_config = $form_structures['common_fields']['employee_section']['fields'][$field_name] ?? [];
                                    ?>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <?= $field_config['label'] ?? str_replace('_', ' ', $field_name) ?>
                                                <?php if(($field_config['required'] ?? false)): ?><span class="required">*</span><?php endif; ?>
                                            </label>
                                            <input type="<?= $field_config['type'] ?? 'text' ?>"
                                                   name="<?= $field_name ?>"
                                                   id="<?= $field_name ?>"
                                                   class="form-input"
                                                   <?php if($active_mode === 'my_request'): ?>readonly value="<?= htmlspecialchars($user_data[$field_name] ?? '') ?>"<?php else: ?>readonly placeholder="Enter Employee ID first"<?php endif; ?>>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Request Specific Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-file-alt"></i> Request Details
                                </h3>
                                <div class="form-grid">
                                    <?php foreach($form_structure['fields'] as $field_name => $field_config): ?>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <?= $field_config['label'] ?>
                                                <?php if($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                                            </label>
                                            
                                            <?php if($field_name === 'LEVEL' && $active_form === 'sim_issue'): ?>
                                                <!-- Level Field -->
                                                <select name="LEVEL" id="LEVEL" class="form-select" required>
                                                    <option value="">Select Employee Level</option>
                                                    <option value="M-1">M-1</option>
                                                    <option value="M-2">M-2</option>
                                                    <option value="M-3">M-3</option>
                                                    <option value="M-4">M-4</option>
                                                    <option value="M-5">M-5</option>
                                                    <option value="M-6">M-6</option>
                                                    <option value="M-7">M-7</option>
                                                    <option value="M-8">M-8</option>
                                                    <option value="M-9">M-9</option>
                                                    <option value="M-10">M-10</option>
                                                    <option value="M-11">M-11</option>
                                                    <option value="Common">Common</option>
                                                </select>
                                                
                                            <?php elseif($field_name === 'RENTAL_PLAN' && $active_form === 'sim_issue'): ?>
                                                <!-- Custom Rental Plan with Level -->
                                                <select name="RENTAL_PLAN" id="RENTAL_PLAN" class="form-select" required>
                                                    <option value="">Select Rental Plan</option>
                                                    <?php foreach($rental_plans as $plan): ?>
                                                        <option value="<?= $plan['level'] ?> - ₹<?= $plan['vi_monthly_rental_plan'] ?>">
                                                            <?= $plan['level'] ?> (<?= $plan['mobile_usage_limits'] ?> mins) - ₹<?= $plan['vi_monthly_rental_plan'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                
                                            <?php elseif($field_name === 'POI' && $active_form === 'sim_issue'): ?>
                                                <!-- File Upload with Preview -->
                                                <div class="file-upload-container" onclick="document.getElementById('POI').click()">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <div style="margin-bottom: 10px; font-weight: 600;">Click to upload Proof of Identity</div>
                                                    <div style="font-size: 12px; color: var(--text-lighter);">
                                                        Supported: image/*, .pdf, .doc, .docx (Max: 5MB)
                                                    </div>
                                                    <input type="file" 
                                                           name="POI" 
                                                           id="POI" 
                                                           class="file-input" 
                                                           accept="image/*,.pdf,.doc,.docx"
                                                           required
                                                           onchange="handleFileUpload(this)">
                                                </div>
                                                <div class="file-preview" id="filePreview">
                                                    <!-- File preview will appear here -->
                                                </div>
                                                
                                            <?php elseif($field_config['type'] === 'select'): ?>
                                                <select name="<?= $field_name ?>"
                                                        id="<?= $field_name ?>"
                                                        class="form-select"
                                                        <?php if($field_config['required']): ?>required<?php endif; ?>>
                                                    <option value="">Select <?= $field_config['label'] ?></option>
                                                    <?php foreach($field_config['options'] as $value => $label): ?>
                                                        <option value="<?= htmlspecialchars($value) ?>">
                                                            <?= htmlspecialchars($label) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            
                                            <?php elseif($field_config['type'] === 'textarea'): ?>
                                                <textarea name="<?= $field_name ?>"
                                                          id="<?= $field_name ?>"
                                                          class="form-textarea"
                                                          <?php if($field_config['required']): ?>required<?php endif; ?>
                                                          placeholder="<?= $field_config['placeholder'] ?? '' ?>"
                                                          rows="4"></textarea>
                                            
                                            <?php elseif($field_config['type'] === 'file' && $field_name !== 'POI'): ?>
                                                <input type="file"
                                                       name="<?= $field_name ?>"
                                                       id="<?= $field_name ?>"
                                                       class="form-input"
                                                       <?php if($field_config['accept']): ?>accept="<?= $field_config['accept'] ?>"<?php endif; ?>
                                                       <?php if($field_config['required']): ?>required<?php endif; ?>>
                                                <div class="auto-fill-note">
                                                    Supported: <?= $field_config['accept'] ?? 'All files' ?>
                                                </div>
                                            
                                            <?php else: ?>
                                                <input type="<?= $field_config['type'] ?>"
                                                       name="<?= $field_name ?>"
                                                       id="<?= $field_name ?>"
                                                       class="form-input"
                                                       <?php if($field_config['required']): ?>required<?php endif; ?>
                                                       placeholder="<?= $field_config['placeholder'] ?? '' ?>"
                                                       <?php if(isset($field_config['min'])): ?>min="<?= $field_config['min'] ?>"<?php endif; ?>
                                                       <?php if(isset($field_config['max'])): ?>max="<?= $field_config['max'] ?>"<?php endif; ?>>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Hidden fields -->
                            <input type="hidden" name="request_type" value="<?= $active_form ?>">
                            <input type="hidden" name="mode" value="<?= $active_mode ?>">
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="button" class="form-btn btn-cancel" onclick="window.location.href='dashboard.php'">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" name="submit_request" class="form-btn btn-submit">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- My Requests Section -->
            <?php if(!$active_form): ?>
            <div class="search-card">
                <div class="search-title">
                    <i class="fas fa-search"></i> Search My Requests
                </div>
                <form id="mySearchForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Request Type</label>
                            <select class="filter-select" id="myReqType">
                                <option value="all">All Types</option>
                                <?php foreach($permissions['my_requests'] as $request): ?>
                                    <option value="<?= $request ?>"><?= $request_names[$request] ?? $request ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select class="filter-select" id="myReqStatus">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="complete">Complete</option>
                                <option value="reject">Rejected</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date Range</label>
                            <select class="filter-select" id="myReqDate">
                                <option value="all">All Time</option>
                                <option value="latest">Latest (7 days)</option>
                                <option value="last_3_months">Last 3 Months</option>
                                <option value="3_6_months">3-6 Months</option>
                                <option value="6_12_months">6-12 Months</option>
                                <option value="above_12_months">Above 12 Months</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetMySearch()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="button" class="btn btn-primary" onclick="searchMyRequests()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="request-card">
                <div class="card-header">
                    <div class="card-title">My Requests</div>
                    <div class="card-count" id="myReqCount">0</div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="myRequestsTable">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Employee</th>
                                    <th>Requested By</th> <!-- Add this column -->
                                    <th>Type</th>
                                    <th>SIM Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="myRequestsBody">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Employee Requests Section (if applicable) -->
            <?php if($has_emp_requests): ?>
            <div class="search-card">
                <div class="search-title">
                    <i class="fas fa-search"></i> Search Employee Requests
                </div>
                <form id="empSearchForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Employee ID</label>
                            <input type="number" class="filter-input" id="empReqId" placeholder="Enter Employee ID">
                        </div>
                        <div class="filter-group">
                            <label>Request Type</label>
                            <select class="filter-select" id="empReqType">
                                <option value="all">All Types</option>
                                <?php foreach($permissions['emp_request_types'] as $request): ?>
                                    <?php if($request == 'all'): ?>
                                        <option value="all">All Requests</option>
                                    <?php else: ?>
                                        <option value="<?= $request ?>"><?= $request_names[$request] ?? $request ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select class="filter-select" id="empReqStatus">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="complete">Complete</option>
                                <option value="reject">Rejected</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date Range</label>
                            <select class="filter-select" id="empReqDate">
                                <option value="all">All Time</option>
                                <option value="latest">Latest (7 days)</option>
                                <option value="last_3_months">Last 3 Months</option>
                                <option value="3_6_months">3-6 Months</option>
                                <option value="6_12_months">6-12 Months</option>
                                <option value="above_12_months">Above 12 Months</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetEmpSearch()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="button" class="btn btn-primary" onclick="searchEmpRequests()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="request-card">
                <div class="card-header">
                    <div class="card-title">Employee Requests</div>
                    <div class="card-count" id="empReqCount">0</div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="empRequestsTable">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Employee</th>
                                    <th>Requested By</th> <!-- Add this column -->
                                    <th>Type</th>
                                    <th>SIM Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                    <?php if($user_role == 'admin'): ?>
                                    <th>Action By</th> <!-- Show who took action -->
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="empRequestsBody">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modals -->
    <!-- Request Details Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Request Details</div>
                <button class="modal-close" onclick="closeModal('requestModal')">&times;</button>
            </div>
            <div class="modal-body" id="requestDetails">
                <!-- Details loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Profile Modal -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">My Profile</div>
                <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-row">
                    <div class="detail-label">Full Name:</div>
                    <div class="detail-value"><?= htmlspecialchars($user_data['FULL_NAME']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Employee ID:</div>
                    <div class="detail-value"><?= $user_data['ID'] ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?= htmlspecialchars($user_data['EMAIL_ID']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Mobile:</div>
                    <div class="detail-value"><?= htmlspecialchars($user_data['MOBILE_NO']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Designation:</div>
                    <div class="detail-value"><?= htmlspecialchars($user_data['DESGINATION'] ?? 'Not specified') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Location:</div>
                    <div class="detail-value"><?= htmlspecialchars($user_data['LOCATION'] ?? 'Not specified') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Role:</div>
                    <div class="detail-value"><?= $user_data['ROLE'] ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Action Modal -->
    <?php if($user_role == 'admin'): ?>
    <div class="modal" id="actionModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Update Request Status</div>
                <button class="modal-close" onclick="closeModal('actionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="actionReqId">
                <input type="hidden" id="actionReqTable">
                
                <div class="filter-group" style="margin-bottom: 16px;">
                    <label>Action</label>
                    <select class="filter-select" id="actionStatus" onchange="toggleReason()">
                        <option value="complete">Approve</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                
                <div class="filter-group" id="reasonField" style="display: none;">
                    <label>Rejection Reason</label>
                    <textarea class="filter-input" id="actionReason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="closeModal('actionModal')">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAction()">Submit</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>

        // Handle form submission
        document.getElementById('requestForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            let firstError = null;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger)';
                    isValid = false;
                    if (!firstError) firstError = field;
                }
            });
            
            if (!isValid) {
                showToast('Please fill all required fields', 'error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            
            // Submit form
            const formData = new FormData(this);
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            fetch('backend.php?ajax=1&action=submit_request', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Request submitted successfully! Request ID: ' + data.request_id, 'success');
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showToast('Error: ' + (data.message || 'Submission failed'), 'error');
                }
            })
            .catch(error => {
                showToast('Network error. Please try again.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        // Global variables
        let currentRequestId = null;
        let currentRequestTable = null;
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadMyRequests();
            <?php if($has_emp_requests && !$active_form): ?>loadEmpRequests();<?php endif; ?>
            
            // Auto-fill for my requests
            const mode = '<?= $active_mode ?>';
            const form = '<?= $active_form ?>';
            
            if (mode === 'my_request' && form) {
                // Auto-fill current user data
                document.getElementById('EMP_ID').value = <?= $user_id ?>;
                document.getElementById('FULL_NAME').value = '<?= addslashes($user_data['FULL_NAME']) ?>';
                document.getElementById('EMAIL_ID').value = '<?= addslashes($user_data['EMAIL_ID']) ?>';
                document.getElementById('MOBILE_NO').value = '<?= addslashes($user_data['MOBILE_NO']) ?>';
                document.getElementById('DESGINATION').value = '<?= addslashes($user_data['DESGINATION'] ?? '') ?>';
                document.getElementById('LOCATION').value = '<?= addslashes($user_data['LOCATION'] ?? '') ?>';
            }
        });
        
        // Toggle dropdown
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('show');
        }
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.getElementById('dropdown').classList.remove('show');
            }
            if (!e.target.closest('.sidebar') && !e.target.closest('.mobile-toggle')) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
        
        // Load my requests
        function loadMyRequests() {
            fetch('backend.php?ajax=1&action=get_my_requests')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        populateMyRequests(data.requests);
                    }
                });
        }
        
        // Load employee requests
        function loadEmpRequests() {
            fetch('backend.php?ajax=1&action=get_emp_requests')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        populateEmpRequests(data.requests);
                    }
                });
        }
        
        // Populate my requests table
        // Populate my requests table
        // Populate my requests table
        function populateMyRequests(requests) {
            const tbody = document.getElementById('myRequestsBody');
            tbody.innerHTML = '';
            
            requests.forEach(req => {
                const row = document.createElement('tr');
                const reqId = req.REQ_ID || req.REQUEST_ID || req.ID || 'N/A';
                const empName = req.EMP_NAME || req.FULL_NAME || 'You';
                const reqByName = req.REQ_BY_NAME || 'You'; // Show who made the request
                const simNo = req.SIM_NO || req.SIMM_NO || 'N/A';
                const date = formatDate(req.req_at || req.REQ_AT);
                const status = req.STATUS || 'pending';
                
                row.innerHTML = `
                    <td>${reqId}</td>
                    <td>${empName}</td>
                    <td>${reqByName}</td> <!-- Requested By column -->
                    <td>${req.request_type || 'Unknown'}</td>
                    <td>${simNo}</td>
                    <td>${date}</td>
                    <td><span class="status status-${status}">${status}</span></td>
                    <td>
                        <button class="action-btn btn-view" onclick="viewRequest('${req.source_table}', '${reqId}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            document.getElementById('myReqCount').textContent = requests.length;
            
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#myRequestsTable')) {
                $('#myRequestsTable').DataTable().destroy();
            }
            if (requests.length > 0) {
                $('#myRequestsTable').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
                    "order": [[5, "desc"]], // Date column is now 5th (0-indexed)
                    "language": {
                        "emptyTable": "No requests found"
                    }
                });
            }
        }
        
        // Populate employee requests table
        // Populate employee requests table
        // Populate employee requests table
        function populateEmpRequests(requests) {
            const tbody = document.getElementById('empRequestsBody');
            tbody.innerHTML = '';
            
            requests.forEach(req => {
                const row = document.createElement('tr');
                const reqId = req.REQ_ID || req.REQUEST_ID || req.ID || 'N/A';
                const empName = req.FULL_NAME || req.EMP_NAME || 'Unknown';
                const reqByName = req.REQ_BY_NAME || 'Unknown';
                const actByName = req.ACT_BY_NAME || '-';
                const simNo = req.SIM_NO || req.SIMM_NO || 'N/A';
                const date = formatDate(req.req_at || req.REQ_AT);
                const status = req.STATUS || 'pending';
                
                let adminActions = '';
                <?php if($user_role == 'admin'): ?>
                if (status === 'pending') {
                    adminActions = `
                        <div style="display: flex; gap: 5px;">
                            <button class="action-btn btn-approve btn-small" onclick="showActionModal('${req.source_table}', '${reqId}', 'complete')">
                                <i class="fas fa-check"></i> 
                            </button>
                            <button class="action-btn btn-reject btn-small" onclick="showActionModal('${req.source_table}', '${reqId}', 'reject')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                } else {
                    adminActions = actByName; // Show who took action
                }
                <?php endif; ?>
                
                row.innerHTML = `
                    <td>${reqId}</td>
                    <td>${empName}</td>
                    <td>${reqByName}</td> <!-- Requested By -->
                    <td>${req.request_type || 'Unknown'}</td>
                    <td>${simNo}</td>
                    <td>${date}</td>
                    <td><span class="status status-${status}">${status}</span></td>
                    <td>
                        <button class="action-btn btn-view btn-small" onclick="viewRequest('${req.source_table}', '${reqId}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                    <?php if($user_role == 'admin'): ?>
                    <td>${adminActions}</td>
                    <?php endif; ?>
                `;
                tbody.appendChild(row);
            });
            
            document.getElementById('empReqCount').textContent = requests.length;
            
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#empRequestsTable')) {
                $('#empRequestsTable').DataTable().destroy();
            }
            if (requests.length > 0) {
                $('#empRequestsTable').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
                    "order": [[5, "desc"]], // Date column is now 5th
                    "language": {
                        "emptyTable": "No requests found"
                    }
                });
            }
        }
        
        // Format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-IN');
        }
        
        // View request details
        function viewRequest(table, id) {
            fetch(`backend.php?ajax=1&action=view_request&table=${table}&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showRequestDetails(data.request, table);
                    }
                });
        }
        
        // Show request details
        // Show request details
        function showRequestDetails(request, table) {
            const modalBody = document.getElementById('requestDetails');
            let html = '';
            
            // Create a mapping for better display names
            const fieldLabels = {
                'EMP_NAME': 'Employee Name',
                'EMP_EMAIL': 'Employee Email',
                'EMP_MOBILE': 'Employee Mobile',
                'FULL_NAME': 'Employee Name',
                'EMAIL_ID': 'Employee Email',
                'MOBILE_NO': 'Employee Mobile',
                'REQ_BY_NAME': 'Requested By',
                'ACT_BY_NAME': 'Action By',
                'REQ_AT': 'Request Date',
                'ACT_AT': 'Action Date',
                'STATUS': 'Status',
                'SIM_NO': 'SIM Number',
                'SIMM_NO': 'E-SIM Number',
                'REMARKS': 'Remarks',
                'REASON': 'Reason',
                'REJECT_REASON': 'Rejection Reason',
                'DURATION': 'Duration (Days)',
                'POI_DOC_NO': 'POI Document No',
                'POI_DOC_TYPE': 'POI Document Type',
                'RENTAL_PLAN': 'Rental Plan',
                'MOBILE_NO_AL': 'Alternative Mobile',
                'CURRENT': 'Current Number',
                'TO_SIM': 'New SIM Number'
            };
            
            // Group fields by category for better organization
            const employeeFields = ['EMP_NAME', 'FULL_NAME', 'EMAIL_ID', 'EMP_EMAIL', 'MOBILE_NO', 'EMP_MOBILE', 'DESGINATION', 'LOCATION'];
            const requestFields = ['REQ_BY_NAME', 'ACT_BY_NAME', 'REQ_AT', 'ACT_AT', 'STATUS', 'REMARKS', 'REASON', 'REJECT_REASON'];
            const simFields = ['SIM_NO', 'SIMM_NO', 'MOBILE_NO_AL', 'CURRENT', 'TO_SIM', 'DURATION', 'POI_DOC_NO', 'POI_DOC_TYPE', 'RENTAL_PLAN'];
            
            // Function to get display label
            function getDisplayLabel(key) {
                return fieldLabels[key] || key.replace(/_/g, ' ');
            }
            
            // Skip unwanted fields
            const skipFields = ['ID', 'EMP_ID', 'REQ_BY', 'ACT_BY', 'source_table', 'request_type', 'table_name', 'req_at', 'act_at', 'req_by', 'act_by'];
            
            // Employee Details Section
            html += '<h4 style="color: var(--primary); margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">Employee Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && employeeFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">${getDisplayLabel(key)}:</div>
                            <div class="detail-value">${value}</div>
                        </div>
                    `;
                }
            }
            
            // Request Details Section
            html += '<h4 style="color: var(--primary); margin-top: 20px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">Request Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && requestFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">${getDisplayLabel(key)}:</div>
                            <div class="detail-value">${value}</div>
                        </div>
                    `;
                }
            }
            
            // SIM Details Section
            html += '<h4 style="color: var(--primary); margin-top: 20px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">SIM Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && simFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">${getDisplayLabel(key)}:</div>
                            <div class="detail-value">${value}</div>
                        </div>
                    `;
                }
            }
            
            // Other fields (catch-all)
            let hasOtherFields = false;
            let otherFieldsHtml = '';
            for (const [key, value] of Object.entries(request)) {
                if (value && !skipFields.includes(key) && 
                    !employeeFields.includes(key) && 
                    !requestFields.includes(key) && 
                    !simFields.includes(key) &&
                    key !== 'REQ_ID' && key !== 'REQUEST_ID' && key !== 'ID') {
                    
                    otherFieldsHtml += `
                        <div class="detail-row">
                            <div class="detail-label">${getDisplayLabel(key)}:</div>
                            <div class="detail-value">${value}</div>
                        </div>
                    `;
                    hasOtherFields = true;
                }
            }
            
            if (hasOtherFields) {
                html += '<h4 style="color: var(--primary); margin-top: 20px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">Other Details</h4>';
                html += otherFieldsHtml;
            }
            
            // Show request ID
            const reqId = request.REQ_ID || request.REQUEST_ID || request.ID;
            if (reqId) {
                html = `
                    <div class="detail-row" style="background: #f0f9ff; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <div class="detail-label" style="font-weight: bold; color: var(--primary);">Request ID:</div>
                        <div class="detail-value" style="font-weight: bold; color: var(--primary);">${reqId}</div>
                    </div>
                ` + html;
            }
            
            modalBody.innerHTML = html;
            showModal('requestModal');
        }
        // Check if employee ID is entered
        function checkEmployeeId() {
            const empId = document.getElementById('EMP_ID').value;
            const fetchBtn = document.getElementById('fetchEmpBtn');
            
            if (empId && empId.length > 0) {
                fetchBtn.classList.add('show');
            } else {
                fetchBtn.classList.remove('show');
            }
        }
        
        // Employee ID change handler
        function onEmployeeIdChange() {
            const mode = '<?= $active_mode ?>';
            if (mode === 'emp_request') {
                const empId = document.getElementById('EMP_ID').value;
                if (empId) {
                    // Auto-fetch after a short delay
                    setTimeout(() => {
                        fetchEmployeeDetails(true);
                    }, 500);
                }
            }
        }
        
        // Employee ID keyup handler
        function onEmployeeIdKeyUp(e) {
            const mode = '<?= $active_mode ?>';
            if (mode === 'emp_request' && e.key === 'Enter') {
                const empId = document.getElementById('EMP_ID').value;
                if (empId) {
                    fetchEmployeeDetails();
                    e.preventDefault();
                }
            }
        }
        
        // Fetch employee details by ID
        function fetchEmployeeDetails(autoMode = false) {
            let empId;
            
            if (autoMode) {
                empId = document.getElementById('EMP_ID').value;
                if (!empId) return;
            } else {
                empId = document.getElementById('EMP_ID').value;
                if (!empId) {
                    showToast('Please enter an Employee ID', 'error');
                    return;
                }
            }
            
            // Validate it's a number
            if (isNaN(empId) || empId <= 0) {
                showToast('Please enter a valid Employee ID', 'error');
                return;
            }
            
            // Show loading state
            const fetchBtn = document.getElementById('fetchEmpBtn');
            if (fetchBtn) {
                const originalHTML = fetchBtn.innerHTML;
                fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                fetchBtn.disabled = true;
            }
            
            // Add a timestamp to prevent caching
            const timestamp = new Date().getTime();
            
            fetch(`backend.php?ajax=1&action=get_employee&id=${empId}&_=${timestamp}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.success && data.employee) {
                        // Fill employee details
                        const emp = data.employee;
                        document.getElementById('FULL_NAME').value = emp.FULL_NAME || '';
                        document.getElementById('EMAIL_ID').value = emp.EMAIL_ID || '';
                        document.getElementById('MOBILE_NO').value = emp.MOBILE_NO || '';
                        document.getElementById('DESGINATION').value = emp.DESGINATION || '';
                        document.getElementById('LOCATION').value = emp.LOCATION || '';
                        
                        // Show success message
                        if (!autoMode) {
                            showToast('Employee details loaded successfully', 'success');
                        }
                    } else if (!autoMode) {
                        showToast(data.message || 'Employee not found', 'error');
                        clearEmployeeDetails();
                    }
                })
                .catch(error => {
                    if (!autoMode) {
                        showToast('Error fetching employee details', 'error');
                    }
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Restore button state
                    if (fetchBtn) {
                        fetchBtn.innerHTML = '<i class="fas fa-search"></i> Fetch';
                        fetchBtn.disabled = false;
                    }
                });
        }
        
        // Clear employee details
        function clearEmployeeDetails() {
            document.getElementById('FULL_NAME').value = '';
            document.getElementById('EMAIL_ID').value = '';
            document.getElementById('MOBILE_NO').value = '';
            document.getElementById('DESGINATION').value = '';
            document.getElementById('LOCATION').value = '';
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            // Remove existing toast
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) {
                existingToast.remove();
            }
            
            // Create toast
            const toast = document.createElement('div');
            toast.className = `toast-message toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                 type === 'error' ? 'exclamation-circle' : 
                                 type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add to body
            document.body.appendChild(toast);
            
            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);
        }
        
        // Handle file upload for POI
        function handleFileUpload(input) {
            const file = input.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File size exceeds 5MB limit', 'error');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!validTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i)) {
                    showToast('Invalid file type. Please upload image, PDF, or Word document', 'error');
                    input.value = '';
                    return;
                }
                
                // Format file size
                const fileSize = formatFileSize(file.size);
                
                // Create object URL for preview
                const fileURL = URL.createObjectURL(file);
                
                // Determine file icon
                let fileIcon = 'fa-file';
                if (file.type.includes('image/')) {
                    fileIcon = 'fa-file-image';
                } else if (file.type.includes('pdf')) {
                    fileIcon = 'fa-file-pdf';
                } else if (file.type.includes('word') || file.type.includes('document')) {
                    fileIcon = 'fa-file-word';
                }
                
                // Show preview with view button
                preview.innerHTML = `
                    <div class="preview-item">
                        <div class="preview-info">
                            <i class="fas ${fileIcon}" style="color: var(--accent); font-size: 24px;"></i>
                            <div>
                                <div class="preview-name">${file.name}</div>
                                <div class="preview-size">${fileSize}</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="action-btn btn-view btn-small" onclick="viewUploadedFile('${fileURL}', '${file.name}', '${file.type}')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button type="button" class="action-btn btn-reject btn-small" onclick="deleteFile()">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
                preview.classList.add('show');
                
                // Store file reference for later use
                preview.dataset.fileName = file.name;
                preview.dataset.fileURL = fileURL;
            }
        }

        // View uploaded file
        function viewUploadedFile(fileURL, fileName, fileType) {
            // Create modal HTML
            const modalHTML = `
                <div class="modal show" id="fileViewModal">
                    <div class="modal-content" style="max-width: 800px; max-height: 90vh;">
                        <div class="modal-header">
                            <div class="modal-title">View Document: ${fileName}</div>
                            <button class="modal-close" onclick="closeModal('fileViewModal')">&times;</button>
                        </div>
                        <div class="modal-body" style="height: 70vh; padding: 0; overflow: auto;">
                            ${fileType.includes('image/') ? 
                                `<img src="${fileURL}" alt="${fileName}" style="width: 100%; height: auto; max-height: 65vh; object-fit: contain;">` :
                                `<iframe src="${fileURL}" style="width: 100%; height: 100%; border: none; min-height: 65vh;"></iframe>`
                            }
                        </div>
                        <div style="padding: 15px; text-align: center; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 10px;">
                            <a href="${fileURL}" download="${fileName}" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button class="btn btn-secondary" onclick="closeModal('fileViewModal')">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to body
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHTML;
            document.body.appendChild(modalContainer);
            
            // Show modal
            document.getElementById('fileViewModal').classList.add('show');
            
            // Close modal when clicking outside
            document.getElementById('fileViewModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal('fileViewModal');
                }
            });
        }
        
        // Delete uploaded file
        function deleteFile() {
            const fileInput = document.getElementById('POI');
            const preview = document.getElementById('filePreview');
            
            fileInput.value = '';
            preview.innerHTML = '';
            preview.classList.remove('show');
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Search my requests
        function searchMyRequests() {
            const type = document.getElementById('myReqType').value;
            const status = document.getElementById('myReqStatus').value;
            const date = document.getElementById('myReqDate').value;
            
            const params = new URLSearchParams();
            params.append('type', type);
            params.append('status', status);
            params.append('date', date);
            
            fetch(`backend.php?ajax=1&action=search_my_requests&${params}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        populateMyRequests(data.requests);
                    }
                });
        }
        
        // Search employee requests
        function searchEmpRequests() {
            const empId = document.getElementById('empReqId').value;
            const type = document.getElementById('empReqType').value;
            const status = document.getElementById('empReqStatus').value;
            const date = document.getElementById('empReqDate').value;
            
            const params = new URLSearchParams();
            if (empId) params.append('emp_id', empId);
            params.append('type', type);
            params.append('status', status);
            params.append('date', date);
            
            fetch(`backend.php?ajax=1&action=search_emp_requests&${params}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        populateEmpRequests(data.requests);
                    }
                });
        }
        
        // Reset my search
        function resetMySearch() {
            document.getElementById('myReqType').value = 'all';
            document.getElementById('myReqStatus').value = 'all';
            document.getElementById('myReqDate').value = 'all';
            loadMyRequests();
        }
        
        // Reset employee search
        function resetEmpSearch() {
            document.getElementById('empReqId').value = '';
            document.getElementById('empReqType').value = 'all';
            document.getElementById('empReqStatus').value = 'all';
            document.getElementById('empReqDate').value = 'all';
            loadEmpRequests();
        }
        
        // Show profile modal
        function showProfileModal() {
            showModal('profileModal');
        }
        
        // Show modal
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.getElementById('dropdown').classList.remove('show');
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Logout
        function logout() {
            fetch('backend.php?ajax=1&action=logout')
                .then(() => {
                    window.location.href = 'index.php';
                });
        }
        
        // Admin actions
        <?php if($user_role == 'admin'): ?>
        function showActionModal(table, id, status) {
            currentRequestTable = table;
            currentRequestId = id;
            
            document.getElementById('actionReqId').value = id;
            document.getElementById('actionReqTable').value = table;
            document.getElementById('actionStatus').value = status;
            document.getElementById('actionReason').value = '';
            
            if (status === 'reject') {
                document.getElementById('reasonField').style.display = 'block';
            } else {
                document.getElementById('reasonField').style.display = 'none';
            }
            
            showModal('actionModal');
        }
        
        function toggleReason() {
            const status = document.getElementById('actionStatus').value;
            const reasonField = document.getElementById('reasonField');
            reasonField.style.display = status === 'reject' ? 'block' : 'none';
        }
        
        function submitAction() {
            const table = document.getElementById('actionReqTable').value;
            const id = document.getElementById('actionReqId').value;
            const status = document.getElementById('actionStatus').value;
            const reason = document.getElementById('actionReason').value;
            
            const formData = new FormData();
            formData.append('table', table);
            formData.append('id', id);
            formData.append('status', status);
            formData.append('reason', reason);
            
            fetch('backend.php?ajax=1&action=update_status', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Request updated successfully', 'success');
                    closeModal('actionModal');
                    loadEmpRequests();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
        }
        <?php endif; ?>
        
        // Auto-hide sidebar on mobile when clicking content
        document.querySelector('.content').addEventListener('click', function() {
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
    </script>
</body>
</html>
<?php 
mysqli_close($conn); 
?>