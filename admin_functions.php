<?php
// admin_functions.php - Admin-specific functions and pages

// Include required files
require_once 'config.php';
require_once 'backend.php'; // Include backend for request functions

// Check if user is admin
// Check if user is admin
function checkAdminAuth() {
    checkAuth(); // First check if user is logged in
    
    $allowed_roles = ['admin', 'HR', 'HOD', 'Head HR', 'CEO'];
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php");
        exit();
    }
}

// Get database connection
function getDbConnection() {
    global $conn;
    return $conn;
}

// Get admin statistics
function getAdminStats() {
    global $conn;
    
    $stats = [];
    
    // Total requests count
    $query = "SELECT 
                (SELECT COUNT(*) FROM sim_issue) +
                (SELECT COUNT(*) FROM activision_of_new_sim) +
                (SELECT COUNT(*) FROM deactivision_of_sim) +
                (SELECT COUNT(*) FROM porting_cocp) +
                (SELECT COUNT(*) FROM activision_e_sim) +
                (SELECT COUNT(*) FROM sim_replacement) +
                (SELECT COUNT(*) FROM temporary_suspension) +
                (SELECT COUNT(*) FROM air_offical_usage) +
                (SELECT COUNT(*) FROM air_for_personal_use) +
                (SELECT COUNT(*) FROM isd_activision) as total_requests";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_requests'] = $row['total_requests'] ?? 0;
    }
    
    // Pending requests count
    $query = "SELECT 
                (SELECT COUNT(*) FROM sim_issue WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM activision_of_new_sim WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM deactivision_of_sim WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM porting_cocp WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM activision_e_sim WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM sim_replacement WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM temporary_suspension WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM air_offical_usage WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM air_for_personal_use WHERE STATUS = 'pending') +
                (SELECT COUNT(*) FROM isd_activision WHERE STATUS = 'pending') as pending_requests";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['pending_requests'] = $row['pending_requests'] ?? 0;
    }
    
    // Get today's requests
    $today = date('Y-m-d');
    $query = "SELECT 
                (SELECT COUNT(*) FROM sim_issue WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM activision_of_new_sim WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM deactivision_of_sim WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM porting_cocp WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM activision_e_sim WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM sim_replacement WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM temporary_suspension WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM air_offical_usage WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM air_for_personal_use WHERE DATE(req_at) = '$today') +
                (SELECT COUNT(*) FROM isd_activision WHERE DATE(req_at) = '$today') as today_requests";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['today_requests'] = $row['today_requests'] ?? 0;
    }
    
    // Get total users
    $query = "SELECT COUNT(*) as total_users FROM master_employee";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_users'] = $row['total_users'] ?? 0;
    }
    
    // Get inventory stats from sim_inventory table
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'sim_inventory'");
    if (mysqli_num_rows($table_check) > 0) {
        $query = "SELECT 
                    COUNT(*) as total_sim,
                    SUM(CASE WHEN Status = 'AVAILABLE' THEN 1 ELSE 0 END) as available_sim,
                    SUM(CASE WHEN Status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned_sim,
                    SUM(CASE WHEN Status = 'ACTIVE' THEN 1 ELSE 0 END) as active_sim,
                    SUM(CASE WHEN Status = 'SUSPENDED' THEN 1 ELSE 0 END) as suspended_sim,
                    SUM(CASE WHEN Status = 'DEACTIVATED' THEN 1 ELSE 0 END) as deactivated_sim
                  FROM sim_inventory";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['total_sim'] = $row['total_sim'] ?? 0;
            $stats['available_sim'] = $row['available_sim'] ?? 0;
            $stats['assigned_sim'] = $row['assigned_sim'] ?? 0;
            $stats['active_sim'] = $row['active_sim'] ?? 0;
            $stats['suspended_sim'] = $row['suspended_sim'] ?? 0;
            $stats['deactivated_sim'] = $row['deactivated_sim'] ?? 0;
        }
    } else {
        // If sim_inventory table doesn't exist, set default values
        $stats['total_sim'] = 0;
        $stats['available_sim'] = 0;
        $stats['assigned_sim'] = 0;
        $stats['active_sim'] = 0;
        $stats['suspended_sim'] = 0;
        $stats['deactivated_sim'] = 0;
    }
    
    return $stats;
}

