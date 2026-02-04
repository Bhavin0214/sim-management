<?php
// request_form.php - Full page form with auto-fill on employee ID
require_once 'config.php';
require_once 'form_structure.php';

checkAuth();

$user_id = getUserId();
$user_role = getUserRole();

// Get request type and mode from URL
$request_type = $_GET['type'] ?? '';
$mode = $_GET['mode'] ?? 'my_request';

// Validate request type
if (!$request_type || !isset($form_structures['request_types'][$request_type])) {
    header("Location: dashboard.php");
    exit();
}

// Check if user can access this request type
if (!canAccessRequestType($user_role, $request_type, $mode)) {
    header("Location: dashboard.php");
    exit();
}

// Get user data for autofill
$user_query = "SELECT * FROM master_employee WHERE ID = $user_id";
$user_result = mysqli_query($conn, $user_query);
$current_user = mysqli_fetch_assoc($user_result);

// Get request structure
$request_structure = $form_structures['request_types'][$request_type];
$request_title = $request_structure['title'];
$common_fields = $form_structures['common_fields'];

// Get employee data if emp_id is provided in URL
$employee_data = [];
if ($mode === 'emp_request' && isset($_GET['emp_id'])) {
    $emp_id = intval($_GET['emp_id']);
    $emp_query = "SELECT * FROM master_employee WHERE ID = $emp_id";
    $emp_result = mysqli_query($conn, $emp_query);
    if ($emp_result && mysqli_num_rows($emp_result) > 0) {
        $employee_data = mysqli_fetch_assoc($emp_result);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    // Prepare data for backend submission
    $post_data = [
        'request_type' => $request_type,
        'mode' => $mode,
        'EMP_ID' => $_POST['EMP_ID'] ?? $user_id
    ];
    
    // Add all form fields
    foreach ($request_structure['fields'] as $field_name => $field_config) {
        if ($field_config['type'] === 'file') {
            // Handle file upload
            if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == 0) {
                // In a real system, you would save the file and store the path
                $post_data[$field_name] = $_FILES[$field_name]['name'];
            }
        } else {
            $post_data[$field_name] = $_POST[$field_name] ?? '';
        }
    }
    
    // Submit to backend
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'backend.php?ajax=1&action=submit_request');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result['success']) {
        $success_message = "Request submitted successfully! Request ID: " . $result['request_id'];
    } else {
        $error_message = "Error: " . ($result['message'] ?? "Submission failed");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NORA INFOTECH - <?php echo getRequestTypeName($request_type); ?> Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #004d4d;
            --primary-light: #006666;
            --secondary: #10b981;
            --text: #333333;
            --text-light: #666666;
            --border: #e5e7eb;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 6px;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: #f0fdfa;
            color: var(--text);
            font-size: 13px;
            line-height: 1.4;
            min-height: 100vh;
        }
        
        .header {
            background: var(--bg-white);
            height: 52px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary);
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        
        .back-btn {
            margin-left: auto;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-btn:hover {
            background: var(--primary-light);
        }
        
        .form-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        .form-header {
            background: var(--primary);
            color: white;
            padding: 16px 24px;
        }
        
        .form-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .form-header p {
            opacity: 0.9;
            font-size: 12px;
        }
        
        .form-content {
            padding: 24px;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .form-section {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
        }
        
        .form-label .required {
            color: var(--danger);
            margin-left: 2px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s;
            background: var(--bg-white);
            color: var(--text);
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 77, 77, 0.1);
        }
        
        .form-input[readonly] {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .auto-fill-note {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 4px;
            font-style: italic;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .form-btn {
            padding: 8px 24px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-submit {
            background: var(--primary);
            color: white;
        }
        
        .btn-submit:hover {
            background: var(--primary-light);
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
        }
        
        .employee-id-input {
            position: relative;
        }
        
        .search-btn {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            font-size: 11px;
            cursor: pointer;
            display: none;
        }
        
        .search-btn.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .form-content {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="dashboard.php" class="logo-section">
            <div class="logo-text">NORA INFOTECH</div>
        </a>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </header>
    
    <!-- Main Form -->
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h2><?php echo getRequestTypeName($request_type); ?> Request</h2>
                <p>Fill in the details below to submit your request</p>
            </div>
            
            <div class="form-content">
                <!-- Messages -->
                <?php if(isset($success_message)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
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
                                           <?php if($mode === 'my_request'): ?>
                                               readonly value="<?php echo $user_id; ?>"
                                           <?php elseif($mode === 'emp_request' && !empty($employee_data)): ?>
                                               value="<?php echo $employee_data['ID'] ?? ''; ?>"
                                           <?php endif; ?>
                                           placeholder="Enter Employee ID"
                                           oninput="checkEmployeeId()"
                                           onchange="onEmployeeIdChange()"
                                           onkeyup="onEmployeeIdKeyUp(event)">
                                    <button type="button" class="search-btn" id="searchEmpBtn" onclick="fetchEmployeeDetails()">
                                        <i class="fas fa-search"></i> Fetch
                                    </button>
                                </div>
                                <?php if($mode === 'emp_request'): ?>
                                <div class="auto-fill-note">
                                    Enter Employee ID and click Fetch to auto-fill details (or press Enter)
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Other Employee Fields -->
                            <?php 
                            $employee_fields = ['FULL_NAME', 'EMAIL_ID', 'MOBILE_NO', 'DESGINATION', 'LOCATION'];
                            foreach($employee_fields as $field_name): 
                                $field_config = $common_fields['employee_section']['fields'][$field_name] ?? [];
                            ?>
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $field_config['label'] ?? str_replace('_', ' ', $field_name); ?>
                                        <?php if(($field_config['required'] ?? false)): ?><span class="required">*</span><?php endif; ?>
                                    </label>
                                    <input type="<?php echo $field_config['type'] ?? 'text'; ?>"
                                           name="<?php echo $field_name; ?>"
                                           id="<?php echo $field_name; ?>"
                                           class="form-input"
                                           <?php if($mode === 'my_request' || ($mode === 'emp_request' && !empty($employee_data))): ?>
                                               readonly
                                           <?php endif; ?>
                                           value="<?php echo htmlspecialchars(($mode === 'my_request' ? $current_user[$field_name] ?? '' : ($employee_data[$field_name] ?? ''))); ?>"
                                           placeholder="<?php echo $mode === 'emp_request' ? 'Enter Employee ID first' : ''; ?>">
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
                            <?php foreach($request_structure['fields'] as $field_name => $field_config): ?>
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $field_config['label']; ?>
                                        <?php if($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                                    </label>
                                    
                                    <?php if($field_config['type'] === 'select'): ?>
                                        <select name="<?php echo $field_name; ?>"
                                                id="<?php echo $field_name; ?>"
                                                class="form-select"
                                                <?php if($field_config['required']): ?>required<?php endif; ?>>
                                            <option value="">Select <?php echo $field_config['label']; ?></option>
                                            <?php foreach($field_config['options'] as $value => $label): ?>
                                                <option value="<?php echo htmlspecialchars($value); ?>">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif($field_config['type'] === 'textarea'): ?>
                                        <textarea name="<?php echo $field_name; ?>"
                                                  id="<?php echo $field_name; ?>"
                                                  class="form-textarea"
                                                  <?php if($field_config['required']): ?>required<?php endif; ?>
                                                  placeholder="<?php echo $field_config['placeholder'] ?? ''; ?>"
                                                  rows="3"></textarea>
                                    
                                    <?php elseif($field_config['type'] === 'file'): ?>
                                        <input type="file"
                                               name="<?php echo $field_name; ?>"
                                               id="<?php echo $field_name; ?>"
                                               class="form-input"
                                               <?php if($field_config['accept']): ?>accept="<?php echo $field_config['accept']; ?>"<?php endif; ?>
                                               <?php if($field_config['required']): ?>required<?php endif; ?>>
                                        <div class="auto-fill-note">
                                            Supported formats: <?php echo $field_config['accept'] ?? 'All files'; ?>
                                        </div>
                                    
                                    <?php else: ?>
                                        <input type="<?php echo $field_config['type']; ?>"
                                               name="<?php echo $field_name; ?>"
                                               id="<?php echo $field_name; ?>"
                                               class="form-input"
                                               <?php if($field_config['required']): ?>required<?php endif; ?>
                                               placeholder="<?php echo $field_config['placeholder'] ?? ''; ?>"
                                               <?php if(isset($field_config['min'])): ?>min="<?php echo $field_config['min']; ?>"<?php endif; ?>
                                               <?php if(isset($field_config['max'])): ?>max="<?php echo $field_config['max']; ?>"<?php endif; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Hidden fields -->
                    <input type="hidden" name="request_type" value="<?php echo $request_type; ?>">
                    <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="dashboard.php" class="form-btn btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="submit_request" class="form-btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-fill for my requests
        document.addEventListener('DOMContentLoaded', function() {
            const mode = '<?php echo $mode; ?>';
            
            if (mode === 'my_request') {
                // All data is already pre-filled in PHP
                console.log('My request form loaded with auto-filled data');
            } else if (mode === 'emp_request' && '<?php echo !empty($employee_data) ? 'true' : 'false'; ?>' === 'true') {
                // Employee data was pre-filled via URL parameter
                console.log('Employee request form loaded with pre-filled data');
            }
        });
        
        // Check if employee ID is entered (for emp_request mode)
        function checkEmployeeId() {
            const empId = document.getElementById('EMP_ID').value;
            const searchBtn = document.getElementById('searchEmpBtn');
            
            if (empId && empId.length > 0) {
                searchBtn.classList.add('show');
            } else {
                searchBtn.classList.remove('show');
            }
        }
        
        // Employee ID change handler
        function onEmployeeIdChange() {
            const mode = '<?php echo $mode; ?>';
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
            const mode = '<?php echo $mode; ?>';
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
                    alert('Please enter an Employee ID');
                    return;
                }
            }
            
            // Validate it's a number
            if (isNaN(empId) || empId <= 0) {
                alert('Please enter a valid Employee ID');
                return;
            }
            
            // Show loading state
            const searchBtn = document.getElementById('searchEmpBtn');
            const originalHTML = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            searchBtn.disabled = true;
            
            fetch(`backend.php?ajax=1&action=get_employee&id=${empId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.employee) {
                        // Fill employee details
                        const emp = data.employee;
                        document.getElementById('FULL_NAME').value = emp.FULL_NAME || '';
                        document.getElementById('EMAIL_ID').value = emp.EMAIL_ID || '';
                        document.getElementById('MOBILE_NO').value = emp.MOBILE_NO || '';
                        document.getElementById('DESGINATION').value = emp.DESGINATION || '';
                        document.getElementById('LOCATION').value = emp.LOCATION || '';
                        
                        // Make fields read-only
                        const employeeFields = ['FULL_NAME', 'EMAIL_ID', 'MOBILE_NO', 'DESGINATION', 'LOCATION'];
                        employeeFields.forEach(field => {
                            const fieldElement = document.getElementById(field);
                            if (fieldElement) {
                                fieldElement.readOnly = true;
                            }
                        });
                        
                        // Show success message
                        if (!autoMode) {
                            alert('Employee details loaded successfully');
                        }
                    } else if (!autoMode) {
                        alert('Employee not found');
                        clearEmployeeDetails();
                    }
                })
                .catch(error => {
                    if (!autoMode) {
                        alert('Error fetching employee details');
                    }
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Restore button state
                    searchBtn.innerHTML = originalHTML;
                    searchBtn.disabled = false;
                });
        }
        
        // Clear employee details
        function clearEmployeeDetails() {
            document.getElementById('FULL_NAME').value = '';
            document.getElementById('EMAIL_ID').value = '';
            document.getElementById('MOBILE_NO').value = '';
            document.getElementById('DESGINATION').value = '';
            document.getElementById('LOCATION').value = '';
            
            // Make fields editable again
            const employeeFields = ['FULL_NAME', 'EMAIL_ID', 'MOBILE_NO', 'DESGINATION', 'LOCATION'];
            employeeFields.forEach(field => {
                const fieldElement = document.getElementById(field);
                if (fieldElement) {
                    fieldElement.readOnly = false;
                    fieldElement.placeholder = 'Enter Employee ID first';
                }
            });
        }
        
        // Form validation
        document.getElementById('requestForm')?.addEventListener('submit', function(e) {
            // Basic validation
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger)';
                    isValid = false;
                    
                    // Scroll to first error
                    if (isValid === false) {
                        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Remove error styling on input
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '';
            });
        });
        
        // Auto-format mobile number input
        document.getElementById('MOBILE_NO')?.addEventListener('input', function(e) {
            if (this.value && !this.readOnly) {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>