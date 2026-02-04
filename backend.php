<?php
// backend.php - Complete Backend Processing
session_start();

// Database configuration
$host = '127.0.0.1';
$dbname = 'sim_managment';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Get user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Get user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Check authentication for AJAX calls
if (!isset($_GET['ajax'])) {
    checkAuth();
}

$user_id = getUserId();
$user_role = getUserRole();

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
        'emp_request_types' => ['all']
    ]
];

// Table mapping
$table_map = [
    'sim_issue' => 'sim_issue',
    'activision_of_new_sim' => 'activision_of_new_sim',
    'deactivision_of_sim' => 'deactivision_of_sim',
    'porting_cocp' => 'porting_cocp',
    'activision_e_sim' => 'activision_e_sim',
    'sim_replacement' => 'sim_replacement',
    'temporary_suspension' => 'temporary_suspension',
    'air_offical_usage' => 'air_offical_usage',
    'air_for_personal_use' => 'air_for_personal_use',
    'isd_activision' => 'isd_activision'
];

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

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if (isset($_GET['action'])) {
        switch($_GET['action']) {
            case 'get_my_requests':
                echo json_encode(getMyRequests());
                break;
                
            case 'get_emp_requests':
                echo json_encode(getEmpRequests());
                break;
                
            case 'view_request':
                $table = $_GET['table'] ?? '';
                $id = $_GET['id'] ?? 0;
                echo json_encode(viewRequest($table, $id));
                break;
                
            case 'search_my_requests':
                echo json_encode(searchMyRequests());
                break;
                
            case 'search_emp_requests':
                echo json_encode(searchEmpRequests());
                break;
                
            case 'update_status':
                echo json_encode(updateRequestStatus());
                break;
                
            case 'get_employee':
                $emp_id = $_GET['id'] ?? 0;
                echo json_encode(getEmployeeById($emp_id));
                break;
                
            case 'get_my_profile':
                echo json_encode(['success' => true, 'profile' => $user_data]);
                break;
                
            case 'submit_request':
                echo json_encode(submitRequest());
                break;
                
            case 'logout':
                session_destroy();
                echo json_encode(['success' => true]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    exit;
}

// Function to get my requests
function getMyRequests() {
    global $conn, $user_id, $user_role, $role_permissions, $table_map, $request_names;
    
    $requests = [];
    $my_tables = $role_permissions[$user_role]['my_requests'] ?? [];
    
    foreach ($my_tables as $table_key) {
        $table = $table_map[$table_key];
        
        // Check if REQ_ID or REQUEST_ID exists
        $id_field = 'REQ_ID';
        if ($table == 'activision_of_new_sim' || $table == 'deactivision_of_sim') {
            $id_field = 'REQUEST_ID';
        }
        
        $query = "SELECT *, '$table' as source_table FROM $table WHERE EMP_ID = $user_id ORDER BY req_at DESC LIMIT 100";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['request_type'] = $request_names[$table_key] ?? $table_key;
                $requests[] = $row;
            }
        }
    }
    
    return ['success' => true, 'requests' => $requests];
}

// Function to get employee requests
function getEmpRequests() {
    global $conn, $user_role, $role_permissions, $table_map, $request_names;
    
    $requests = [];
    
    if (!$role_permissions[$user_role]['emp_requests']) {
        return ['success' => true, 'requests' => []];
    }
    
    $emp_tables = $role_permissions[$user_role]['emp_request_types'];
    
    if (in_array('all', $emp_tables)) {
        // Admin sees all tables
        foreach ($table_map as $key => $table) {
            $id_field = ($table == 'activision_of_new_sim' || $table == 'deactivision_of_sim') ? 'REQUEST_ID' : 'REQ_ID';
            
            $query = "SELECT r.*, e.FULL_NAME, e.EMAIL_ID, e.MOBILE_NO, '$table' as source_table 
                     FROM $table r 
                     LEFT JOIN master_employee e ON r.EMP_ID = e.ID 
                     ORDER BY r.req_at DESC LIMIT 100";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['request_type'] = $request_names[$key] ?? $key;
                    $requests[] = $row;
                }
            }
        }
    } else {
        foreach ($emp_tables as $table_key) {
            if (!isset($table_map[$table_key])) continue;
            
            $table = $table_map[$table_key];
            $id_field = ($table == 'activision_of_new_sim' || $table == 'deactivision_of_sim') ? 'REQUEST_ID' : 'REQ_ID';
            
            $query = "SELECT r.*, e.FULL_NAME, e.EMAIL_ID, e.MOBILE_NO, '$table' as source_table 
                     FROM $table r 
                     LEFT JOIN master_employee e ON r.EMP_ID = e.ID 
                     ORDER BY r.req_at DESC LIMIT 100";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['request_type'] = $request_names[$table_key] ?? $table_key;
                    $requests[] = $row;
                }
            }
        }
    }
    
    return ['success' => true, 'requests' => $requests];
}