// Get all users for admin management
function getAllUsers($search = '') {
    global $conn;
    
    $where = '';
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $where = " WHERE FULL_NAME LIKE '%$search%' OR 
                          EMAIL_ID LIKE '%$search%' OR 
                          MOBILE_NO LIKE '%$search%' OR 
                          ROLE LIKE '%$search%'";
    }
    
    $query = "SELECT ID, FULL_NAME, EMAIL_ID, MOBILE_NO, DESGINATION, LOCATION, ROLE, 
                     DATE_FORMAT(REG_AT, '%d-%m-%Y') as REG_DATE
              FROM master_employee 
              $where 
              ORDER BY ID DESC";
    
    $result = mysqli_query($conn, $query);
    $users = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    return $users;
}

// Get request statistics by type
function getRequestStatsByType() {
    global $conn;
    
    $stats = [];
    
    $tables = [
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
    
    foreach ($tables as $table => $name) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN STATUS = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN STATUS = 'complete' THEN 1 ELSE 0 END) as complete,
                    SUM(CASE WHEN STATUS = 'reject' THEN 1 ELSE 0 END) as reject
                  FROM $table";
        
        $result = mysqli_query($conn, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $stats[$table] = [
                'name' => $name,
                'total' => $row['total'] ?? 0,
                'pending' => $row['pending'] ?? 0,
                'complete' => $row['complete'] ?? 0,
                'reject' => $row['reject'] ?? 0
            ];
        }
    }
    
    return $stats;
}

