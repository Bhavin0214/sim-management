<?php
// forms.php - Dynamic form for all request types
require_once 'config.php';
require_once 'form_structure.php';

checkAuth();

$user_id = getUserId();
$user_role = getUserRole();

// Get request type and mode from URL
$request_type = $_GET['type'] ?? '';
$mode = $_GET['mode'] ?? 'my_request'; // my_request or emp_request

// Validate request type
if (!$request_type || !isset($form_structures['request_types'][$request_type])) {
    die('<div class="error">Invalid request type</div>');
}

// Check if user can access this request type
if (!canAccessRequestType($user_role, $request_type, $mode)) {
    die('<div class="error">You do not have permission to access this request type</div>');
}

// Get user data for autofill
$user_query = "SELECT * FROM master_employee WHERE ID = $user_id";
$user_result = mysqli_query($conn, $user_query);
$current_user = mysqli_fetch_assoc($user_result);

// Get request structure
$request_structure = $form_structures['request_types'][$request_type];
$request_title = $request_structure['title'];
$common_fields = $form_structures['common_fields'];

// Get employee data if emp_id is provided
$employee_data = [];
if ($mode === 'emp_request' && isset($_GET['emp_id'])) {
    $emp_id = intval($_GET['emp_id']);
    $emp_query = "SELECT * FROM master_employee WHERE ID = $emp_id";
    $emp_result = mysqli_query($conn, $emp_query);
    if ($emp_result && mysqli_num_rows($emp_result) > 0) {
        $employee_data = mysqli_fetch_assoc($emp_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $request_title; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 24px 32px;
        }
        
        .form-header h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .form-content {
            padding: 32px;
        }
        
        .form-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a237e;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .form-label .required {
            color: #d32f2f;
            margin-left: 4px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #1a237e;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        .form-input[readonly] {
            background: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-file {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .form-file:hover {
            border-color: #1a237e;
            background: #f8f9ff;
        }
        
        .form-file input {
            display: none;
        }
        
        .file-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #666;
            cursor: pointer;
        }
        
        .file-label i {
            font-size: 24px;
            color: #1a237e;
        }
        
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #eee;
        }
        
        .form-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.3);
        }
        
        .btn-cancel {
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-cancel:hover {
            background: #e9e9e9;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error i {
            font-size: 18px;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .employee-search {
            background: #f8f9ff;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .search-input-group {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 16px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
        }
        
        .search-btn {
            padding: 10px 20px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .form-content {
                padding: 20px;
            }
            
            .form-header {
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
    <div class="form-container">
        <div class="form-header">
            <h2><?php echo $request_title; ?></h2>
            <p>Fill in the details below to submit your request</p>
        </div>
        
        <div class="form-content">
            <!-- Messages will be displayed here -->
            <div id="messageContainer"></div>
            
            <form id="requestForm" enctype="multipart/form-data">
                <!-- Employee Details Section -->
                <div class="form-section">
                    <h3 class="section-title">Employee Details</h3>
                    
                    <?php if($mode === 'emp_request'): ?>
                    <!-- Employee Search for HR/HOD/CEO/Head HR -->
                    <div class="employee-search">
                        <div class="search-input-group">
                            <input type="number" 
                                   id="searchEmpId" 
                                   class="search-input" 
                                   placeholder="Enter Employee ID to search"
                                   min="1"
                                   value="<?php echo !empty($employee_data) ? $employee_data['ID'] : ''; ?>">
                            <button type="button" class="search-btn" onclick="searchEmployee()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <p style="margin-top: 10px; font-size: 13px; color: #666;">
                            Enter the Employee ID to autofill employee details
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <?php foreach($common_fields['employee_section']['fields'] as $field_name => $field_config): ?>
                            <?php 
                                $is_readonly = ($mode === 'my_request') ? true : false;
                                $value = ($mode === 'my_request') ? 
                                         htmlspecialchars($current_user[$field_name] ?? '') : 
                                         ($mode === 'emp_request' && !empty($employee_data)) ? 
                                         htmlspecialchars($employee_data[$field_name] ?? '') : '';
                            ?>
                            <div class="form-group">
                                <label class="form-label">
                                    <?php echo $field_config['label']; ?>
                                    <?php if($field_config['required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="<?php echo $field_config['type']; ?>"
                                       name="<?php echo $field_name; ?>"
                                       id="<?php echo $field_name; ?>"
                                       class="form-input"
                                       <?php if($is_readonly || ($mode === 'emp_request' && !empty($employee_data))): ?>readonly<?php endif; ?>
                                       <?php if($field_config['required']): ?>required<?php endif; ?>
                                       value="<?php echo $value; ?>"
                                       placeholder="<?php echo $mode === 'emp_request' && empty($employee_data) ? 'Enter Employee ID first' : ''; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Request Specific Section -->
                <div class="form-section">
                    <h3 class="section-title">Request Details</h3>
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
                                              rows="4"></textarea>
                                
                                <?php elseif($field_config['type'] === 'file'): ?>
                                    <div class="form-file">
                                        <label class="file-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Click to upload <?php echo $field_config['label']; ?></span>
                                            <span style="font-size: 12px; color: #999;">
                                                Supported: <?php echo $field_config['accept'] ?? 'All files'; ?>
                                            </span>
                                            <input type="file"
                                                   name="<?php echo $field_name; ?>"
                                                   id="<?php echo $field_name; ?>"
                                                   <?php if($field_config['accept']): ?>accept="<?php echo $field_config['accept']; ?>"<?php endif; ?>
                                                   <?php if($field_config['required']): ?>required<?php endif; ?>>
                                        </label>
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
                <input type="hidden" name="table_name" value="<?php echo $request_structure['table']; ?>">
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="form-btn btn-cancel" onclick="closeForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="form-btn btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Global variables
        let isMyRequest = <?php echo $mode === 'my_request' ? 'true' : 'false'; ?>;
        
        // Auto-fill employee details for my requests
        if (isMyRequest) {
            document.addEventListener('DOMContentLoaded', function() {
                // Get logged-in user data
                fetch('backend.php?action=get_my_profile')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Fill employee ID
                            document.getElementById('EMP_ID').value = data.profile.ID;
                        }
                    });
            });
        }
        
        // Search employee by ID (for emp_request mode)
        function searchEmployee() {
            const empId = document.getElementById('searchEmpId').value;
            
            if (!empId) {
                showMessage('Please enter an Employee ID', 'error');
                return;
            }
            
            showMessage('Searching employee...', 'info');
            
            fetch(`backend.php?action=get_employee&id=${empId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.employee) {
                        // Fill employee details
                        const emp = data.employee;
                        document.getElementById('EMP_ID').value = emp.ID;
                        document.getElementById('FULL_NAME').value = emp.FULL_NAME || '';
                        document.getElementById('MOBILE_NO').value = emp.MOBILE_NO || '';
                        document.getElementById('EMAIL_ID').value = emp.EMAIL_ID || '';
                        document.getElementById('DESGINATION').value = emp.DESGINATION || '';
                        document.getElementById('LOCATION').value = emp.LOCATION || '';
                        
                        showMessage('Employee details loaded successfully', 'success');
                    } else {
                        showMessage('Employee not found', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Error searching employee', 'error');
                    console.error('Error:', error);
                });
        }
        
        // Show message
        function showMessage(message, type = 'info') {
            const messageContainer = document.getElementById('messageContainer');
            const className = type === 'error' ? 'error' : 
                            type === 'success' ? 'success' : 'info';
            
            messageContainer.innerHTML = `
                <div class="${className}">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 
                                      type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    ${message}
                </div>
            `;
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    messageContainer.innerHTML = '';
                }, 5000);
            }
        }
        
        // Form submission
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Prepare form data
            const formData = new FormData(this);
            
            // Show loading state
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Submit form
            fetch('backend.php?action=submit_request', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('Request submitted successfully!', 'success');
                    
                    // Reset form after successful submission
                    this.reset();
                    
                    // Reload parent window requests if in iframe/modal
                    if (window.parent && window.parent.loadMyRequests) {
                        window.parent.loadMyRequests();
                    }
                    if (window.parent && window.parent.loadEmpRequests) {
                        window.parent.loadEmpRequests();
                    }
                    
                    // Close form after 2 seconds
                    setTimeout(() => {
                        closeForm();
                    }, 2000);
                } else {
                    showMessage('Error: ' + (data.message || 'Submission failed'), 'error');
                }
            })
            .catch(error => {
                showMessage('Network error. Please try again.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Form validation
        function validateForm() {
            const form = document.getElementById('requestForm');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            // Check required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#d32f2f';
                    isValid = false;
                    
                    // Add error class
                    field.classList.add('error-field');
                    
                    // Show error message
                    const fieldName = field.name.replace(/_/g, ' ');
                    showMessage(`Please fill in ${fieldName}`, 'error');
                } else {
                    field.style.borderColor = '';
                    field.classList.remove('error-field');
                }
            });
            
            // Validate email format if present
            const emailField = document.getElementById('EMAIL_ID');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    emailField.style.borderColor = '#d32f2f';
                    showMessage('Please enter a valid email address', 'error');
                    isValid = false;
                }
            }
            
            // Validate mobile number if present
            const mobileField = document.getElementById('MOBILE_NO');
            if (mobileField && mobileField.value) {
                const mobileRegex = /^[0-9]{10}$/;
                if (!mobileRegex.test(mobileField.value)) {
                    mobileField.style.borderColor = '#d32f2f';
                    showMessage('Please enter a valid 10-digit mobile number', 'error');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        // Close form
        function closeForm() {
            if (window.parent) {
                // If opened in iframe/modal, close it
                if (window.parent.closeModal) {
                    window.parent.closeModal('requestModal');
                } else if (window.parent.location) {
                    window.parent.location.reload();
                }
            } else {
                window.history.back();
            }
        }
        
        // Remove error styling on input
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '';
                this.classList.remove('error-field');
            });
        });
        
        // Show selected file name
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.closest('.file-label');
                if (label && this.files.length > 0) {
                    const fileName = this.files[0].name;
                    label.querySelector('span:nth-child(2)').textContent = `Selected: ${fileName}`;
                }
            });
        });
        
        // Auto-format mobile number
        document.getElementById('MOBILE_NO')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
        
        // Auto-format SIM number (20 digits)
        document.querySelectorAll('input[id*="SIM"]').forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').slice(0, 20);
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>