// View request details
function viewRequest($table, $id) {
    global $conn;
    
    if (!$table || !$id) {
        return ['success' => false, 'message' => 'Invalid parameters'];
    }
    
    // Determine ID field based on table
    $id_field = 'REQ_ID';
    if ($table == 'activision_of_new_sim' || $table == 'deactivision_of_sim') {
        $id_field = 'REQUEST_ID';
    }
    
    $query = "SELECT * FROM $table WHERE $id_field = '" . mysqli_real_escape_string($conn, $id) . "' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $request = mysqli_fetch_assoc($result);
        return ['success' => true, 'request' => $request];
    }
    
    return ['success' => false, 'message' => 'Request not found'];
}

// Search my requests
function searchMyRequests() {
    global $conn, $user_id, $user_role, $role_permissions, $table_map;
    
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    $date = $_GET['date'] ?? '';
    
    $requests = [];
    $my_tables = $role_permissions[$user_role]['my_requests'] ?? [];
    
    foreach ($my_tables as $table_key) {
        if ($type && $type != 'all' && $type != $table_key) continue;
        
        $table = $table_map[$table_key];
        $query = "SELECT *, '$table' as source_table FROM $table WHERE EMP_ID = $user_id";
        
        if ($status && $status != 'all') {
            $query .= " AND STATUS = '$status'";
        }
        
        if ($date && $date != 'all') {
            $query .= getDateCondition($date);
        }
        
        $query .= " ORDER BY req_at DESC";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['request_type'] = getRequestTypeName($table);
                $requests[] = $row;
            }
        }
    }
    
    return ['success' => true, 'requests' => $requests];
}

// Search employee requests
function searchEmpRequests() {
    global $conn, $user_role, $role_permissions, $table_map;
    
    $emp_id = $_GET['emp_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    $date = $_GET['date'] ?? '';
    
    $requests = [];
    
    if (!$role_permissions[$user_role]['emp_requests']) {
        return ['success' => true, 'requests' => []];
    }
    
    $emp_tables = $role_permissions[$user_role]['emp_request_types'];
    
    // Handle "all" for admin
    $tables_to_search = [];
    if (in_array('all', $emp_tables)) {
        $tables_to_search = array_values($table_map);
    } else {
        foreach ($emp_tables as $table_key) {
            if (isset($table_map[$table_key])) {
                $tables_to_search[] = $table_map[$table_key];
            }
        }
    }
    
    foreach ($tables_to_search as $table) {
        if ($type && $type != 'all') {
            $table_key = array_search($table, $table_map);
            if ($table_key !== $type) continue;
        }
        
        $query = "SELECT r.*, e.FULL_NAME, e.EMAIL_ID, e.MOBILE_NO, '$table' as source_table 
                 FROM $table r 
                 LEFT JOIN master_employee e ON r.EMP_ID = e.ID 
                 WHERE 1=1";
        
        if ($emp_id && is_numeric($emp_id)) {
            $query .= " AND r.EMP_ID = " . intval($emp_id);
        }
        
        if ($status && $status != 'all') {
            $query .= " AND r.STATUS = '$status'";
        }
        
        if ($date && $date != 'all') {
            $query .= getDateCondition($date, 'r.');
        }
        
        $query .= " ORDER BY r.req_at DESC";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $table_key = array_search($table, $table_map);
                $row['request_type'] = getRequestTypeName($table);
                $requests[] = $row;
            }
        }
    }
    
    return ['success' => true, 'requests' => $requests];
}