// Get all employee requests with search filters (shows pending first)
function getAllEmployeeRequests($emp_id = '', $type = 'all', $status = 'all', $date_range = 'all') {
    global $conn;
    
    // Array to store all requests
    $all_requests = [];
    
    // Define request tables with their display names
    $tables = [
        'sim_issue' => [
            'name' => 'SIM Issuance',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'req_at'
        ],
        'activision_of_new_sim' => [
            'name' => 'New SIM Activation',
            'id_field' => 'REQUEST_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'req_at'
        ],
        'deactivision_of_sim' => [
            'name' => 'SIM Deactivation',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'porting_cocp' => [
            'name' => 'Porting COCP',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'activision_e_sim' => [
            'name' => 'E-SIM Activation',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIMM_NO',
            'date_field' => 'REQ_AT'
        ],
        'sim_replacement' => [
            'name' => 'SIM Replacement',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'temporary_suspension' => [
            'name' => 'Temporary Suspension',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'air_offical_usage' => [
            'name' => 'Intl Roaming (Official)',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'air_for_personal_use' => [
            'name' => 'Intl Roaming (Personal)',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ],
        'isd_activision' => [
            'name' => 'ISD Activation',
            'id_field' => 'REQ_ID',
            'sim_field' => 'SIM_NO',
            'date_field' => 'REQ_AT'
        ]
    ];
    
    // Filter which tables to query
    $tables_to_query = [];
    if ($type == 'all') {
        $tables_to_query = $tables;
    } elseif (isset($tables[$type])) {
        $tables_to_query[$type] = $tables[$type];
    }
    
    foreach ($tables_to_query as $table_name => $table_info) {
        $where_conditions = [];
        
        // Add employee ID filter
        if ($emp_id) {
            $emp_id_escaped = mysqli_real_escape_string($conn, $emp_id);
            $where_conditions[] = "r.EMP_ID = '$emp_id_escaped'";
        }
        
        // Add status filter
        if ($status != 'all') {
            $status_escaped = mysqli_real_escape_string($conn, $status);
            $where_conditions[] = "r.STATUS = '$status_escaped'";
        }
        
        // Add date range filter
        if ($date_range != 'all') {
            $date_field = $table_info['date_field'];
            $date_condition = "";
            switch($date_range) {
                case 'latest':
                    $date_condition = "DATE(r.$date_field) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'last_3_months':
                    $date_condition = "DATE(r.$date_field) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                    break;
                case '3_6_months':
                    $date_condition = "DATE(r.$date_field) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                    break;
                case '6_12_months':
                    $date_condition = "DATE(r.$date_field) BETWEEN DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
                    break;
                case 'above_12_months':
                    $date_condition = "DATE(r.$date_field) < DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
                    break;
            }
            if ($date_condition) {
                $where_conditions[] = $date_condition;
            }
        }
        
        $where_clause = "";
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }
        
        // Determine field names based on table structure
        $req_by_field = 'REQ_BY';
        $act_by_field = 'ACT_BY';
        $req_at_field = 'REQ_AT';
        $act_at_field = 'ACT_AT';
        
        if ($table_name == 'sim_issue' || $table_name == 'activision_of_new_sim') {
            $req_by_field = 'req_by';
            $act_by_field = 'act_by';
            $req_at_field = 'req_at';
            $act_at_field = 'act_at';
        }
        
        // Build query - Order by STATUS to show pending first
        $query = "SELECT r.*, 
                         e.FULL_NAME, 
                         e.EMAIL_ID, 
                         e.MOBILE_NO as EMP_MOBILE,
                         re.FULL_NAME as REQ_BY_NAME,
                         ac.FULL_NAME as ACT_BY_NAME,
                         DATE_FORMAT(r.$req_at_field, '%d-%m-%Y %H:%i') as REQ_DATE,
                         DATE_FORMAT(r.$act_at_field, '%d-%m-%Y %H:%i') as ACT_DATE,
                         '$table_name' as source_table,
                         '{$table_info['name']}' as request_type
                  FROM $table_name r
                  LEFT JOIN master_employee e ON r.EMP_ID = e.ID
                  LEFT JOIN master_employee re ON r.$req_by_field = re.ID
                  LEFT JOIN master_employee ac ON r.$act_by_field = ac.ID
                  $where_clause
                  ORDER BY FIELD(r.STATUS, 'pending', 'complete', 'reject'), r.$req_at_field DESC";
        
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $all_requests[] = $row;
            }
        }
    }
    
    return $all_requests;
}

// Get pending requests only
function getPendingRequests() {
    return getAllEmployeeRequests('', 'all', 'pending', 'all');
}

// Export requests to Excel
function exportRequestsToExcel($emp_id = '', $type = 'all', $status = 'all', $date_range = 'all') {
    global $conn;
    
    $requests = getAllEmployeeRequests($emp_id, $type, $status, $date_range);
    
    // Create CSV content
    $csv = "Request ID,Employee Name,Employee ID,Request Type,SIM Number,Status,Request Date,Requested By\n";
    
    foreach ($requests as $req) {
        $reqId = $req['REQ_ID'] ?? $req['REQUEST_ID'] ?? 'N/A';
        $empName = $req['FULL_NAME'] ?? 'Unknown';
        $empId = $req['EMP_ID'] ?? 'N/A';
        $requestType = $req['request_type'] ?? 'Unknown';
        $simNo = $req['SIM_NO'] ?? $req['SIMM_NO'] ?? 'N/A';
        $statusText = $req['STATUS'] ?? 'pending';
        $reqDate = $req['REQ_DATE'] ?? 'N/A';
        $reqByName = $req['REQ_BY_NAME'] ?? 'Unknown';
        
        // Escape CSV values
        $csv .= '"' . str_replace('"', '""', $reqId) . '",';
        $csv .= '"' . str_replace('"', '""', $empName) . '",';
        $csv .= '"' . str_replace('"', '""', $empId) . '",';
        $csv .= '"' . str_replace('"', '""', $requestType) . '",';
        $csv .= '"' . str_replace('"', '""', $simNo) . '",';
        $csv .= '"' . str_replace('"', '""', $statusText) . '",';
        $csv .= '"' . str_replace('"', '""', $reqDate) . '",';
        $csv .= '"' . str_replace('"', '""', $reqByName) . '"';
        $csv .= "\n";
    }
    
    return $csv;
}

// Update user role
function updateUserRole($user_id, $new_role) {
    global $conn;
    
    $user_id = intval($user_id);
    $new_role = mysqli_real_escape_string($conn, $new_role);
    
    $query = "UPDATE master_employee SET ROLE = '$new_role' WHERE ID = $user_id";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'message' => 'User role updated successfully'];
    }
    
    return ['success' => false, 'message' => mysqli_error($conn)];
}

