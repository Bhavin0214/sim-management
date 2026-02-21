<?php
// admin_dashboard.php - Complete Admin Panel

// Start session first
session_start();

// Include required files
require_once 'config.php';
require_once 'admin_functions.php';

// Check admin authentication
checkAdminAuth();

// Get database connection
$conn = getDbConnection();

// Get admin data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM master_employee WHERE ID = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get current view from URL
$view = $_GET['view'] ?? 'dashboard';
$active_section = $_GET['section'] ?? 'all_requests';

// Get statistics for dashboard
if ($view == 'dashboard') {
    $stats = getAdminStats();
    $request_stats = getRequestStatsByType();
}

// Get inventory for inventory view
if ($view == 'inventory') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $inventory = getAllInventory($search, $status);
}

// Get users for user management
if ($view == 'users') {
    $search = $_GET['search'] ?? '';
    $users = getAllUsers($search);
}

// Get requests for requests view
if ($view == 'requests') {
    $emp_id = $_GET['emp_id'] ?? '';
    $type = $_GET['type'] ?? 'all';
    $status_filter = $_GET['status'] ?? ($active_section == 'pending' ? 'pending' : 'all');
    $date_range = $_GET['date'] ?? 'all';
    
    if ($active_section == 'my_requests') {
        // Get admin's own requests using backend function
        require_once 'backend.php';
        $my_requests_result = getMyRequests();
        $emp_requests = $my_requests_result['requests'] ?? [];
    } else {
        // Get all employee requests
        $emp_requests = getAllEmployeeRequests($emp_id, $type, $status_filter, $date_range);
    }
    
    // Filter pending requests if needed
    if ($active_section == 'pending') {
        $emp_requests = array_filter($emp_requests, function($req) {
            return ($req['STATUS'] ?? '') == 'pending';
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NORA INFOTECH - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        /* CSS styles remain exactly the same as before */
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
        
        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
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
            width: 260px;
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
            margin-left: 260px;
            padding: 24px;
            background: #f8fafc;
            min-height: calc(100vh - 65px);
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
        
        /* Dashboard Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
        .stat-icon.pending { background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%); }
        .stat-icon.complete { background: linear-gradient(135deg, var(--success) 0%, var(--secondary-light) 100%); }
        .stat-icon.today { background: linear-gradient(135deg, var(--accent) 0%, #3b82f6 100%); }
        .stat-icon.users { background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); }
        .stat-icon.sim { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); }
        
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
        }
        
        /* Cards */
        .card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            border: 1px solid var(--border);
            overflow: hidden;
        }
        
        .card-header {
            padding: 16px 20px;
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
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
        
        .table-container {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 800px;
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
            position: sticky;
            top: 0;
            z-index: 10;
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
        
        /* Inventory Status Badges */
        .status-available {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-assigned {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .status-active {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .status-suspended {
            background: #e5e7eb;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .status-deactivated {
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
            margin: 2px;
        }
        
        .btn-view {
            background: var(--primary);
            color: white;
        }
        
        .btn-view:hover {
            background: var(--primary-light);
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
        
        .btn-assign {
            background: var(--info);
            color: white;
        }
        
        .btn-assign:hover {
            background: #2563eb;
        }
        
        .btn-edit {
            background: var(--warning);
            color: white;
        }
        
        .btn-edit:hover {
            background: #d97706;
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        /* Request Tabs */
        .request-tabs {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .request-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
            color: #64748b;
            transition: 0.3s;
        }
        
        .request-tab.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        /* Import Card */
        .import-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            border: 2px dashed var(--border);
            text-align: center;
        }
        
        .import-icon {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .import-instructions {
            background: #f8fafc;
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 20px;
            text-align: left;
            font-size: 13px;
        }
        
        .import-instructions h4 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .import-instructions ul {
            padding-left: 20px;
            margin-bottom: 10px;
        }
        
        .import-instructions li {
            margin-bottom: 5px;
        }
        
        /* File Upload */
        .file-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #f9fafb;
            margin: 20px 0;
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
            padding: 20px;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-white);
            border-radius: var(--radius);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
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
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .content {
                margin-left: 240px;
                padding: 20px;
            }
            
            .filter-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .search-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .table-container {
                margin: 0 -24px;
                padding: 0 24px;
            }
            
            table {
                min-width: 1000px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 16px;
            }
            
            .content {
                padding: 16px;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
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
            
            .user-name {
                display: none;
            }
            
            .user-role {
                display: none;
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
                <div><img class="logo-img" src="data/logo.png" alt="Nora Infotech" onerror="this.src='https://via.placeholder.com/40x40?text=NI'"></div>
                <div class="logo-text">
                    <div class="logo-main">NORA INFOTECH</div>
                    <div class="logo-sub">Admin Panel</div>
                </div>
            </div>
        </div>
        
        <div class="user-menu" onclick="toggleDropdown()">
            <div class="avatar"><?= strtoupper(substr($user_data['FULL_NAME'], 0, 2)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user_data['FULL_NAME']) ?></div>
                <div class="user-role"><?= strtoupper($_SESSION['role']) ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
            
            <div class="dropdown" id="dropdown">
                <a href="dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i> User Dashboard
                </a>
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
            <!-- Dashboard -->
            <div class="sidebar-section">
                <div class="sidebar-title">Dashboard</div>
                <a href="admin_dashboard.php?view=dashboard" class="sidebar-link <?= $view == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                </a>
            </div>
            
            <!-- Requests -->
            <div class="sidebar-section">
                <div class="sidebar-title">Requests</div>
                <a href="admin_dashboard.php?view=requests&section=pending" class="sidebar-link <?= ($view == 'requests' && $active_section == 'pending') ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i> Pending Requests
                </a>
                <a href="admin_dashboard.php?view=requests&section=all_requests" class="sidebar-link <?= ($view == 'requests' && $active_section == 'all_requests') ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> Employee Requests
                </a>
                <a href="admin_dashboard.php?view=requests&section=my_requests" class="sidebar-link <?= ($view == 'requests' && $active_section == 'my_requests') ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> My Requests
                </a>
            </div>
            
            <!-- Management -->
            <div class="sidebar-section">
                <div class="sidebar-title">Management</div>
                <a href="admin_dashboard.php?view=users" class="sidebar-link <?= $view == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="admin_dashboard.php?view=inventory" class="sidebar-link <?= $view == 'inventory' ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i> SIM Inventory
                </a>
                <a href="admin_dashboard.php?view=reports" class="sidebar-link <?= $view == 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Request Statistics
                </a>
            </div>
            
            <!-- My New Requests (Only for admin) -->
            <?php if($_SESSION['role'] == 'admin'): ?>
            <div class="sidebar-section">
                <div class="sidebar-title">My New Request</div>
                <a href="dashboard.php?form=activision_e_sim&mode=my_request" class="sidebar-link">
                    <i class="fas fa-sim-card"></i> E-SIM Activation
                </a>
                <a href="dashboard.php?form=sim_replacement&mode=my_request" class="sidebar-link">
                    <i class="fas fa-exchange-alt"></i> SIM Replacement
                </a>
                <a href="dashboard.php?form=temporary_suspension&mode=my_request" class="sidebar-link">
                    <i class="fas fa-pause-circle"></i> Temporary Suspension
                </a>
            </div>
            <?php endif; ?>
        </aside>
        
        <!-- Content -->
        <main class="content">
            <?php if($view == 'dashboard'): ?>
                <!-- Dashboard View -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Admin Dashboard</h1>
                        <p class="page-subtitle">Welcome, <?= htmlspecialchars($user_data['FULL_NAME']) ?> - System Overview</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="refreshStats()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_requests'] ?? 0 ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pending_requests'] ?? 0 ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon today">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-value"><?= $stats['today_requests'] ?? 0 ?></div>
                        <div class="stat-label">Today's Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_users'] ?? 0 ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon sim">
                            <i class="fas fa-sim-card"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_sim'] ?? 0 ?></div>
                        <div class="stat-label">Total SIMs</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon available">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?= $stats['available_sim'] ?? 0 ?></div>
                        <div class="stat-label">Available SIMs</div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                    </div>
                    <div class="card-body">
                        <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <a href="admin_dashboard.php?view=requests&section=pending" class="btn btn-primary">
                                <i class="fas fa-clock"></i> View Pending Requests
                            </a>
                            <a href="admin_dashboard.php?view=inventory" class="btn btn-primary">
                                <i class="fas fa-boxes"></i> Manage Inventory
                            </a>
                            <a href="admin_dashboard.php?view=users" class="btn btn-primary">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt"></i> Go to User Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Pending Requests (Requiring Action)</div>
                        <div class="card-count"><?= $stats['pending_requests'] ?? 0 ?></div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="pendingRequestsTable">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>SIM Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $pending_requests = getPendingRequests();
                                    foreach($pending_requests as $req): 
                                        $reqId = $req['REQ_ID'] ?? $req['REQUEST_ID'] ?? 'N/A';
                                        $empName = $req['FULL_NAME'] ?? $req['EMP_NAME'] ?? 'Unknown';
                                        $empId = $req['EMP_ID'] ?? 'N/A';
                                        $simNo = $req['SIM_NO'] ?? $req['SIMM_NO'] ?? 'N/A';
                                        $date = $req['REQ_DATE'] ?? 'N/A';
                                        $sourceTable = $req['source_table'] ?? '';
                                        $requestType = $req['request_type'] ?? 'Unknown';
                                        $requestTypeKey = array_search($requestType, [
                                            'SIM Issuance' => 'sim_issue',
                                            'New SIM Activation' => 'activision_of_new_sim',
                                            'E-SIM Activation' => 'activision_e_sim',
                                            'SIM Replacement' => 'sim_replacement'
                                        ]);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($reqId) ?></td>
                                        <td><?= htmlspecialchars($empName) ?> (<?= $empId ?>)</td>
                                        <td><?= htmlspecialchars($requestType) ?></td>
                                        <td><?= htmlspecialchars($simNo) ?></td>
                                        <td><?= htmlspecialchars($date) ?></td>
                                        <td><span class="status status-pending">Pending</span></td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <button class="action-btn btn-view btn-small" onclick="viewRequest('<?= $sourceTable ?>', '<?= $reqId ?>')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if(in_array($_SESSION['role'], ['admin', 'HR', 'HOD', 'Head HR', 'CEO'])): ?>
                                                <button class="action-btn btn-approve btn-small" onclick="showActionModal('<?= $sourceTable ?>', '<?= $reqId ?>', '<?= $requestTypeKey ?>', <?= $empId ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="action-btn btn-reject btn-small" onclick="showRejectModal('<?= $sourceTable ?>', '<?= $reqId ?>')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($pending_requests)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-light);">
                                            No pending requests
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif($view == 'requests'): ?>
                <!-- Requests View -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Request Management</h1>
                        <p class="page-subtitle">Manage all SIM requests</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <!-- Export Button -->
                        <button class="btn btn-primary" onclick="exportRequests()">
                            <i class="fas fa-file-export"></i> Export to Excel
                        </button>
                        <button class="btn btn-primary" onclick="loadRequests()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Request Tabs -->
                <div class="request-tabs">
                    <div class="request-tab <?= $active_section == 'pending' ? 'active' : '' ?>" onclick="window.location.href='admin_dashboard.php?view=requests&section=pending'">
                        Pending Requests
                    </div>
                    <div class="request-tab <?= $active_section == 'all_requests' ? 'active' : '' ?>" onclick="window.location.href='admin_dashboard.php?view=requests&section=all_requests'">
                        Employee Requests
                    </div>
                    <div class="request-tab <?= $active_section == 'my_requests' ? 'active' : '' ?>" onclick="window.location.href='admin_dashboard.php?view=requests&section=my_requests'">
                        My Requests
                    </div>
                </div>
                
                <!-- Search Card -->
                <div class="search-card">
                    <div class="search-title">
                        <i class="fas fa-search"></i> Search Requests
                    </div>
                    <form id="requestSearchForm" onsubmit="searchRequests(); return false;">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label>Employee ID</label>
                                <input type="number" class="filter-input" id="searchEmpId" placeholder="Enter Employee ID" value="<?= htmlspecialchars($_GET['emp_id'] ?? '') ?>">
                            </div>
                            <div class="filter-group">
                                <label>Request Type</label>
                                <select class="filter-select" id="searchReqType">
                                    <option value="all" <?= ($_GET['type'] ?? 'all') == 'all' ? 'selected' : '' ?>>All Types</option>
                                    <option value="sim_issue" <?= ($_GET['type'] ?? '') == 'sim_issue' ? 'selected' : '' ?>>SIM Issuance</option>
                                    <option value="activision_of_new_sim" <?= ($_GET['type'] ?? '') == 'activision_of_new_sim' ? 'selected' : '' ?>>New SIM Activation</option>
                                    <option value="deactivision_of_sim" <?= ($_GET['type'] ?? '') == 'deactivision_of_sim' ? 'selected' : '' ?>>SIM Deactivation</option>
                                    <option value="porting_cocp" <?= ($_GET['type'] ?? '') == 'porting_cocp' ? 'selected' : '' ?>>Porting COCP</option>
                                    <option value="activision_e_sim" <?= ($_GET['type'] ?? '') == 'activision_e_sim' ? 'selected' : '' ?>>E-SIM Activation</option>
                                    <option value="sim_replacement" <?= ($_GET['type'] ?? '') == 'sim_replacement' ? 'selected' : '' ?>>SIM Replacement</option>
                                    <option value="temporary_suspension" <?= ($_GET['type'] ?? '') == 'temporary_suspension' ? 'selected' : '' ?>>Temporary Suspension</option>
                                    <option value="air_offical_usage" <?= ($_GET['type'] ?? '') == 'air_offical_usage' ? 'selected' : '' ?>>Intl Roaming (Official)</option>
                                    <option value="air_for_personal_use" <?= ($_GET['type'] ?? '') == 'air_for_personal_use' ? 'selected' : '' ?>>Intl Roaming (Personal)</option>
                                    <option value="isd_activision" <?= ($_GET['type'] ?? '') == 'isd_activision' ? 'selected' : '' ?>>ISD Activation</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Status</label>
                                <select class="filter-select" id="searchStatus">
                                    <option value="all" <?= ($_GET['status'] ?? 'all') == 'all' ? 'selected' : '' ?>>All Status</option>
                                    <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="complete" <?= ($_GET['status'] ?? '') == 'complete' ? 'selected' : '' ?>>Complete</option>
                                    <option value="reject" <?= ($_GET['status'] ?? '') == 'reject' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Date Range</label>
                                <select class="filter-select" id="searchDate">
                                    <option value="all" <?= ($_GET['date'] ?? 'all') == 'all' ? 'selected' : '' ?>>All Time</option>
                                    <option value="latest" <?= ($_GET['date'] ?? '') == 'latest' ? 'selected' : '' ?>>Latest (7 days)</option>
                                    <option value="last_3_months" <?= ($_GET['date'] ?? '') == 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
                                    <option value="3_6_months" <?= ($_GET['date'] ?? '') == '3_6_months' ? 'selected' : '' ?>>3-6 Months</option>
                                    <option value="6_12_months" <?= ($_GET['date'] ?? '') == '6_12_months' ? 'selected' : '' ?>>6-12 Months</option>
                                    <option value="above_12_months" <?= ($_GET['date'] ?? '') == 'above_12_months' ? 'selected' : '' ?>>Above 12 Months</option>
                                </select>
                            </div>
                        </div>
                        <div class="search-actions">
                            <button type="button" class="btn btn-secondary" onclick="resetSearch()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <?= $active_section == 'pending' ? 'Pending Requests' : 
                               ($active_section == 'all_requests' ? 'All Employee Requests' : 'My Requests') ?>
                            <span style="font-size: 12px; color: var(--text-light); margin-left: 10px;">
                                (Showing <?= count($emp_requests) ?> requests)
                            </span>
                        </div>
                        <div class="card-count"><?= count($emp_requests) ?></div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>SIM Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Group requests by status to show pending first
                                    $pending_requests = [];
                                    $other_requests = [];
                                    
                                    foreach($emp_requests as $req) {
                                        if (($req['STATUS'] ?? '') == 'pending') {
                                            $pending_requests[] = $req;
                                        } else {
                                            $other_requests[] = $req;
                                        }
                                    }
                                    
                                    // Merge arrays with pending first
                                    $sorted_requests = array_merge($pending_requests, $other_requests);
                                    
                                    foreach($sorted_requests as $req): 
                                        $reqId = $req['REQ_ID'] ?? $req['REQUEST_ID'] ?? 'N/A';
                                        $empName = $req['FULL_NAME'] ?? $req['EMP_NAME'] ?? 'Unknown';
                                        $empId = $req['EMP_ID'] ?? 'N/A';
                                        $simNo = $req['SIM_NO'] ?? $req['SIMM_NO'] ?? 'N/A';
                                        $date = $req['REQ_DATE'] ?? 'N/A';
                                        $status = $req['STATUS'] ?? 'pending';
                                        $sourceTable = $req['source_table'] ?? '';
                                        $requestType = $req['request_type'] ?? 'Unknown';
                                        $requestTypeKey = array_search($requestType, [
                                            'SIM Issuance' => 'sim_issue',
                                            'New SIM Activation' => 'activision_of_new_sim',
                                            'E-SIM Activation' => 'activision_e_sim',
                                            'SIM Replacement' => 'sim_replacement'
                                        ]);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($reqId) ?></td>
                                        <td><?= htmlspecialchars($empName) ?> (<?= $empId ?>)</td>
                                        <td><?= htmlspecialchars($requestType) ?></td>
                                        <td><?= htmlspecialchars($simNo) ?></td>
                                        <td><?= htmlspecialchars($date) ?></td>
                                        <td>
                                            <?php if($status == 'pending'): ?>
                                                <span class="status status-pending">Pending</span>
                                            <?php elseif($status == 'complete'): ?>
                                                <span class="status status-complete">Complete</span>
                                            <?php elseif($status == 'reject'): ?>
                                                <span class="status status-reject">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <button class="action-btn btn-view btn-small" onclick="viewRequest('<?= $sourceTable ?>', '<?= $reqId ?>')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if($status == 'pending' && $active_section != 'my_requests' && in_array($_SESSION['role'], ['admin', 'HR', 'HOD', 'Head HR', 'CEO'])): ?>
                                                <button class="action-btn btn-approve btn-small" onclick="showActionModal('<?= $sourceTable ?>', '<?= $reqId ?>', '<?= $requestTypeKey ?>', <?= $empId ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="action-btn btn-reject btn-small" onclick="showRejectModal('<?= $sourceTable ?>', '<?= $reqId ?>')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($sorted_requests)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-light);">
                                            No requests found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif($view == 'inventory'): ?>
                <!-- Inventory View -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">SIM Inventory Management</h1>
                        <p class="page-subtitle">Manage SIM cards inventory</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="showImportModal()">
                            <i class="fas fa-file-import"></i> Import Excel
                        </button>
                        <button class="btn btn-primary" onclick="loadInventory()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Search Card -->
                <div class="search-card">
                    <div class="search-title">
                        <i class="fas fa-search"></i> Search Inventory
                    </div>
                    <form id="inventorySearchForm" onsubmit="searchInventory(); return false;">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label>Search</label>
                                <input type="text" class="filter-input" id="inventorySearch" placeholder="SIM Number, Mobile, or Provider" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                            <div class="filter-group">
                                <label>Status</label>
                                <select class="filter-select" id="inventoryStatus">
                                    <option value="">All Status</option>
                                    <option value="AVAILABLE" <?= ($_GET['status'] ?? '') == 'AVAILABLE' ? 'selected' : '' ?>>Available</option>
                                    <option value="ASSIGNED" <?= ($_GET['status'] ?? '') == 'ASSIGNED' ? 'selected' : '' ?>>Assigned</option>
                                    <option value="ACTIVE" <?= ($_GET['status'] ?? '') == 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                    <option value="SUSPENDED" <?= ($_GET['status'] ?? '') == 'SUSPENDED' ? 'selected' : '' ?>>Suspended</option>
                                    <option value="DEACTIVATED" <?= ($_GET['status'] ?? '') == 'DEACTIVATED' ? 'selected' : '' ?>>Deactivated</option>
                                </select>
                            </div>
                        </div>
                        <div class="search-actions">
                            <button type="button" class="btn btn-secondary" onclick="resetInventorySearch()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Inventory Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">SIM Inventory</div>
                        <div class="card-count"><?= count($inventory) ?></div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th>SIM ID</th>
                                        <th>SIM Number</th>
                                        <th>Mobile Number</th>
                                        <th>Service Provider</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($inventory as $item): ?>
                                    <tr>
                                        <td><?= $item['SIM_ID'] ?></td>
                                        <td><?= htmlspecialchars($item['SIM_NUMBER']) ?></td>
                                        <td><?= htmlspecialchars($item['Mobile_Number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($item['Service_Provider']) ?></td>
                                        <td><?= htmlspecialchars($item['Location'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if($item['Status'] == 'AVAILABLE'): ?>
                                                <span class="status status-available">Available</span>
                                            <?php elseif($item['Status'] == 'ASSIGNED'): ?>
                                                <span class="status status-assigned">Assigned</span>
                                            <?php elseif($item['Status'] == 'ACTIVE'): ?>
                                                <span class="status status-active">Active</span>
                                            <?php elseif($item['Status'] == 'SUSPENDED'): ?>
                                                <span class="status status-suspended">Suspended</span>
                                            <?php elseif($item['Status'] == 'DEACTIVATED'): ?>
                                                <span class="status status-deactivated">Deactivated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($item['assigned_name']): ?>
                                                <?= htmlspecialchars($item['assigned_name']) ?> (<?= $item['assigned_designation'] ?>)
                                            <?php else: ?>
                                                Not assigned
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <button class="action-btn btn-view btn-small" onclick="viewInventoryItem(<?= $item['SIM_ID'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if($item['Status'] == 'AVAILABLE' && $_SESSION['role'] == 'admin'): ?>
                                                <button class="action-btn btn-assign btn-small" onclick="assignInventoryItem(<?= $item['SIM_ID'] ?>)">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="action-btn btn-edit btn-small" onclick="editInventoryItem(<?= $item['SIM_ID'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($inventory)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-light);">
                                            No inventory found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif($view == 'users'): ?>
                <!-- Users View -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">Manage system users and roles</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="loadUsers()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Search Card -->
                <div class="search-card">
                    <div class="search-title">
                        <i class="fas fa-search"></i> Search Users
                    </div>
                    <form id="userSearchForm" onsubmit="searchUsers(); return false;">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label>Search</label>
                                <input type="text" class="filter-input" id="userSearch" placeholder="Name, Email, Mobile, or Role" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="search-actions">
                            <button type="button" class="btn btn-secondary" onclick="resetUserSearch()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">System Users</div>
                        <div class="card-count"><?= count($users) ?></div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Designation</th>
                                        <th>Role</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?= $user['ID'] ?></td>
                                        <td><?= htmlspecialchars($user['FULL_NAME']) ?></td>
                                        <td><?= htmlspecialchars($user['EMAIL_ID']) ?></td>
                                        <td><?= htmlspecialchars($user['MOBILE_NO']) ?></td>
                                        <td><?= htmlspecialchars($user['DESGINATION']) ?></td>
                                        <td>
                                            <span class="status status-<?= strtolower($user['ROLE']) ?>">
                                                <?= $user['ROLE'] ?>
                                            </span>
                                        </td>
                                        <td><?= $user['REG_DATE'] ?></td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <?php if(in_array($_SESSION['role'], ['admin', 'HR', 'HOD', 'Head HR', 'CEO'])): ?>
                                                <button class="action-btn btn-edit btn-small" onclick="editUserRole(<?= $user['ID'] ?>, '<?= $user['ROLE'] ?>', '<?= htmlspecialchars($user['FULL_NAME']) ?>')">
                                                    <i class="fas fa-edit"></i> Role
                                                </button>
                                                <?php if($user['ID'] != $user_id && $user['ROLE'] != 'admin' && $_SESSION['role'] == 'admin'): ?>
                                                <button class="action-btn btn-delete btn-small" onclick="deleteUserConfirm(<?= $user['ID'] ?>, '<?= htmlspecialchars($user['FULL_NAME']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($users)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-light);">
                                            No users found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif($view == 'reports'): ?>
                <!-- Reports View -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Request Statistics</h1>
                        <p class="page-subtitle">System request statistics by type</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Request Statistics by Type</div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="requestStatsTable">
                                <thead>
                                    <tr>
                                        <th>Request Type</th>
                                        <th>Total</th>
                                        <th>Pending</th>
                                        <th>Completed</th>
                                        <th>Rejected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $request_stats = getRequestStatsByType(); ?>
                                    <?php foreach($request_stats as $type => $stat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stat['name']) ?></td>
                                        <td><?= $stat['total'] ?></td>
                                        <td><span class="status status-pending"><?= $stat['pending'] ?></span></td>
                                        <td><span class="status status-complete"><?= $stat['complete'] ?></span></td>
                                        <td><span class="status status-reject"><?= $stat['reject'] ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
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
    
    <!-- Approve/Reject Modal -->
    <div class="modal" id="actionModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Update Request Status</div>
                <button class="modal-close" onclick="closeModal('actionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="actionReqId">
                <input type="hidden" id="actionReqTable">
                <input type="hidden" id="actionReqType">
                <input type="hidden" id="actionEmpId">
                
                <div id="assignSimSection" style="display: none;">
                    <h4 style="color: var(--primary); margin-bottom: 15px;">Assign SIM to Employee</h4>
                    <div class="filter-group" style="margin-bottom: 16px;">
                        <label>Select Available SIM</label>
                        <select class="filter-select" id="availableSims">
                            <option value="">Loading available SIMs...</option>
                        </select>
                    </div>
                    <p id="simInfo" style="font-size: 12px; color: var(--text-light); margin-bottom: 20px;"></p>
                </div>
                
                <div class="filter-group" style="margin-bottom: 16px;">
                    <label>Action</label>
                    <select class="filter-select" id="actionStatus" onchange="toggleActionFields()">
                        <option value="complete">Approve</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                
                <div class="filter-group" id="reasonField" style="display: none;">
                    <label>Rejection Reason</label>
                    <textarea class="filter-input" id="actionReason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('actionModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitAction()">Submit</button>
            </div>
        </div>
    </div>
    
    <!-- Import Inventory Modal -->
    <div class="modal" id="importModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Import Inventory from Excel</div>
                <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="import-card">
                    <div class="import-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <h3 style="color: var(--primary); margin-bottom: 10px;">Upload Excel/CSV File</h3>
                    <p style="color: var(--text-light); margin-bottom: 20px;">
                        Upload an Excel or CSV file containing SIM inventory data
                    </p>
                    
                    <div class="file-upload-container" onclick="document.getElementById('inventoryFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div style="margin-bottom: 10px; font-weight: 600;">Click to upload file</div>
                        <div style="font-size: 12px; color: var(--text-lighter);">
                            Supported: .csv, .xls, .xlsx (Max: 10MB)
                        </div>
                        <input type="file" 
                               name="inventory_file" 
                               id="inventoryFile" 
                               class="file-input" 
                               accept=".csv,.xls,.xlsx"
                               onchange="handleFileUpload(this)">
                    </div>
                    
                    <div id="filePreview" class="file-preview" style="display: none;">
                        <!-- File preview will appear here -->
                    </div>
                    
                    <div class="import-instructions">
                        <h4>File Format Requirements:</h4>
                        <ul>
                            <li>First row should contain headers</li>
                            <li>Required columns: SIM_NUMBER, Mobile_Number</li>
                            <li>Optional columns: Service_Provider, Location, Status, emp_id</li>
                            <li>Maximum file size: 10MB</li>
                        </ul>
                        <p><strong>Sample format:</strong></p>
                        <pre style="background: #f1f5f9; padding: 10px; border-radius: 4px; font-size: 12px; overflow-x: auto;">
SIM_NUMBER,Mobile_Number,Service_Provider,Location,Status,emp_id
9876543210,9876543210,VI,Mumbai,AVAILABLE,
9876543211,9876543211,AIRTEL,Delhi,ASSIGNED,101
                        </pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                <button class="btn btn-primary" onclick="importInventory()" id="importBtn" disabled>
                    <i class="fas fa-file-import"></i> Import
                </button>
            </div>
        </div>
    </div>
    
    <!-- Edit Role Modal -->
    <div class="modal" id="editRoleModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Edit User Role</div>
                <button class="modal-close" onclick="closeModal('editRoleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUserId">
                <p id="editUserName" style="margin-bottom: 20px;"></p>
                <div class="filter-group">
                    <label>Select New Role:</label>
                    <select id="newUserRole" class="filter-select">
                        <option value="Employee">Employee</option>
                        <option value="HR">HR</option>
                        <option value="HOD">HOD</option>
                        <option value="Head HR">Head HR</option>
                        <option value="CEO">CEO</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('editRoleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="updateUserRole()">Update Role</button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        // Global variables
        let currentFile = null;
        
        // Initialize page based on view
        $(document).ready(function() {
            <?php if($view == 'dashboard'): ?>
                $('#pendingRequestsTable').DataTable({
                    "pageLength": 10,
                    "order": [[4, "desc"]],
                    "language": {
                        "emptyTable": "No pending requests"
                    }
                });
            <?php elseif($view == 'requests'): ?>
                $('#requestsTable').DataTable({
                    "pageLength": 10,
                    "order": [[4, "desc"]],
                    "language": {
                        "emptyTable": "No requests found"
                    }
                });
            <?php elseif($view == 'inventory'): ?>
                $('#inventoryTable').DataTable({
                    "pageLength": 10,
                    "order": [[0, "desc"]],
                    "language": {
                        "emptyTable": "No inventory found"
                    }
                });
            <?php elseif($view == 'users'): ?>
                $('#usersTable').DataTable({
                    "pageLength": 10,
                    "order": [[0, "desc"]],
                    "language": {
                        "emptyTable": "No users found"
                    }
                });
            <?php elseif($view == 'reports'): ?>
                $('#requestStatsTable').DataTable({
                    "pageLength": 10,
                    "order": [[1, "desc"]],
                    "language": {
                        "emptyTable": "No data available"
                    }
                });
            <?php endif; ?>
        });
        
        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Toggle dropdown
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('show');
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
        
        // Refresh stats (dashboard only)
        function refreshStats() {
            if ('<?= $view ?>' !== 'dashboard') return;
            
            fetch('admin_functions.php?ajax=1&action=get_admin_stats')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Stats refreshed successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
        }
        
        // ========== REQUESTS MANAGEMENT ==========
        
        // Search requests
        function searchRequests() {
            const empId = document.getElementById('searchEmpId').value;
            const type = document.getElementById('searchReqType').value;
            const status = document.getElementById('searchStatus').value;
            const date = document.getElementById('searchDate').value;
            
            const url = new URL('admin_dashboard.php', window.location.origin);
            url.searchParams.set('view', 'requests');
            url.searchParams.set('section', '<?= $active_section ?>');
            if (empId) url.searchParams.set('emp_id', empId);
            if (type !== 'all') url.searchParams.set('type', type);
            if (status !== 'all') url.searchParams.set('status', status);
            if (date !== 'all') url.searchParams.set('date', date);
            
            window.location.href = url.toString();
        }
        
        // Reset search
        function resetSearch() {
            window.location.href = 'admin_dashboard.php?view=requests&section=<?= $active_section ?>';
        }
        
        // Export requests to Excel
        function exportRequests() {
            const empId = document.getElementById('searchEmpId').value;
            const type = document.getElementById('searchReqType').value;
            const status = document.getElementById('searchStatus').value;
            const date = document.getElementById('searchDate').value;
            
            const params = new URLSearchParams();
            params.append('ajax', '1');
            params.append('action', 'export_requests');
            if (empId) params.append('emp_id', empId);
            params.append('type', type);
            params.append('status', status);
            params.append('date', date);
            
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = `admin_functions.php?${params.toString()}`;
            link.download = `requests_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showToast('Export started. File will download shortly.', 'success');
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
        
        // Show request details in modal
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
            
            // Group fields by category
            const employeeFields = ['EMP_NAME', 'FULL_NAME', 'EMAIL_ID', 'EMP_EMAIL', 'MOBILE_NO', 'EMP_MOBILE', 'DESGINATION', 'LOCATION'];
            const requestFields = ['REQ_BY_NAME', 'ACT_BY_NAME', 'REQ_AT', 'ACT_AT', 'STATUS', 'REMARKS', 'REASON', 'REJECT_REASON'];
            const simFields = ['SIM_NO', 'SIMM_NO', 'MOBILE_NO_AL', 'CURRENT', 'TO_SIM', 'DURATION', 'POI_DOC_NO', 'POI_DOC_TYPE', 'RENTAL_PLAN'];
            
            // Function to get display label
            function getDisplayLabel(key) {
                return fieldLabels[key] || key.replace(/_/g, ' ');
            }
            
            // Skip unwanted fields
            const skipFields = ['ID', 'EMP_ID', 'REQ_BY', 'ACT_BY', 'source_table', 'request_type', 'table_name', 'req_at', 'act_at', 'req_by', 'act_by'];
            
            // Request ID
            const reqId = request.REQ_ID || request.REQUEST_ID || request.ID;
            if (reqId) {
                html += `
                    <div style="background: #f0f9ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <div style="font-weight: bold; color: var(--primary);">Request ID: ${reqId}</div>
                    </div>
                `;
            }
            
            // Employee Details
            html += '<h4 style="color: var(--primary); margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">Employee Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && employeeFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div style="display: flex; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                            <div style="width: 160px; font-weight: 600; color: var(--text-light); font-size: 12px;">${getDisplayLabel(key)}:</div>
                            <div style="flex: 1; font-size: 13px; color: var(--text);">${value}</div>
                        </div>
                    `;
                }
            }
            
            // Request Details
            html += '<h4 style="color: var(--primary); margin-top: 20px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">Request Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && requestFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div style="display: flex; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                            <div style="width: 160px; font-weight: 600; color: var(--text-light); font-size: 12px;">${getDisplayLabel(key)}:</div>
                            <div style="flex: 1; font-size: 13px; color: var(--text);">${value}</div>
                        </div>
                    `;
                }
            }
            
            // SIM Details
            html += '<h4 style="color: var(--primary); margin-top: 20px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--primary);">SIM Details</h4>';
            for (const [key, value] of Object.entries(request)) {
                if (value && simFields.includes(key) && !skipFields.includes(key)) {
                    html += `
                        <div style="display: flex; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                            <div style="width: 160px; font-weight: 600; color: var(--text-light); font-size: 12px;">${getDisplayLabel(key)}:</div>
                            <div style="flex: 1; font-size: 13px; color: var(--text);">${value}</div>
                        </div>
                    `;
                }
            }
            
            modalBody.innerHTML = html;
            showModal('requestModal');
        }
        
        // Show action modal (approve/reject)
        function showActionModal(table, id, requestType, empId) {
            document.getElementById('actionReqId').value = id;
            document.getElementById('actionReqTable').value = table;
            document.getElementById('actionReqType').value = requestType;
            document.getElementById('actionEmpId').value = empId;
            document.getElementById('actionStatus').value = 'complete';
            document.getElementById('actionReason').value = '';
            
            // Check if this is a SIM assignment request (only for admin)
            const simAssignmentTypes = ['sim_issue', 'activision_of_new_sim', 'activision_e_sim', 'sim_replacement'];
            if (simAssignmentTypes.includes(requestType) && '<?= $_SESSION['role'] ?>' === 'admin') {
                // Show SIM assignment section
                document.getElementById('assignSimSection').style.display = 'block';
                loadAvailableSims();
            } else {
                document.getElementById('assignSimSection').style.display = 'none';
            }
            
            toggleActionFields();
            showModal('actionModal');
        }
        
        // Show reject modal
        function showRejectModal(table, id) {
            document.getElementById('actionReqId').value = id;
            document.getElementById('actionReqTable').value = table;
            document.getElementById('actionStatus').value = 'reject';
            document.getElementById('actionReason').value = '';
            document.getElementById('assignSimSection').style.display = 'none';
            toggleActionFields();
            showModal('actionModal');
        }
        
        // Toggle action fields based on status
        function toggleActionFields() {
            const status = document.getElementById('actionStatus').value;
            const reasonField = document.getElementById('reasonField');
            const assignSimSection = document.getElementById('assignSimSection');
            
            if (status === 'reject') {
                reasonField.style.display = 'block';
                assignSimSection.style.display = 'none';
            } else {
                reasonField.style.display = 'none';
                // Show assign SIM section only for SIM assignment requests and admin role
                const requestType = document.getElementById('actionReqType').value;
                const simAssignmentTypes = ['sim_issue', 'activision_of_new_sim', 'activision_e_sim', 'sim_replacement'];
                if (simAssignmentTypes.includes(requestType) && '<?= $_SESSION['role'] ?>' === 'admin') {
                    assignSimSection.style.display = 'block';
                }
            }
        }
        
        // Load available SIMs for assignment
        function loadAvailableSims() {
            fetch('admin_functions.php?ajax=1&action=get_available_sims')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('availableSims');
                        select.innerHTML = '<option value="">Select SIM to assign</option>';
                        
                        data.sims.forEach(sim => {
                            const option = document.createElement('option');
                            option.value = sim.id;
                            option.textContent = `${sim.sim_number} (${sim.mobile_number}) - ${sim.provider} - ${sim.location || 'No location'}`;
                            option.dataset.info = `Provider: ${sim.provider} | Location: ${sim.location || 'Not specified'}`;
                            select.appendChild(option);
                        });
                        
                        // Add change listener to show SIM info
                        select.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            document.getElementById('simInfo').textContent = selectedOption.dataset.info || '';
                        });
                    }
                });
        }
        
        // Submit action (approve/reject)
        function submitAction() {
            const table = document.getElementById('actionReqTable').value;
            const id = document.getElementById('actionReqId').value;
            const status = document.getElementById('actionStatus').value;
            const reason = document.getElementById('actionReason').value;
            const requestType = document.getElementById('actionReqType').value;
            const empId = document.getElementById('actionEmpId').value;
            const simId = document.getElementById('availableSims').value;
            
            if (status === 'complete' && requestType && ['sim_issue', 'activision_of_new_sim', 'activision_e_sim', 'sim_replacement'].includes(requestType) && '<?= $_SESSION['role'] ?>' === 'admin') {
                // SIM assignment required (only for admin)
                if (!simId) {
                    showToast('Please select a SIM to assign', 'error');
                    return;
                }
                
                // Assign SIM and approve request
                const formData = new FormData();
                formData.append('sim_id', simId);
                formData.append('employee_id', empId);
                formData.append('request_id', id);
                formData.append('request_type', requestType);
                
                fetch('admin_functions.php?ajax=1&action=assign_sim', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Request approved and SIM assigned: ${data.sim_number}`, 'success');
                        closeModal('actionModal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                });
                
            } else {
                // Standard approve/reject
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
                        showToast(`Request ${status === 'complete' ? 'approved' : 'rejected'} successfully`, 'success');
                        closeModal('actionModal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                });
            }
        }
        
        // ========== INVENTORY MANAGEMENT ==========
        
        // Search inventory
        function searchInventory() {
            const search = document.getElementById('inventorySearch').value;
            const status = document.getElementById('inventoryStatus').value;
            
            const url = new URL('admin_dashboard.php', window.location.origin);
            url.searchParams.set('view', 'inventory');
            if (search) url.searchParams.set('search', search);
            if (status) url.searchParams.set('status', status);
            
            window.location.href = url.toString();
        }
        
        // Reset inventory search
        function resetInventorySearch() {
            window.location.href = 'admin_dashboard.php?view=inventory';
        }
        
        // Show import modal
        function showImportModal() {
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('importBtn').disabled = true;
            currentFile = null;
            showModal('importModal');
        }
        
        // Handle file upload
        function handleFileUpload(input) {
            const file = input.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showToast('File size exceeds 10MB limit', 'error');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['csv', 'xls', 'xlsx'];
                const fileExt = file.name.split('.').pop().toLowerCase();
                
                if (!validTypes.includes(fileExt)) {
                    showToast('Invalid file type. Please upload CSV or Excel file', 'error');
                    input.value = '';
                    return;
                }
                
                // Format file size
                const fileSize = formatFileSize(file.size);
                
                // Show preview
                preview.innerHTML = `
                    <div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-file-excel" style="font-size: 24px; color: var(--success);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--text);">${file.name}</div>
                                    <div style="font-size: 12px; color: var(--text-light);">${fileSize}</div>
                                </div>
                            </div>
                            <button class="action-btn btn-delete btn-small" onclick="removeFile()">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
                preview.style.display = 'block';
                
                // Enable import button
                document.getElementById('importBtn').disabled = false;
                currentFile = file;
            }
        }
        
        // Remove file
        function removeFile() {
            document.getElementById('inventoryFile').value = '';
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('importBtn').disabled = true;
            currentFile = null;
        }
        
        // Import inventory
        function importInventory() {
            if (!currentFile) {
                showToast('Please select a file first', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('inventory_file', currentFile);
            
            // Show loading state
            const importBtn = document.getElementById('importBtn');
            const originalHTML = importBtn.innerHTML;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
            importBtn.disabled = true;
            
            fetch('admin_functions.php?ajax=1&action=import_inventory', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(`Inventory imported successfully: ${data.imported} imported, ${data.skipped} skipped`, 'success');
                    closeModal('importModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network error: ' + error.message, 'error');
            })
            .finally(() => {
                importBtn.innerHTML = originalHTML;
                importBtn.disabled = false;
            });
        }
        
        // View inventory item
        function viewInventoryItem(id) {
            // Implement view inventory item
            showToast('View inventory item: ' + id, 'info');
        }
        
        // Assign inventory item
        function assignInventoryItem(id) {
            // Implement assign inventory item
            showToast('Assign inventory item: ' + id, 'info');
        }
        
        // Edit inventory item
        function editInventoryItem(id) {
            // Implement edit inventory item
            showToast('Edit inventory item: ' + id, 'info');
        }
        
        // ========== USER MANAGEMENT ==========
        
        // Search users
        function searchUsers() {
            const search = document.getElementById('userSearch').value;
            
            const url = new URL('admin_dashboard.php', window.location.origin);
            url.searchParams.set('view', 'users');
            if (search) url.searchParams.set('search', search);
            
            window.location.href = url.toString();
        }
        
        // Reset user search
        function resetUserSearch() {
            window.location.href = 'admin_dashboard.php?view=users';
        }
        
        // Edit user role
        function editUserRole(userId, currentRole, userName) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').textContent = `Editing role for: ${userName}`;
            document.getElementById('newUserRole').value = currentRole;
            showModal('editRoleModal');
        }
        
        // Update user role
        function updateUserRole() {
            const userId = document.getElementById('editUserId').value;
            const newRole = document.getElementById('newUserRole').value;
            
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('new_role', newRole);
            
            fetch('admin_functions.php?ajax=1&action=update_user_role', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('User role updated successfully', 'success');
                    closeModal('editRoleModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
        }
        
        // Confirm user deletion
        function deleteUserConfirm(userId, userName) {
            if (confirm(`Are you sure you want to delete user: ${userName}?\n\nThis action cannot be undone!`)) {
                deleteUser(userId);
            }
        }
        
        // Delete user
        function deleteUser(userId) {
            const formData = new FormData();
            formData.append('user_id', userId);
            
            fetch('admin_functions.php?ajax=1&action=delete_user', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('User deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
        }
        
        // ========== HELPER FUNCTIONS ==========
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
            toast.className = 'toast-message';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 3000;
                transform: translateX(150%);
                transition: transform 0.3s ease;
                max-width: 350px;
                border-left: 4px solid;
                border-left-color: ${type === 'success' ? 'var(--success)' : 
                                 type === 'error' ? 'var(--danger)' : 
                                 type === 'warning' ? 'var(--warning)' : 'var(--info)'};
                background: ${type === 'success' ? '#d1fae5' : 
                            type === 'error' ? '#fee2e2' : 
                            type === 'warning' ? '#fef3c7' : '#dbeafe'};
                color: ${type === 'success' ? '#065f46' : 
                        type === 'error' ? '#991b1b' : 
                        type === 'warning' ? '#92400e' : '#1e40af'};
            `;
            
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
                toast.style.transform = 'translateX(0)';
            }, 10);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(150%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 5000);
        }
        
        // Auto-hide sidebar on mobile when clicking content
        document.querySelector('.content').addEventListener('click', function() {
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
    </script>
</body>
</html>