// Update request status (admin only)
function updateRequestStatus() {
    global $conn, $user_role;
    
    if ($user_role != 'admin') {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
    
    $table = $_POST['table'] ?? '';
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    if (!$table || !$id || !$status) {
        return ['success' => false, 'message' => 'Missing parameters'];
    }
    
    // Determine ID field based on table
    $id_field = 'REQ_ID';
    if ($table == 'activision_of_new_sim' || $table == 'deactivision_of_sim') {
        $id_field = 'REQUEST_ID';
    }
    
    // Update based on table structure
    $query = "UPDATE $table SET STATUS = '$status'";
    
    if ($status == 'reject' && $reason) {
        $query .= ", REJECT_REASON = '" . mysqli_real_escape_string($conn, $reason) . "'";
    }
    
    $query .= " WHERE $id_field = '" . mysqli_real_escape_string($conn, $id) . "'";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => mysqli_error($conn)];
}

// Get employee by ID - FIXED VERSION
function getEmployeeById($emp_id = null) {
    global $conn;
    
    // If no emp_id provided, try to get from GET
    if ($emp_id === null) {
        $emp_id = $_GET['id'] ?? 0;
    }
    
    // Validate input
    if (!is_numeric($emp_id) || $emp_id <= 0) {
        return ['success' => false, 'message' => 'Invalid Employee ID'];
    }
    
    // Sanitize input
    $emp_id = intval($emp_id);
    
    $query = "SELECT ID, FULL_NAME, EMAIL_ID, MOBILE_NO, DESGINATION, LOCATION, ROLE 
              FROM master_employee 
              WHERE ID = $emp_id 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)];
    }
    
    if (mysqli_num_rows($result) > 0) {
        $employee = mysqli_fetch_assoc($result);
        
        // Clean up any null values
        foreach ($employee as $key => $value) {
            if (is_null($value)) {
                $employee[$key] = '';
            }
        }
        
        return ['success' => true, 'employee' => $employee];
    }
    
    return ['success' => false, 'message' => 'Employee not found'];
}

// Submit request form
function submitRequest() {
    global $conn, $user_id, $user_role;
    
    $request_type = $_POST['request_type'] ?? '';
    $mode = $_POST['mode'] ?? 'my_request';
    $emp_id = $_POST['EMP_ID'] ?? $user_id;
    
    // Validate request type
    $valid_types = ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp', 
                    'activision_e_sim', 'sim_replacement', 'temporary_suspension', 
                    'air_offical_usage', 'air_for_personal_use', 'isd_activision'];
    
    if (!in_array($request_type, $valid_types)) {
        return ['success' => false, 'message' => 'Invalid request type'];
    }
    
    // Check if user can submit this request type
    if (!canAccessRequestType($user_role, $request_type, $mode)) {
        return ['success' => false, 'message' => 'You do not have permission to submit this request type'];
    }
    
    // Get table name
    $table_name = '';
    switch($request_type) {
        case 'sim_issue': $table_name = 'sim_issue'; break;
        case 'activision_of_new_sim': $table_name = 'activision_of_new_sim'; break;
        case 'deactivision_of_sim': $table_name = 'deactivision_of_sim'; break;
        case 'porting_cocp': $table_name = 'porting_cocp'; break;
        case 'activision_e_sim': $table_name = 'activision_e_sim'; break;
        case 'sim_replacement': $table_name = 'sim_replacement'; break;
        case 'temporary_suspension': $table_name = 'temporary_suspension'; break;
        case 'air_offical_usage': $table_name = 'air_offical_usage'; break;
        case 'air_for_personal_use': $table_name = 'air_for_personal_use'; break;
        case 'isd_activision': $table_name = 'isd_activision'; break;
    }
    
    // Prepare data based on request type
    $data = prepareRequestData($request_type);
    $data['EMP_ID'] = $emp_id;
    $data['STATUS'] = 'pending';
    
    // Generate request ID based on table
    $request_id = generateRequestId($table_name);
    
    // Build insert query
    $columns = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $columns[] = $column;
        $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
    }
    
    // Add request by and timestamp
    $columns[] = 'REQ_BY';
    $values[] = $user_id;
    $columns[] = 'REQ_AT';
    $values[] = 'NOW()';
    
    // For tables that use REQUEST_ID instead of REQ_ID
    if ($table_name == 'activision_of_new_sim' || $table_name == 'deactivision_of_sim') {
        $columns[] = 'REQUEST_ID';
        $values[] = "'$request_id'";
    } else {
        $columns[] = 'REQ_ID';
        $values[] = "'$request_id'";
    }
    
    $columns_str = implode(', ', $columns);
    $values_str = implode(', ', $values);
    
    $query = "INSERT INTO $table_name ($columns_str) VALUES ($values_str)";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'request_id' => $request_id];
    } else {
        return ['success' => false, 'message' => mysqli_error($conn)];
    }
}