// Delete user (admin only)
function deleteUser($user_id) {
    global $conn;
    
    $user_id = intval($user_id);
    
    // Check if user has any pending requests
    $check_query = "SELECT 
                    (SELECT COUNT(*) FROM sim_issue WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM activision_of_new_sim WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM deactivision_of_sim WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM porting_cocp WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM activision_e_sim WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM sim_replacement WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM temporary_suspension WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM air_offical_usage WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM air_for_personal_use WHERE EMP_ID = $user_id AND STATUS = 'pending') +
                    (SELECT COUNT(*) FROM isd_activision WHERE EMP_ID = $user_id AND STATUS = 'pending') as pending_count";
    
    $result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['pending_count'] > 0) {
        return ['success' => false, 'message' => 'Cannot delete user with pending requests'];
    }
    
    $query = "DELETE FROM master_employee WHERE ID = $user_id";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'message' => 'User deleted successfully'];
    }
    
    return ['success' => false, 'message' => mysqli_error($conn)];
}

// Get all inventory from sim_inventory table
function getAllInventory($search = '', $status = '') {
    global $conn;
    
    $where = "WHERE 1=1";
    
    if ($search) {
        $search = mysqli_real_escape_string($conn, $search);
        $where .= " AND (SIM_NUMBER LIKE '%$search%' OR 
                         Mobile_Number LIKE '%$search%' OR 
                         Service_Provider LIKE '%$search%')";
    }
    
    if ($status && in_array($status, ['AVAILABLE', 'ASSIGNED', 'ACTIVE', 'SUSPENDED', 'DEACTIVATED'])) {
        $status = mysqli_real_escape_string($conn, $status);
        $where .= " AND Status = '$status'";
    }
    
    $query = "SELECT i.*, 
                     e.FULL_NAME as assigned_name,
                     e.DESGINATION as assigned_designation
              FROM sim_inventory i
              LEFT JOIN master_employee e ON i.emp_id = e.ID
              $where 
              ORDER BY SIM_ID DESC";
    
    $result = mysqli_query($conn, $query);
    $inventory = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $inventory[] = $row;
        }
    }
    
    return $inventory;
}

// Import inventory from Excel into sim_inventory table
function importInventoryFromExcel($file_path) {
    global $conn;
    
    // Check if file exists
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($file_ext == 'csv') {
        return importInventoryFromCSV($file_path);
    } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
        return importInventoryFromExcelFile($file_path);
    } else {
        return ['success' => false, 'message' => 'Unsupported file format'];
    }
}

