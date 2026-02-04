
<?php
// form_structure.php - Form configuration for all request types

$form_structures = [
    // Common fields for all forms
    'common_fields' => [
        'employee_section' => [
            'title' => 'Employee Details',
            'fields' => [
                'EMP_ID' => [
                    'type' => 'number',
                    'label' => 'Employee ID',
                    'required' => true,
                    'readonly' => false,
                    'autofill' => true
                ],
                'FULL_NAME' => [
                    'type' => 'text',
                    'label' => 'Employee Name',
                    'required' => true,
                    'readonly' => true,
                    'autofill' => true
                ],
                'MOBILE_NO' => [
                    'type' => 'text',
                    'label' => 'Mobile Number',
                    'required' => true,
                    'readonly' => true,
                    'autofill' => true
                ],
                'EMAIL_ID' => [
                    'type' => 'email',
                    'label' => 'Email ID',
                    'required' => true,
                    'readonly' => true,
                    'autofill' => true
                ],
                'DESGINATION' => [
                    'type' => 'text',
                    'label' => 'Designation',
                    'required' => false,
                    'readonly' => true,
                    'autofill' => true
                ],
                'LOCATION' => [
                    'type' => 'text',
                    'label' => 'Location',
                    'required' => false,
                    'readonly' => true,
                    'autofill' => true
                ]
            ]
        ]
    ],
    
    // Request-specific fields
    'request_types' => [
        'sim_issue' => [
            'title' => 'SIM Issuance Request',
            'table' => 'sim_issue',
            'fields' => [
                'LEVEL' => [
                    'type' => 'select',
                    'label' => 'Employee Level',
                    'required' => true,
                    'options' => [
                        'M-1' => 'M-1',
                        'M-2' => 'M-2',
                        'M-3' => 'M-3',
                        'M-4' => 'M-4',
                        'M-5' => 'M-5',
                        'M-6' => 'M-6',
                        'M-7' => 'M-7',
                        'M-8' => 'M-8',
                        'M-9' => 'M-9',
                        'M-10' => 'M-10',
                        'M-11' => 'M-11',
                        'Common' => 'Common'
                    ]
                ],
                'POI_DOC_NO' => [
                    'type' => 'text',
                    'label' => 'POI Document Number',
                    'required' => true,
                    'placeholder' => 'Enter document number'
                ],
                'POI_DOC_TYPE' => [
                    'type' => 'select',
                    'label' => 'POI Document Type',
                    'required' => true,
                    'options' => [
                        'Aadhar' => 'Aadhar Card',
                        'PAN' => 'PAN Card',
                        'Passport' => 'Passport',
                        'Driving_License' => 'Driving License',
                        'Voter_ID' => 'Voter ID'
                    ]
                ],
                'RENTAL_PLAN' => [
                    'type' => 'select',
                    'label' => 'Rental Plan',
                    'required' => true,
                    'options' => [] // Will be populated from database
                ],
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter 20-digit SIM number'
                ],
                'MOBILE_NO_AL' => [
                    'type' => 'text',
                    'label' => 'Alternative Mobile Number',
                    'required' => false,
                    'placeholder' => 'Enter alternative mobile'
                ],
                'REMARKS' => [
                    'type' => 'textarea',
                    'label' => 'Remarks',
                    'required' => false,
                    'placeholder' => 'Any additional remarks'
                ],
                'POI' => [
                    'type' => 'file',
                    'label' => 'Proof of Identity (POI)',
                    'required' => true,
                    'accept' => 'image/*,.pdf,.doc,.docx'
                ]
            ]
        ],
        
        'activision_of_new_sim' => [
            'title' => 'New SIM Activation Request',
            'table' => 'activision_of_new_sim',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number'
                ],
                'MOBILE_NO' => [
                    'type' => 'text',
                    'label' => 'Mobile Number',
                    'required' => true,
                    'placeholder' => 'Enter mobile number'
                ],
                'REMARKS' => [
                    'type' => 'textarea',
                    'label' => 'Remarks',
                    'required' => false,
                    'placeholder' => 'Any additional remarks'
                ]
            ]
        ],
        
        'deactivision_of_sim' => [
            'title' => 'SIM Deactivation Request',
            'table' => 'deactivision_of_sim',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number to deactivate'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Reason for Deactivation',
                    'required' => true,
                    'placeholder' => 'Specify reason for deactivation'
                ]
            ]
        ],
        
        'porting_cocp' => [
            'title' => 'Porting COCP Request',
            'table' => 'porting_cocp',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'Current SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter current SIM number'
                ],
                'CURRENT' => [
                    'type' => 'text',
                    'label' => 'Current Mobile Number',
                    'required' => true,
                    'placeholder' => 'Current mobile number'
                ],
                'TO_SIM' => [
                    'type' => 'text',
                    'label' => 'New SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter new SIM number'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Reason for Porting',
                    'required' => true,
                    'placeholder' => 'Specify reason for porting'
                ]
            ]
        ],
        
        'activision_e_sim' => [
            'title' => 'E-SIM Activation Request',
            'table' => 'activision_e_sim',
            'fields' => [
                'SIMM_NO' => [
                    'type' => 'text',
                    'label' => 'E-SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter E-SIM number'
                ],
                'REMARKS' => [
                    'type' => 'textarea',
                    'label' => 'Remarks',
                    'required' => false,
                    'placeholder' => 'Any additional remarks'
                ]
            ]
        ],
        
        'sim_replacement' => [
            'title' => 'SIM Replacement Request',
            'table' => 'sim_replacement',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'Old SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter old SIM number'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Reason for Replacement',
                    'required' => true,
                    'placeholder' => 'Specify reason for replacement (lost/damaged/etc.)'
                ]
            ]
        ],
        
        'temporary_suspension' => [
            'title' => 'Temporary Suspension Request',
            'table' => 'temporary_suspension',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number to suspend'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Reason for Suspension',
                    'required' => true,
                    'placeholder' => 'Specify reason for suspension (theft/misplaced/etc.)'
                ]
            ]
        ],
        
        'air_offical_usage' => [
            'title' => 'International Roaming (Official) Request',
            'table' => 'air_offical_usage',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Purpose of Travel',
                    'required' => true,
                    'placeholder' => 'Specify purpose of official travel'
                ],
                'DURATION' => [
                    'type' => 'number',
                    'label' => 'Duration (Days)',
                    'required' => true,
                    'placeholder' => 'Enter number of days',
                    'min' => 1,
                    'max' => 30
                ]
            ]
        ],
        
        'air_for_personal_use' => [
            'title' => 'International Roaming (Personal) Request',
            'table' => 'air_for_personal_use',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number'
                ],
                'REASON' => [
                    'type' => 'textarea',
                    'label' => 'Purpose of Travel',
                    'required' => true,
                    'placeholder' => 'Specify purpose of personal travel'
                ],
                'DURATION' => [
                    'type' => 'number',
                    'label' => 'Duration (Days)',
                    'required' => true,
                    'placeholder' => 'Enter number of days',
                    'min' => 1,
                    'max' => 30
                ]
            ]
        ],
        
        'isd_activision' => [
            'title' => 'ISD Activation Request',
            'table' => 'isd_activision',
            'fields' => [
                'SIM_NO' => [
                    'type' => 'text',
                    'label' => 'SIM Number',
                    'required' => true,
                    'placeholder' => 'Enter SIM number'
                ],
                'REMARKS' => [
                    'type' => 'textarea',
                    'label' => 'Remarks',
                    'required' => false,
                    'placeholder' => 'Any additional remarks'
                ]
            ]
        ]
    ]
];