// Prepare request data based on type
function prepareRequestData($request_type) {
    $data = [];
    
    switch($request_type) {
        case 'sim_issue':
            $data = [
                'POI_DOC_NO' => $_POST['POI_DOC_NO'] ?? '',
                'POI_DOC_TYPE' => $_POST['POI_DOC_TYPE'] ?? '',
                'RENTAL_PLAN' => $_POST['RENTAL_PLAN'] ?? '',
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'MOBILE_NO_AL' => $_POST['MOBILE_NO_AL'] ?? '',
                'REMARKS' => $_POST['REMARKS'] ?? ''
            ];
            break;
            
        case 'activision_of_new_sim':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'MOBILE_NO' => $_POST['MOBILE_NO'] ?? '',
                'REMARKS' => $_POST['REMARKS'] ?? ''
            ];
            break;
            
        case 'deactivision_of_sim':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REASON' => $_POST['REASON'] ?? ''
            ];
            break;
            
        case 'porting_cocp':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'CURRENT' => $_POST['CURRENT'] ?? '',
                'TO_SIM' => $_POST['TO_SIM'] ?? '',
                'REASON' => $_POST['REASON'] ?? ''
            ];
            break;
            
        case 'activision_e_sim':
            $data = [
                'SIMM_NO' => $_POST['SIMM_NO'] ?? '',
                'REMARKS' => $_POST['REMARKS'] ?? ''
            ];
            break;
            
        case 'sim_replacement':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REASON' => $_POST['REASON'] ?? ''
            ];
            break;
            
        case 'temporary_suspension':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REASON' => $_POST['REASON'] ?? ''
            ];
            break;
            
        case 'air_offical_usage':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REASON' => $_POST['REASON'] ?? '',
                'DURATION' => $_POST['DURATION'] ?? 0
            ];
            break;
            
        case 'air_for_personal_use':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REASON' => $_POST['REASON'] ?? '',
                'DURATION' => $_POST['DURATION'] ?? 0
            ];
            break;
            
        case 'isd_activision':
            $data = [
                'SIM_NO' => $_POST['SIM_NO'] ?? '',
                'REMARKS' => $_POST['REMARKS'] ?? ''
            ];
            break;
    }
    
    return array_filter($data, function($value) {
        return $value !== null && $value !== '';
    });
}

// Generate unique request ID
function generateRequestId($table_name) {
    $prefix = strtoupper(substr($table_name, 0, 3));
    $timestamp = time();
    $random = rand(1000, 9999);
    return $prefix . $timestamp . $random;
}

// Check if user can access request type
function canAccessRequestType($user_role, $request_type, $mode = 'my_request') {
    $role_access = [
        'Employee' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => []
        ],
        'HR' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp']
        ],
        'HOD' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['isd_activision']
        ],
        'Head HR' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['air_for_personal_use']
        ],
        'CEO' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['air_offical_usage']
        ],
        'admin' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp', 
                            'activision_e_sim', 'sim_replacement', 'temporary_suspension', 'air_offical_usage', 
                            'air_for_personal_use', 'isd_activision']
        ]
    ];
    
    if (!isset($role_access[$user_role][$mode])) {
        return false;
    }
    
    return in_array($request_type, $role_access[$user_role][$mode] ?? []);
}

// Helper function to get request type name
function getRequestTypeName($table) {
    global $request_names, $table_map;
    
    foreach ($table_map as $key => $table_name) {
        if ($table_name === $table) {
            return $request_names[$key] ?? $key;
        }
    }
    
    return $table;
}

// Helper function for date conditions
function getDateCondition($range, $prefix = '') {
    $conditions = [
        'latest' => " AND {$prefix}req_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        'last_3_months' => " AND {$prefix}req_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)",
        '3_6_months' => " AND {$prefix}req_at BETWEEN DATE_SUB(NOW(), INTERVAL 6 MONTH) AND DATE_SUB(NOW(), INTERVAL 3 MONTH)",
        '6_12_months' => " AND {$prefix}req_at BETWEEN DATE_SUB(NOW(), INTERVAL 12 MONTH) AND DATE_SUB(NOW(), INTERVAL 6 MONTH)",
        'above_12_months' => " AND {$prefix}req_at < DATE_SUB(NOW(), INTERVAL 12 MONTH)"
    ];
    
    return $conditions[$range] ?? '';
}

// Close database connection at the end of script execution
register_shutdown_function(function() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
});
?>