// Import from CSV
function importInventoryFromCSV($file_path) {
    global $conn;
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $row = 1;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip header row
            if ($row == 1) {
                $row++;
                continue;
            }
            
            // Check if we have enough columns
            if (count($data) >= 2) {
                $sim_number = mysqli_real_escape_string($conn, $data[0]);
                $mobile_number = isset($data[1]) ? mysqli_real_escape_string($conn, $data[1]) : '';
                $service_provider = isset($data[2]) ? mysqli_real_escape_string($conn, $data[2]) : 'VI';
                $location = isset($data[3]) ? mysqli_real_escape_string($conn, $data[3]) : '';
                $status = isset($data[4]) ? mysqli_real_escape_string($conn, strtoupper($data[4])) : 'AVAILABLE';
                $emp_id = isset($data[5]) ? intval($data[5]) : NULL;
                
                // Validate status
                $valid_statuses = ['AVAILABLE', 'ASSIGNED', 'ACTIVE', 'SUSPENDED', 'DEACTIVATED'];
                if (!in_array($status, $valid_statuses)) {
                    $status = 'AVAILABLE';
                }
                
                // Validate provider
                $valid_providers = ['VI', 'AIRTEL', 'OTHERS'];
                if (!in_array($service_provider, $valid_providers)) {
                    $service_provider = 'VI';
                }
                
                // Check if SIM already exists
                $check_query = "SELECT SIM_ID FROM sim_inventory WHERE SIM_NUMBER = '$sim_number'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) == 0) {
                    $query = "INSERT INTO sim_inventory (SIM_NUMBER, Mobile_Number, Service_Provider, Location, Status, emp_id) 
                              VALUES ('$sim_number', '$mobile_number', '$service_provider', '$location', '$status', " . ($emp_id ? $emp_id : 'NULL') . ")";
                    
                    if (mysqli_query($conn, $query)) {
                        $imported++;
                    } else {
                        $errors[] = "Row $row: " . mysqli_error($conn);
                        $skipped++;
                    }
                } else {
                    $skipped++;
                    $errors[] = "Row $row: SIM already exists";
                }
            } else {
                $skipped++;
                $errors[] = "Row $row: Insufficient data";
            }
            
            $row++;
        }
        fclose($handle);
    }
    
    return [
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

// Import from Excel file
function importInventoryFromExcelFile($file_path) {
    global $conn;
    
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Try to include it
        $vendor_path = __DIR__ . '/vendor/autoload.php';
        if (file_exists($vendor_path)) {
            require_once $vendor_path;
        } else {
            return ['success' => false, 'message' => 'PhpSpreadsheet library not found. Please install via composer: composer require phpoffice/phpspreadsheet'];
        }
    }
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            // Skip header row
            if ($index == 0) continue;
            
            // Check if we have enough data (at least SIM number)
            if (isset($row[0])) {
                $sim_number = mysqli_real_escape_string($conn, $row[0]);
                $mobile_number = isset($row[1]) ? mysqli_real_escape_string($conn, $row[1]) : '';
                $service_provider = isset($row[2]) ? mysqli_real_escape_string($conn, $row[2]) : 'VI';
                $location = isset($row[3]) ? mysqli_real_escape_string($conn, $row[3]) : '';
                $status = isset($row[4]) ? mysqli_real_escape_string($conn, strtoupper($row[4])) : 'AVAILABLE';
                $emp_id = isset($row[5]) ? intval($row[5]) : NULL;
                
                // Validate status
                $valid_statuses = ['AVAILABLE', 'ASSIGNED', 'ACTIVE', 'SUSPENDED', 'DEACTIVATED'];
                if (!in_array($status, $valid_statuses)) {
                    $status = 'AVAILABLE';
                }
                
                // Validate provider
                $valid_providers = ['VI', 'AIRTEL', 'OTHERS'];
                if (!in_array($service_provider, $valid_providers)) {
                    $service_provider = 'VI';
                }
                
                // Check if SIM already exists
                $check_query = "SELECT SIM_ID FROM sim_inventory WHERE SIM_NUMBER = '$sim_number'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) == 0) {
                    $query = "INSERT INTO sim_inventory (SIM_NUMBER, Mobile_Number, Service_Provider, Location, Status, emp_id) 
                              VALUES ('$sim_number', '$mobile_number', '$service_provider', '$location', '$status', " . ($emp_id ? $emp_id : 'NULL') . ")";
                    
                    if (mysqli_query($conn, $query)) {
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": " . mysqli_error($conn);
                        $skipped++;
                    }
                } else {
                    $skipped++;
                    $errors[] = "Row " . ($index + 1) . ": SIM already exists";
                }
            } else {
                $skipped++;
                $errors[] = "Row " . ($index + 1) . ": Insufficient data";
            }
        }
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error reading Excel file: ' . $e->getMessage()];
    }
}