// Request type names for display
$request_type_names = [
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

// Get request type name
function getRequestTypeName($type) {
    global $request_type_names;
    return $request_type_names[$type] ?? $type;
}

// In form_structure.php, add this function:

// Function to auto-fill employee data
function autoFillEmployeeData($user_id, $mode = 'my_request', $emp_id = null) {
    global $conn;
    
    $employee_data = [];
    
    if ($mode === 'my_request') {
        // Get current user data
        $query = "SELECT ID, FULL_NAME, EMAIL_ID, MOBILE_NO, DESGINATION, LOCATION 
                  FROM master_employee 
                  WHERE ID = $user_id 
                  LIMIT 1";
    } elseif ($mode === 'emp_request' && $emp_id) {
        // Get specified employee data
        $query = "SELECT ID, FULL_NAME, EMAIL_ID, MOBILE_NO, DESGINATION, LOCATION 
                  FROM master_employee 
                  WHERE ID = $emp_id 
                  LIMIT 1";
    } else {
        return $employee_data;
    }
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $employee_data = mysqli_fetch_assoc($result);
        
        // Ensure all fields have values
        $fields = ['FULL_NAME', 'EMAIL_ID', 'MOBILE_NO', 'DESGINATION', 'LOCATION'];
        foreach ($fields as $field) {
            if (!isset($employee_data[$field])) {
                $employee_data[$field] = '';
            }
        }
    }
    
    return $employee_data;
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
        // In form_structures.php, update the admin role in canAccessRequestType function:

        'admin' => [
            'my_request' => ['activision_e_sim', 'sim_replacement', 'temporary_suspension'],
            'emp_request' => ['sim_issue', 'activision_of_new_sim', 'deactivision_of_sim', 'porting_cocp', 
                            'activision_e_sim', 'sim_replacement', 'temporary_suspension', 'air_offical_usage', 
                            'air_for_personal_use', 'isd_activision']
        ]
    ];
    
    return in_array($request_type, $role_access[$user_role][$mode] ?? []);
}
?>