// Get available SIMs for assignment
function getAvailableSims() {
    global $conn;
    
    $query = "SELECT SIM_ID, SIM_NUMBER, Mobile_Number, Service_Provider, Location 
              FROM sim_inventory 
              WHERE Status = 'AVAILABLE' 
              ORDER BY SIM_ID DESC";
    
    $result = mysqli_query($conn, $query);
    $sims = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $sims[] = [
                'id' => $row['SIM_ID'],
                'sim_number' => $row['SIM_NUMBER'],
                'mobile_number' => $row['Mobile_Number'],
                'provider' => $row['Service_Provider'],
                'location' => $row['Location']
            ];
        }
    }
    
    return $sims;
}

// Assign SIM to employee (for SIM Issuance requests)
function assignSimToEmployee($sim_id, $employee_id, $request_id, $request_type) {
    global $conn;
    
    $sim_id = intval($sim_id);
    $employee_id = intval($employee_id);
    
    // Get SIM details
    $sim_query = "SELECT * FROM sim_inventory WHERE SIM_ID = $sim_id AND Status = 'AVAILABLE'";
    $sim_result = mysqli_query($conn, $sim_query);
    
    if (mysqli_num_rows($sim_result) == 0) {
        return ['success' => false, 'message' => 'SIM not available or already assigned'];
    }
    
    $sim_data = mysqli_fetch_assoc($sim_result);
    
    // Update SIM status to ASSIGNED
    $update_query = "UPDATE sim_inventory SET 
                     Status = 'ASSIGNED',
                     emp_id = $employee_id
                     WHERE SIM_ID = $sim_id";
    
    if (!mysqli_query($conn, $update_query)) {
        return ['success' => false, 'message' => 'Failed to assign SIM: ' . mysqli_error($conn)];
    }
    
    // Update the request status and add SIM details
    $table_name = '';
    $sim_field = 'SIM_NO';
    $mobile_field = 'MOBILE_NO';
    
    switch($request_type) {
        case 'sim_issue': 
            $table_name = 'sim_issue';
            $mobile_field = 'MOBILE_NO_AL';
            break;
        case 'activision_of_new_sim': 
            $table_name = 'activision_of_new_sim';
            break;
        case 'activision_e_sim': 
            $table_name = 'activision_e_sim';
            $sim_field = 'SIMM_NO';
            $mobile_field = 'SIMM_NO';
            break;
        case 'sim_replacement': 
            $table_name = 'sim_replacement';
            break;
        default: 
            $table_name = 'sim_issue';
    }
    
    // Determine ID field
    $id_field = 'REQ_ID';
    if ($table_name == 'activision_of_new_sim') {
        $id_field = 'REQUEST_ID';
    }
    
    // Determine action by field
    $act_by_field = 'ACT_BY';
    $act_at_field = 'ACT_AT';
    if ($table_name == 'sim_issue' || $table_name == 'activision_of_new_sim') {
        $act_by_field = 'act_by';
        $act_at_field = 'act_at';
    }
    
    // Update request with SIM details
    $update_request = "UPDATE $table_name SET 
                       STATUS = 'complete',
                       $sim_field = '" . $sim_data['SIM_NUMBER'] . "',
                       $mobile_field = '" . $sim_data['Mobile_Number'] . "',
                       $act_by_field = " . $_SESSION['user_id'] . ",
                       $act_at_field = NOW()
                       WHERE $id_field = '" . mysqli_real_escape_string($conn, $request_id) . "'";
    
    if (!mysqli_query($conn, $update_request)) {
        // Rollback SIM assignment
        $rollback = "UPDATE sim_inventory SET 
                     Status = 'AVAILABLE',
                     emp_id = NULL
                     WHERE SIM_ID = $sim_id";
        mysqli_query($conn, $rollback);
        
        return ['success' => false, 'message' => 'Failed to update request: ' . mysqli_error($conn)];
    }
    
    return [
        'success' => true, 
        'message' => 'SIM assigned successfully',
        'sim_number' => $sim_data['SIM_NUMBER'],
        'mobile_number' => $sim_data['Mobile_Number']
    ];
}

// Handle AJAX requests for admin
if (isset($_GET['ajax']) && isset($_GET['action'])) {
    header('Content-Type: application/json');
    checkAdminAuth();
    
    switch($_GET['action']) {
        case 'get_admin_stats':
            echo json_encode(['success' => true, 'stats' => getAdminStats()]);
            break;
            
        case 'get_all_users':
            $search = $_GET['search'] ?? '';
            echo json_encode(['success' => true, 'users' => getAllUsers($search)]);
            break;
            
        case 'update_user_role':
            $user_id = $_POST['user_id'] ?? 0;
            $new_role = $_POST['new_role'] ?? '';
            echo json_encode(updateUserRole($user_id, $new_role));
            break;
            
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? 0;
            echo json_encode(deleteUser($user_id));
            break;
            
        case 'get_request_stats_by_type':
            echo json_encode(['success' => true, 'stats' => getRequestStatsByType()]);
            break;
            
        case 'get_employee_requests':
            $emp_id = $_GET['emp_id'] ?? '';
            $type = $_GET['type'] ?? 'all';
            $status = $_GET['status'] ?? 'all';
            $date_range = $_GET['date'] ?? 'all';
            echo json_encode(['success' => true, 'requests' => getAllEmployeeRequests($emp_id, $type, $status, $date_range)]);
            break;
            
        case 'export_requests':
            $emp_id = $_GET['emp_id'] ?? '';
            $type = $_GET['type'] ?? 'all';
            $status = $_GET['status'] ?? 'all';
            $date_range = $_GET['date'] ?? 'all';
            
            $csv = exportRequestsToExcel($emp_id, $type, $status, $date_range);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="requests_' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
            break;
            
        case 'get_inventory':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            echo json_encode(['success' => true, 'inventory' => getAllInventory($search, $status)]);
            break;
            
        case 'import_inventory':
            if (isset($_FILES['inventory_file'])) {
                $file = $_FILES['inventory_file'];
                
                // Check for errors
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'File upload error']);
                    break;
                }
                
                // Check file size (max 10MB)
                if ($file['size'] > 10 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File size exceeds 10MB']);
                    break;
                }
                
                // Check file type
                $allowed_types = ['csv', 'xls', 'xlsx'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: CSV, XLS, XLSX']);
                    break;
                }
                
                // Create upload directory if not exists
                $upload_dir = 'uploads/inventory/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . $file['name'];
                $filepath = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $result = importInventoryFromExcel($filepath);
                    
                    // Delete the uploaded file
                    unlink($filepath);
                    
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            }
            break;
            
        case 'get_available_sims':
            echo json_encode(['success' => true, 'sims' => getAvailableSims()]);
            break;
            
        case 'assign_sim':
            $sim_id = $_POST['sim_id'] ?? 0;
            $employee_id = $_POST['employee_id'] ?? 0;
            $request_id = $_POST['request_id'] ?? '';
            $request_type = $_POST['request_type'] ?? '';
            
            echo json_encode(assignSimToEmployee($sim_id, $employee_id, $request_id, $request_type));
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid admin action']);
    }
    exit;
}
?>