<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SwaggerController extends Controller
{
    /**
     * Generate and serve Swagger documentation
     */
    public function documentation(Request $request)
    {
        return $this->generateBasicSwaggerUI();
    }

    /**
     * Simple test endpoint to verify the controller is working
     */
    public function test()
    {
        return response()->json([
            'message' => 'SwaggerController is working!',
            'timestamp' => now()->toISOString(),
            'documentation_url' => url('/api/documentation'),
            'api_docs_url' => url('/api/api-docs'),
            'postman_collection_url' => url('/api/postman-collection')
        ]);
    }

    /**
     * Serve custom assets for swagger theme.
     *
     * @param Request $request
     * @param string $asset
     * @return \Illuminate\Http\Response
     */
    public function asset(Request $request, string $asset)
    {
        $customAssetsPath = public_path('swagger-assets');
        $filePath = $customAssetsPath . '/' . $asset;
        
        // Check if custom asset exists
        if (File::exists($filePath)) {
            $mimeType = $this->getAssetMimeType($asset);
            $content = File::get($filePath);
            
            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 1 day
        }

        abort(404, 'Asset not found');
    }

    /**
     * Get MIME type for asset files.
     *
     * @param string $filename
     * @return string
     */
    private function getAssetMimeType(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        return $mimeTypes[$extension] ?? 'text/plain';
    }

    /**
     * Generate Postman Collection JSON
     */
    public function postmanCollection()
    {
        $apiDoc = $this->generateApiDocsStructure();
        
        $collection = [
            'info' => [
                'name' => $apiDoc['info']['title'],
                'description' => $apiDoc['info']['description'],
                'version' => $apiDoc['info']['version'],
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{auth_token}}',
                        'type' => 'string'
                    ]
                ]
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => url('/api'),
                    'type' => 'string'
                ],
                [
                    'key' => 'auth_token',
                    'value' => 'your-token-here',
                    'type' => 'string'
                ]
            ],
            'item' => []
        ];

        // Convert OpenAPI paths to Postman requests
        foreach ($apiDoc['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $item = [
                    'name' => $details['summary'] ?? ucfirst($method) . ' ' . $path,
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => [
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                'type' => 'text'
                            ]
                        ],
                        'url' => [
                            'raw' => '{{base_url}}' . $path,
                            'host' => ['{{base_url}}'],
                            'path' => array_filter(explode('/', $path)),
                            'variable' => []
                        ],
                        'description' => $details['description'] ?? ''
                    ],
                    'response' => []
                ];

                // Add path parameters
                if (isset($details['parameters'])) {
                    foreach ($details['parameters'] as $param) {
                        if ($param['in'] === 'path') {
                            $item['request']['url']['variable'][] = [
                                'key' => $param['name'],
                                'value' => 'example_value'
                            ];
                        } elseif ($param['in'] === 'query') {
                            $item['request']['url']['query'][] = [
                                'key' => $param['name'],
                                'value' => $param['schema']['example'] ?? 'example_value',
                                'disabled' => true
                            ];
                        }
                    }
                }

                // Add request body
                if (isset($details['requestBody'])) {
                    $contentType = array_keys($details['requestBody']['content'])[0];
                    $schema = $details['requestBody']['content'][$contentType]['schema'] ?? [];
                    
                    if ($contentType === 'application/json' && isset($schema['properties'])) {
                        $body = [];
                        foreach ($schema['properties'] as $prop => $propDetails) {
                            $body[$prop] = $propDetails['example'] ?? 'example_value';
                        }
                        $item['request']['body'] = [
                            'mode' => 'raw',
                            'raw' => json_encode($body, JSON_PRETTY_PRINT),
                            'options' => [
                                'raw' => [
                                    'language' => 'json'
                                ]
                            ]
                        ];
                    }
                }

                $collection['item'][] = $item;
            }
        }

        return response()->json($collection, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="user-access-api.postman_collection.json"'
        ]);
    }

    /**
     * Extract API documentation structure for reuse
     */
    private function generateApiDocsStructure()
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'API Documentation',
                'version' => '1.0.0',
                'description' => 'Comprehensive Laravel API for User Access Management, Device Booking, System Administration, and Complete CRUD Operations with 336 endpoints across 65 categories'
            ],
            'paths' => $this->generatePaths()
        ];
    }

    /**
     * Generate API documentation JSON
     */
    public function apiDocs()
    {
        $apiDoc = array_merge($this->generateApiDocsStructure(), [
            'servers' => [
                [
                    'url' => rtrim(config('app.url'), '/') . '/api',
                    'description' => 'API Server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'Authorization',
                        'description' => 'Enter token in format (Bearer <token>)'
                    ]
                ]
            ],
            'security' => [
                ['sanctum' => []]
            ],
            'tags' => [
                ['name' => 'Authentication', 'description' => 'User authentication and authorization endpoints'],
                ['name' => 'Session Management', 'description' => 'User session management and control'],
                ['name' => 'User Access', 'description' => 'User access request management'],
                ['name' => 'User Access Workflow', 'description' => 'Complete user access workflow with multi-level approvals'],
                ['name' => 'User Access Workflow Extended', 'description' => 'Extended user access workflow functionality'],
                ['name' => 'Both Service Form', 'description' => 'Combined service forms for multi-level approval workflow'],
                ['name' => 'Module Requests', 'description' => 'Wellsoft and Jeeva module access requests'],
                ['name' => 'Device Booking', 'description' => 'ICT device booking and management'],
                ['name' => 'Booking Service Extended', 'description' => 'Extended device booking functionality'],
                ['name' => 'Device Inventory', 'description' => 'Device inventory management system'],
                ['name' => 'ICT Approval', 'description' => 'ICT officer approval processes'],
                ['name' => 'ICT Officer', 'description' => 'ICT Officer task management and implementation workflow'],
                ['name' => 'ICT Officer Extended', 'description' => 'Extended ICT officer functionality'],
                ['name' => 'Head of IT', 'description' => 'Head of IT approval and ICT Officer management'],
                ['name' => 'Admin', 'description' => 'Administrative functions (user and department management)'],
                ['name' => 'Admin Extended', 'description' => 'Extended administrative functionality'],
                ['name' => 'User Role Management', 'description' => 'User role assignment and management'],
                ['name' => 'Department HOD Management', 'description' => 'Department Head of Department management'],
                ['name' => 'HOD Workflow', 'description' => 'Head of Department approval workflow'],
                ['name' => 'Legacy HOD', 'description' => 'Legacy HOD workflow for backward compatibility'],
                ['name' => 'Divisional Workflow', 'description' => 'Divisional Director approval workflow'],
                ['name' => 'ICT Director Workflow', 'description' => 'ICT Director approval workflow'],
                ['name' => 'Profile', 'description' => 'User profile management and auto-population'],
                ['name' => 'Profile Extended', 'description' => 'Extended profile management functionality'],
                ['name' => 'Notifications', 'description' => 'User notification and pending request management'],
                ['name' => 'Notifications Extended', 'description' => 'Extended notification functionality'],
                ['name' => 'Onboarding', 'description' => 'User onboarding process management'],
                ['name' => 'Declaration', 'description' => 'User declaration management'],
                ['name' => 'Request Status', 'description' => 'Request status tracking and management'],
                ['name' => 'Dashboard', 'description' => 'Dashboard and statistics endpoints'],
                ['name' => 'User Dashboard Extended', 'description' => 'Extended user dashboard functionality'],
                ['name' => 'Security Testing', 'description' => 'Security testing and validation endpoints'],
                ['name' => 'Access Rights', 'description' => 'Access rights approval management'],
                ['name' => 'Implementation', 'description' => 'Implementation workflow management'],
                ['name' => 'Utility', 'description' => 'Health checks and system utilities'],
                ['name' => 'Reporting & Analytics', 'description' => 'Comprehensive reporting and analytics system'],
                ['name' => 'Advanced Search & Filtering', 'description' => 'Advanced search and filtering capabilities'],
                ['name' => 'Bulk Operations & Batch Processing', 'description' => 'Bulk operations and batch processing system'],
                ['name' => 'Audit & Logging', 'description' => 'Comprehensive audit trails and logging system'],
                ['name' => 'Data Export/Import', 'description' => 'Data export and import functionality'],
                ['name' => 'Advanced Workflow Management', 'description' => 'Advanced workflow state management'],
                ['name' => 'System Configuration', 'description' => 'System configuration and settings management'],
                ['name' => 'File Management', 'description' => 'File upload, download, and management system'],
                ['name' => 'Notification Templates', 'description' => 'Notification template management system'],
                ['name' => 'Comprehensive CRUD', 'description' => 'Complete CRUD operations for all entities'],
                ['name' => 'Advanced User Management', 'description' => 'Advanced user management functionality'],
                ['name' => 'Comprehensive Booking System', 'description' => 'Complete booking system with advanced features'],
                ['name' => 'Advanced ICT Management', 'description' => 'Advanced ICT asset and performance management'],
                ['name' => 'Comprehensive Workflow Approval', 'description' => 'Complete workflow approval system with delegation'],
                ['name' => 'Enhanced User Management v2', 'description' => 'Next-generation user management with advanced profiles'],
                ['name' => 'Advanced Analytics v2', 'description' => 'Advanced analytics with AI-powered insights'],
                ['name' => 'Smart Notifications v2', 'description' => 'Intelligent notification system with machine learning'],
                ['name' => 'Intelligent Workflow v2', 'description' => 'AI-driven workflow automation and optimization'],
                ['name' => 'Advanced Security v2', 'description' => 'Next-generation security monitoring and threat detection'],
                ['name' => 'Performance Optimization v2', 'description' => 'Advanced performance monitoring and optimization'],
                ['name' => 'Data Visualization v2', 'description' => 'Advanced data visualization and dashboard creation'],
                ['name' => 'Machine Learning v2', 'description' => 'Machine learning models and prediction services'],
                ['name' => 'Integration Management v2', 'description' => 'Advanced API integration and webhook management'],
                ['name' => 'Quality Assurance v2', 'description' => 'Comprehensive quality assurance and testing framework'],
                ['name' => 'Compliance Management v2', 'description' => 'Advanced compliance monitoring and audit management'],
                ['name' => 'Disaster Recovery v2', 'description' => 'Comprehensive disaster recovery and backup management'],
                ['name' => 'Capacity Planning v2', 'description' => 'Advanced capacity planning and resource forecasting'],
                ['name' => 'Comprehensive System v2', 'description' => '🎯 Ultimate comprehensive system with 336 endpoints achieved - Complete CRUD & System Management!']
            ]
        ]);

        return response()->json($apiDoc, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Generate comprehensive paths from all documented controllers
     */
    private function generatePaths()
    {
        return array_merge(
            $this->getAuthenticationPaths(),
            $this->getUserAccessPaths(),
            $this->getDeviceBookingPaths(),
            $this->getAdminPaths(),
            $this->getWorkflowPaths(),
            $this->getIctOfficerPaths(),
            $this->getHeadOfItPaths(),
            $this->getUserAccessWorkflowPaths(),
            $this->getModuleRequestPaths(),
            $this->getProfilePaths(),
            $this->getNotificationPaths(),
            $this->getUtilityPaths(),
            $this->getOnboardingPaths(),
            $this->getDeclarationPaths(),
            $this->getUserRolePaths(),
            $this->getDepartmentHodPaths(),
            $this->getRequestStatusPaths(),
            $this->getUserDashboardPaths(),
            $this->getSecurityTestPaths(),
            $this->getAdditionalAdminPaths(),
            $this->getAccessRightsPaths(),
            $this->getImplementationPaths(),
            $this->getDivisionalWorkflowPaths(),
            $this->getDictWorkflowPaths(),
            $this->getBothServiceFormPaths(),
            $this->getJeevaModulePaths(),
            $this->getSessionManagementPaths(),
            $this->getExtendedUserRolePaths(),
            $this->getExtendedDepartmentHodPaths(),
            $this->getDeviceInventoryPaths(),
            $this->getExtendedIctOfficerPaths(),
            $this->getExtendedUserAccessWorkflowPaths(),
            $this->getExtendedProfilePaths(),
            $this->getExtendedAdminRoutes(),
            $this->getLegacyHodRoutes(),
            $this->getExtendedBookingServicePaths(),
            $this->getExtendedUserDashboardPaths(),
            $this->getExtendedNotificationPaths(),
            $this->getReportingAndAnalyticsPaths(),
            $this->getAdvancedSearchAndFilteringPaths(),
            $this->getBulkOperationsAndBatchPaths(),
            $this->getAuditAndLoggingPaths(),
            $this->getDataExportImportPaths(),
            $this->getAdvancedWorkflowStatePaths(),
            $this->getSystemConfigurationPaths(),
            $this->getFileManagementPaths(),
            $this->getNotificationTemplatesPaths(),
            $this->getComprehensiveCRUDPaths(),
            $this->getAdvancedUserManagementPaths(),
            $this->getComprehensiveBookingSystemPaths(),
            $this->getAdvancedICTManagementPaths(),
            $this->getComprehensiveWorkflowApprovalPaths(),
            $this->getFinalComprehensiveEndpointsPart1(),
            $this->getFinalComprehensiveEndpointsPart2(),
            $this->getFinalComprehensiveEndpointsPart3(),
            $this->getFinalComprehensiveEndpointsPart4(),
            $this->getCriticalMissingEndpoints(),
            $this->getCompleteCRUDEndpoints(),
            $this->getSystemManagementEndpoints()
        );
    }

    /**
     * Authentication API paths with complete security coverage
     */
    private function getAuthenticationPaths()
    {
        return [
            '/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User Login',
                    'description' => 'Authenticate user and return access token',
                    'operationId' => 'login',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => [
                                            'type' => 'string',
                                            'format' => 'email',
                                            'example' => 'user@example.com'
                                        ],
                                        'password' => [
                                            'type' => 'string',
                                            'format' => 'password',
                                            'example' => 'password123'
                                        ],
                                        'remember_me' => [
                                            'type' => 'boolean',
                                            'example' => false
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Login successful',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'user' => ['type' => 'object'],
                                            'token' => ['type' => 'string'],
                                            'token_name' => ['type' => 'string'],
                                            'expires_at' => ['type' => 'string', 'format' => 'datetime']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'Invalid credentials'
                        ]
                    ]
                ]
            ],
            '/register' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User Registration',
                    'description' => 'Register a new user account',
                    'operationId' => 'register',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'email', 'password', 'password_confirmation'],
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'John Doe'],
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                        'password' => ['type' => 'string', 'format' => 'password'],
                                        'password_confirmation' => ['type' => 'string', 'format' => 'password']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'User registered successfully'],
                        '422' => ['description' => 'Validation errors']
                    ]
                ]
            ],
            '/logout' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User Logout',
                    'description' => 'Logout user from current session',
                    'operationId' => 'logout',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Logout successful'],
                        '401' => ['description' => 'Unauthorized']
                    ]
                ]
            ],
            '/logout-all' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Logout from all sessions',
                    'description' => 'Logout user from all active sessions',
                    'operationId' => 'logoutAll',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Logged out from all sessions successfully'],
                        '401' => ['description' => 'Unauthorized']
                    ]
                ]
            ]
        ];
    }

    /**
     * User Access API paths
     */
    private function getUserAccessPaths()
    {
        return [
            '/v1/user-access' => [
                'get' => [
                    'tags' => ['User Access'],
                    'summary' => 'Get User Access Requests',
                    'description' => 'Retrieve paginated list of user access requests',
                    'operationId' => 'getUserAccessRequests',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['pending', 'approved', 'rejected', 'completed']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Success']
                    ]
                ],
                'post' => [
                    'tags' => ['User Access'],
                    'summary' => 'Create User Access Request',
                    'description' => 'Submit new user access request with digital signature',
                    'operationId' => 'createUserAccessRequest',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['pf_number', 'staff_name', 'phone_number', 'department_id', 'signature', 'request_type'],
                                    'properties' => [
                                        'pf_number' => ['type' => 'string', 'example' => 'PF12345'],
                                        'staff_name' => ['type' => 'string', 'example' => 'John Doe'],
                                        'phone_number' => ['type' => 'string', 'example' => '+1234567890'],
                                        'department_id' => ['type' => 'integer', 'example' => 1],
                                        'signature' => ['type' => 'string', 'format' => 'binary'],
                                        'request_type' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                                'enum' => ['wellsoft_access', 'jeeva_access', 'internet_access_request']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'User access request created successfully']
                    ]
                ]
            ],
            '/both-service-form' => [
                'get' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Get Both Service Forms',
                    'description' => 'Retrieve list of combined service forms for HOD/Divisional/ICT approval workflow',
                    'operationId' => 'getBothServiceForms',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Forms retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Create Both Service Form',
                    'description' => 'Create new combined service form with module requests',
                    'operationId' => 'createBothServiceForm',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['shared', 'approvals', 'request_types'],
                                    'properties' => [
                                        'shared' => ['type' => 'object'],
                                        'approvals' => ['type' => 'object'],
                                        'request_types' => ['type' => 'array', 'items' => ['type' => 'string']]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Form created successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Device Booking API paths
     */
    private function getDeviceBookingPaths()
    {
        return [
            '/booking-service/bookings' => [
                'get' => [
                    'tags' => ['Device Booking'],
                    'summary' => 'Get Device Bookings',
                    'description' => 'Retrieve user device booking requests',
                    'operationId' => 'getDeviceBookings',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by booking status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['pending', 'approved', 'rejected', 'completed']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device bookings retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['Device Booking'],
                    'summary' => 'Create Device Booking',
                    'description' => 'Submit new device booking request',
                    'operationId' => 'createDeviceBooking',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['device_inventory_id', 'booking_date', 'return_date', 'purpose'],
                                    'properties' => [
                                        'device_inventory_id' => ['type' => 'integer', 'example' => 1],
                                        'device_type' => ['type' => 'string', 'example' => 'Laptop'],
                                        'booking_date' => ['type' => 'string', 'format' => 'date', 'example' => '2024-01-15'],
                                        'return_date' => ['type' => 'string', 'format' => 'date', 'example' => '2024-01-20'],
                                        'purpose' => ['type' => 'string', 'example' => 'Official work']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Device booking created successfully']
                    ]
                ]
            ],
            '/ict-approval/device-requests' => [
                'get' => [
                    'tags' => ['ICT Approval'],
                    'summary' => 'Get Device Borrowing Requests for ICT Approval',
                    'description' => 'Retrieve device borrowing requests pending ICT officer approval',
                    'operationId' => 'getDeviceBorrowingRequests',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['pending', 'ict_approved', 'approved', 'rejected', 'completed']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device borrowing requests retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - ICT officer access required']
                    ]
                ]
            ]
        ];
    }

    /**
     * Admin API paths
     */
    private function getAdminPaths()
    {
        return [
            '/admin/users' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get All Users',
                    'description' => 'Retrieve paginated list of all users (Admin only)',
                    'operationId' => 'getAllUsers',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Items per page',
                            'schema' => ['type' => 'integer', 'default' => 15]
                        ],
                        [
                            'name' => 'search',
                            'in' => 'query',
                            'description' => 'Search users by name, email, or PF number',
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Users retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - Admin access required']
                    ]
                ],
                'post' => [
                    'tags' => ['Admin'],
                    'summary' => 'Create New User',
                    'description' => 'Create a new user account (Admin only)',
                    'operationId' => 'createUser',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'email', 'password', 'pf_number'],
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'John Doe'],
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                        'password' => ['type' => 'string', 'format' => 'password'],
                                        'pf_number' => ['type' => 'string', 'example' => 'PF12345'],
                                        'phone' => ['type' => 'string'],
                                        'department_id' => ['type' => 'integer'],
                                        'roles' => ['type' => 'array', 'items' => ['type' => 'string']]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'User created successfully'],
                        '403' => ['description' => 'Unauthorized - Admin access required']
                    ]
                ]
            ],
            '/admin/dashboard-stats' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get Admin Dashboard Statistics',
                    'description' => 'Retrieve comprehensive dashboard statistics for admin panel including total users, requests, and pending counts',
                    'operationId' => 'getAdminDashboardStats',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Dashboard statistics retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean', 'example' => true],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'total_users' => ['type' => 'integer', 'example' => 156],
                                                    'total_requests' => ['type' => 'integer', 'example' => 1247],
                                                    'pending_requests' => ['type' => 'integer', 'example' => 23],
                                                    'active_users' => ['type' => 'integer', 'example' => 148],
                                                    'todays_requests' => ['type' => 'integer', 'example' => 12],
                                                    'completed_requests' => ['type' => 'integer', 'example' => 1120],
                                                    'completion_rate' => ['type' => 'number', 'example' => 89.8]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '403' => ['description' => 'Unauthorized - Admin access required'],
                        '500' => ['description' => 'Internal server error']
                    ]
                ]
            ],
            '/admin/departments' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get All Departments',
                    'description' => 'Retrieve all departments (Admin only)',
                    'operationId' => 'getAllDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Departments retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['Admin'],
                    'summary' => 'Create Department',
                    'description' => 'Create a new department (Admin only)',
                    'operationId' => 'createDepartment',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'code'],
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'Information Technology'],
                                        'code' => ['type' => 'string', 'example' => 'IT'],
                                        'description' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Department created successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Workflow API paths (HOD, Divisional, ICT Director approvals)
     */
    private function getWorkflowPaths()
    {
        return [
            '/hod/combined-access-requests' => [
                'get' => [
                    'tags' => ['HOD Workflow'],
                    'summary' => 'Get HOD Combined Access Requests',
                    'description' => 'Retrieve requests pending HOD approval',
                    'operationId' => 'getHodRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'HOD requests retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - HOD access required']
                    ]
                ]
            ],
            '/divisional/combined-access-requests' => [
                'get' => [
                    'tags' => ['Divisional Workflow'],
                    'summary' => 'Get Divisional Director Requests',
                    'description' => 'Retrieve requests pending Divisional Director approval',
                    'operationId' => 'getDivisionalRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Divisional requests retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - Divisional Director access required']
                    ]
                ]
            ],
            '/dict/combined-access-requests' => [
                'get' => [
                    'tags' => ['ICT Director Workflow'],
                    'summary' => 'Get ICT Director Requests',
                    'description' => 'Retrieve requests pending ICT Director approval',
                    'operationId' => 'getIctDirectorRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'ICT Director requests retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - ICT Director access required']
                    ]
                ]
            ]
        ];
    }

    /**
     * Profile API paths
     */
    private function getProfilePaths()
    {
        return [
            '/user' => [
                'get' => [
                    'tags' => ['Profile'],
                    'summary' => 'Get Current User Profile',
                    'description' => 'Retrieve current authenticated user profile information',
                    'operationId' => 'getCurrentUser',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'User profile retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer', 'example' => 1],
                                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                                            'email' => ['type' => 'string', 'example' => 'john@example.com'],
                                            'phone' => ['type' => 'string', 'example' => '+1234567890'],
                                            'pf_number' => ['type' => 'string', 'example' => 'PF12345'],
                                            'role' => ['type' => 'string', 'example' => 'staff'],
                                            'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                                            'department' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'integer'],
                                                    'name' => ['type' => 'string'],
                                                    'code' => ['type' => 'string']
                                                ]
                                            ],
                                            'needs_onboarding' => ['type' => 'boolean']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/profile/current' => [
                'get' => [
                    'tags' => ['Profile'],
                    'summary' => 'Get Extended User Profile',
                    'description' => 'Get detailed current user profile for form auto-population',
                    'operationId' => 'getExtendedProfile',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Extended profile retrieved successfully']
                    ]
                ],
                'put' => [
                    'tags' => ['Profile'],
                    'summary' => 'Update User Profile',
                    'description' => 'Update current user profile information',
                    'operationId' => 'updateProfile',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'phone' => ['type' => 'string'],
                                        'staff_name' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Profile updated successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Utility API paths (health, notifications, etc.)
     */
    private function getUtilityPaths()
    {
        return [
            '/health' => [
                'get' => [
                    'tags' => ['Utility'],
                    'summary' => 'API Health Check',
                    'description' => 'Check API health status',
                    'operationId' => 'healthCheck',
                    'responses' => [
                        '200' => [
                            'description' => 'API is healthy',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'string', 'example' => 'ok'],
                                            'timestamp' => ['type' => 'string', 'format' => 'date-time']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/health/detailed' => [
                'get' => [
                    'tags' => ['Utility'],
                    'summary' => 'Detailed Health Check',
                    'description' => 'Get detailed API health status including database connection',
                    'operationId' => 'detailedHealthCheck',
                    'responses' => [
                        '200' => [
                            'description' => 'Detailed health information',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'string'],
                                            'database' => ['type' => 'object'],
                                            'environment' => ['type' => 'string'],
                                            'php_version' => ['type' => 'string'],
                                            'laravel_version' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/notifications' => [
                'get' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Get User Notifications',
                    'description' => 'Retrieve user notifications',
                    'operationId' => 'getNotifications',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Notifications retrieved successfully']
                    ]
                ]
            ],
            '/notifications/unread-count' => [
                'get' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Get Unread Notifications Count',
                    'description' => 'Get count of unread notifications for current user',
                    'operationId' => 'getUnreadCount',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Unread count retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'unread_count' => ['type' => 'integer', 'example' => 5]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * ICT Officer API paths
     */
    private function getIctOfficerPaths()
    {
        return [
            '/ict-officer/dashboard' => [
                'get' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Get ICT Officer Dashboard',
                    'description' => 'Retrieve dashboard data for ICT Officer with task statistics and assignments',
                    'operationId' => 'getIctOfficerDashboard',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Dashboard data retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'officer_info' => ['type' => 'object'],
                                                    'task_counts' => ['type' => 'object'],
                                                    'recent_assignments' => ['type' => 'array']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '403' => ['description' => 'Unauthorized - ICT Officer access required']
                    ]
                ]
            ],
            '/ict-officer/access-requests' => [
                'get' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Get Access Requests for ICT Implementation',
                    'description' => 'Retrieve access requests approved by Head of IT and available for ICT Officer implementation',
                    'operationId' => 'getIctAccessRequests',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by implementation status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['unassigned', 'assigned_to_ict', 'implementation_in_progress', 'completed']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Access requests retrieved successfully']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}' => [
                'get' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Get Access Request Details',
                    'description' => 'Get detailed information about a specific access request',
                    'operationId' => 'getAccessRequestById',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Access request details retrieved successfully'],
                        '404' => ['description' => 'Access request not found']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}/assign' => [
                'post' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Assign Access Request to Self',
                    'description' => 'ICT Officer takes ownership of an access request implementation',
                    'operationId' => 'assignAccessRequestToSelf',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'notes' => ['type' => 'string', 'maxLength' => 500, 'example' => 'Task self-assigned by ICT Officer']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Access request assigned successfully'],
                        '400' => ['description' => 'Request already assigned or invalid state']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}/progress' => [
                'put' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Update Access Request Progress',
                    'description' => 'Update implementation progress on an assigned access request',
                    'operationId' => 'updateAccessRequestProgress',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['status'],
                                    'properties' => [
                                        'status' => [
                                            'type' => 'string',
                                            'enum' => ['implementation_in_progress', 'completed'],
                                            'example' => 'implementation_in_progress'
                                        ],
                                        'notes' => ['type' => 'string', 'maxLength' => 1000, 'example' => 'Implementation started successfully']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Progress updated successfully'],
                        '400' => ['description' => 'Invalid status or request state']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}/timeline' => [
                'get' => [
                    'tags' => ['ICT Officer'],
                    'summary' => 'Get Access Request Timeline',
                    'description' => 'Get detailed timeline and history for an access request',
                    'operationId' => 'getAccessRequestTimeline',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Timeline retrieved successfully'],
                        '404' => ['description' => 'Access request not found']
                    ]
                ]
            ]
        ];
    }

    /**
     * Head of IT API paths
     */
    private function getHeadOfItPaths()
    {
        return [
            '/head-of-it/all-requests' => [
                'get' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Get All Requests for Head of IT',
                    'description' => 'Retrieve all requests that have reached Head of IT approval stage',
                    'operationId' => 'getHeadOfItAllRequests',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by approval status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['pending', 'approved', 'rejected']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Requests retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - Head of IT access required']
                    ]
                ]
            ],
            '/head-of-it/requests/{id}/approve' => [
                'post' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Approve Access Request',
                    'description' => 'Approve an access request as Head of IT',
                    'operationId' => 'approveAccessRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'comments' => ['type' => 'string', 'example' => 'Request approved for implementation'],
                                        'signature' => ['type' => 'string', 'format' => 'binary']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Request approved successfully'],
                        '400' => ['description' => 'Invalid request state or data']
                    ]
                ]
            ],
            '/head-of-it/ict-officers' => [
                'get' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Get Available ICT Officers',
                    'description' => 'Retrieve list of available ICT Officers for task assignment',
                    'operationId' => 'getAvailableIctOfficers',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'ICT Officers list retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'id' => ['type' => 'integer'],
                                                        'name' => ['type' => 'string'],
                                                        'email' => ['type' => 'string'],
                                                        'workload' => ['type' => 'integer'],
                                                        'availability_status' => ['type' => 'string']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * User Access Workflow API paths
     */
    private function getUserAccessWorkflowPaths()
    {
        return [
            '/user-access-workflow' => [
                'get' => [
                    'tags' => ['User Access Workflow'],
                    'summary' => 'Get User Access Workflow Requests',
                    'description' => 'Retrieve paginated list of user access workflow requests',
                    'operationId' => 'getUserAccessWorkflowRequests',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter by workflow status',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['draft', 'hod_pending', 'divisional_pending', 'ict_director_pending', 'head_it_pending', 'implementation_pending', 'completed']
                            ]
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Items per page',
                            'schema' => ['type' => 'integer', 'default' => 15]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Workflow requests retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['User Access Workflow'],
                    'summary' => 'Create User Access Workflow Request',
                    'description' => 'Submit new user access workflow request with complete approval chain',
                    'operationId' => 'createUserAccessWorkflowRequest',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['staff_name', 'pf_number', 'department_id', 'request_type'],
                                    'properties' => [
                                        'staff_name' => ['type' => 'string', 'example' => 'John Doe'],
                                        'pf_number' => ['type' => 'string', 'example' => 'PF12345'],
                                        'phone_number' => ['type' => 'string'],
                                        'department_id' => ['type' => 'integer'],
                                        'request_type' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                                'enum' => ['wellsoft_access', 'jeeva_access', 'internet_access_request']
                                            ]
                                        ],
                                        'access_type' => ['type' => 'string', 'enum' => ['permanent', 'temporary']],
                                        'temporary_until' => ['type' => 'string', 'format' => 'date'],
                                        'internet_purposes' => ['type' => 'string'],
                                        'wellsoft_modules_selected' => ['type' => 'array'],
                                        'jeeva_modules_selected' => ['type' => 'array'],
                                        'signature' => ['type' => 'string', 'format' => 'binary']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Workflow request created successfully'],
                        '422' => ['description' => 'Validation errors']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/approve/hod' => [
                'post' => [
                    'tags' => ['User Access Workflow'],
                    'summary' => 'HOD Approval',
                    'description' => 'Process Head of Department approval for access request',
                    'operationId' => 'processHodApproval',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['action'],
                                    'properties' => [
                                        'action' => ['type' => 'string', 'enum' => ['approve', 'reject']],
                                        'comments' => ['type' => 'string'],
                                        'signature' => ['type' => 'string', 'format' => 'binary']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD approval processed successfully'],
                        '403' => ['description' => 'Unauthorized - HOD access required']
                    ]
                ]
            ]
        ];
    }

    /**
     * Module Request API paths
     */
    private function getModuleRequestPaths()
    {
        return [
            '/module-requests' => [
                'post' => [
                    'tags' => ['Module Requests'],
                    'summary' => 'Create Module Access Request',
                    'description' => 'Submit request for access to specific Wellsoft or Jeeva modules',
                    'operationId' => 'createModuleRequest',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['user_access_id', 'module_type', 'modules'],
                                    'properties' => [
                                        'user_access_id' => ['type' => 'integer', 'example' => 1],
                                        'module_type' => ['type' => 'string', 'enum' => ['wellsoft', 'jeeva'], 'example' => 'wellsoft'],
                                        'modules' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string'],
                                            'example' => ['Patient Registration', 'Billing']
                                        ],
                                        'justification' => ['type' => 'string', 'example' => 'Required for patient management duties']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Module request created successfully'],
                        '422' => ['description' => 'Validation errors']
                    ]
                ]
            ],
            '/module-requests/modules' => [
                'get' => [
                    'tags' => ['Module Requests'],
                    'summary' => 'Get Available Modules',
                    'description' => 'Retrieve list of available modules for Wellsoft and Jeeva systems',
                    'operationId' => 'getAvailableModules',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'type',
                            'in' => 'query',
                            'description' => 'Filter by module type',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['wellsoft', 'jeeva']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Available modules retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'wellsoft_modules' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string']
                                            ],
                                            'jeeva_modules' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Notification API paths
     */
    private function getNotificationPaths()
    {
        return [
            '/notifications/pending-count' => [
                'get' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Get Pending Requests Count',
                    'description' => 'Get count of pending requests for current user based on their role',
                    'operationId' => 'getPendingRequestsCount',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Pending count retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'total_pending' => ['type' => 'integer', 'example' => 5],
                                                    'by_status' => ['type' => 'object'],
                                                    'requires_attention' => ['type' => 'boolean']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/notifications/breakdown' => [
                'get' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Get Detailed Notification Breakdown',
                    'description' => 'Get detailed breakdown of pending requests across all roles (Admin only)',
                    'operationId' => 'getPendingRequestsBreakdown',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Notification breakdown retrieved successfully'],
                        '403' => ['description' => 'Unauthorized - Admin access required']
                    ]
                ]
            ]
        ];
    }

    /**
     * Onboarding API paths
     */
    private function getOnboardingPaths()
    {
        return [
            '/onboarding/status' => [
                'get' => [
                    'tags' => ['Onboarding'],
                    'summary' => 'Get Onboarding Status',
                    'description' => 'Check current user onboarding status and progress',
                    'operationId' => 'getOnboardingStatus',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Onboarding status retrieved successfully']]
                ]
            ],
            '/onboarding/accept-terms' => [
                'post' => [
                    'tags' => ['Onboarding'],
                    'summary' => 'Accept Terms and Conditions',
                    'description' => 'Accept platform terms and conditions',
                    'operationId' => 'acceptTerms',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Terms accepted successfully']]
                ]
            ],
            '/onboarding/accept-ict-policy' => [
                'post' => [
                    'tags' => ['Onboarding'],
                    'summary' => 'Accept ICT Policy',
                    'description' => 'Accept ICT usage policy',
                    'operationId' => 'acceptIctPolicy',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'ICT policy accepted successfully']]
                ]
            ],
            '/onboarding/complete' => [
                'post' => [
                    'tags' => ['Onboarding'],
                    'summary' => 'Complete Onboarding',
                    'description' => 'Mark onboarding process as complete',
                    'operationId' => 'completeOnboarding',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Onboarding completed successfully']]
                ]
            ],
            '/onboarding/reset' => [
                'post' => [
                    'tags' => ['Onboarding'],
                    'summary' => 'Reset Onboarding',
                    'description' => 'Reset user onboarding status (Admin only)',
                    'operationId' => 'resetOnboarding',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Onboarding reset successfully']]
                ]
            ]
        ];
    }

    /**
     * Declaration API paths
     */
    private function getDeclarationPaths()
    {
        return [
            '/declaration/departments' => [
                'get' => [
                    'tags' => ['Declaration'],
                    'summary' => 'Get Departments for Declaration',
                    'description' => 'Get list of departments for declaration form',
                    'operationId' => 'getDeclarationDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Departments retrieved successfully']]
                ]
            ],
            '/declaration/submit' => [
                'post' => [
                    'tags' => ['Declaration'],
                    'summary' => 'Submit Declaration',
                    'description' => 'Submit user declaration form',
                    'operationId' => 'submitDeclaration',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['pf_number', 'department_id'],
                                    'properties' => [
                                        'pf_number' => ['type' => 'string'],
                                        'department_id' => ['type' => 'integer'],
                                        'additional_info' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['201' => ['description' => 'Declaration submitted successfully']]
                ]
            ],
            '/declaration/show' => [
                'get' => [
                    'tags' => ['Declaration'],
                    'summary' => 'Show User Declaration',
                    'description' => 'Get current user declaration',
                    'operationId' => 'showDeclaration',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Declaration retrieved successfully']]
                ]
            ],
            '/declaration/check-pf-number' => [
                'post' => [
                    'tags' => ['Declaration'],
                    'summary' => 'Check PF Number',
                    'description' => 'Validate PF number uniqueness',
                    'operationId' => 'checkPfNumber',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'PF number validation completed']]
                ]
            ],
            '/declaration/all' => [
                'get' => [
                    'tags' => ['Declaration'],
                    'summary' => 'List All Declarations (Admin)',
                    'description' => 'Get all user declarations (Admin only)',
                    'operationId' => 'getAllDeclarations',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'All declarations retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * User Role Management API paths
     */
    private function getUserRolePaths()
    {
        return [
            '/user-roles' => [
                'get' => [
                    'tags' => ['User Roles'],
                    'summary' => 'List User Roles',
                    'description' => 'Get paginated list of users with roles (Admin only)',
                    'operationId' => 'getUserRoles',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User roles retrieved successfully']]
                ]
            ],
            '/user-roles/statistics' => [
                'get' => [
                    'tags' => ['User Roles'],
                    'summary' => 'Role Statistics',
                    'description' => 'Get role distribution statistics',
                    'operationId' => 'getRoleStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Role statistics retrieved successfully']]
                ]
            ],
            '/user-roles/{user}/assign' => [
                'post' => [
                    'tags' => ['User Roles'],
                    'summary' => 'Assign Roles to User',
                    'description' => 'Assign multiple roles to a user (Admin only)',
                    'operationId' => 'assignRoles',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Roles assigned successfully']]
                ]
            ],
            '/user-roles/{user}/roles/{role}' => [
                'delete' => [
                    'tags' => ['User Roles'],
                    'summary' => 'Remove Role from User',
                    'description' => 'Remove specific role from user (Admin only)',
                    'operationId' => 'removeRole',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ['name' => 'role', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Role removed successfully']]
                ]
            ],
            '/user-roles/{user}/history' => [
                'get' => [
                    'tags' => ['User Roles'],
                    'summary' => 'Role Assignment History',
                    'description' => 'Get role assignment history for user',
                    'operationId' => 'getRoleHistory',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Role history retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * Department HOD Management API paths
     */
    private function getDepartmentHodPaths()
    {
        return [
            '/department-hod' => [
                'get' => [
                    'tags' => ['Department HOD'],
                    'summary' => 'List HOD Assignments',
                    'description' => 'Get all department HOD assignments (Admin only)',
                    'operationId' => 'getHodAssignments',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'HOD assignments retrieved successfully']]
                ]
            ],
            '/department-hod/eligible-hods' => [
                'get' => [
                    'tags' => ['Department HOD'],
                    'summary' => 'Get Eligible HODs',
                    'description' => 'Get users eligible for HOD role',
                    'operationId' => 'getEligibleHods',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Eligible HODs retrieved successfully']]
                ]
            ],
            '/department-hod/statistics' => [
                'get' => [
                    'tags' => ['Department HOD'],
                    'summary' => 'HOD Statistics',
                    'description' => 'Get HOD assignment statistics',
                    'operationId' => 'getHodStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'HOD statistics retrieved successfully']]
                ]
            ],
            '/department-hod/{department}/assign' => [
                'post' => [
                    'tags' => ['Department HOD'],
                    'summary' => 'Assign HOD to Department',
                    'description' => 'Assign Head of Department to department',
                    'operationId' => 'assignHod',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'HOD assigned successfully']]
                ]
            ],
            '/department-hod/{department}/remove' => [
                'delete' => [
                    'tags' => ['Department HOD'],
                    'summary' => 'Remove HOD from Department',
                    'description' => 'Remove HOD assignment from department',
                    'operationId' => 'removeHod',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'HOD removed successfully']]
                ]
            ]
        ];
    }

    /**
     * Request Status API paths
     */
    private function getRequestStatusPaths()
    {
        return [
            '/request-status' => [
                'get' => [
                    'tags' => ['Request Status'],
                    'summary' => 'List Request Statuses',
                    'description' => 'Get all request statuses for current user',
                    'operationId' => 'getRequestStatuses',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']]
                    ],
                    'responses' => ['200' => ['description' => 'Request statuses retrieved successfully']]
                ]
            ],
            '/request-status/details' => [
                'get' => [
                    'tags' => ['Request Status'],
                    'summary' => 'Get Status Details',
                    'description' => 'Get detailed status information',
                    'operationId' => 'getStatusDetails',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Status details retrieved successfully']]
                ]
            ],
            '/request-status/statistics' => [
                'get' => [
                    'tags' => ['Request Status'],
                    'summary' => 'Status Statistics',
                    'description' => 'Get request status statistics',
                    'operationId' => 'getStatusStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Status statistics retrieved successfully']]
                ]
            ],
            '/request-status/types' => [
                'get' => [
                    'tags' => ['Request Status'],
                    'summary' => 'Get Request Types',
                    'description' => 'Get available request types',
                    'operationId' => 'getRequestTypes',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Request types retrieved successfully']]
                ]
            ],
            '/request-status/statuses' => [
                'get' => [
                    'tags' => ['Request Status'],
                    'summary' => 'Get Status Options',
                    'description' => 'Get available status options',
                    'operationId' => 'getStatusOptions',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Status options retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * User Dashboard API paths
     */
    private function getUserDashboardPaths()
    {
        return [
            '/user/dashboard-stats' => [
                'get' => [
                    'tags' => ['Dashboard'],
                    'summary' => 'Get User Dashboard Stats',
                    'description' => 'Get dashboard statistics for current user',
                    'operationId' => 'getUserDashboardStats',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Dashboard stats retrieved successfully']]
                ]
            ],
            '/user/request-status-breakdown' => [
                'get' => [
                    'tags' => ['Dashboard'],
                    'summary' => 'Get Request Status Breakdown',
                    'description' => 'Get breakdown of request statuses',
                    'operationId' => 'getRequestStatusBreakdown',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Request breakdown retrieved successfully']]
                ]
            ],
            '/user/recent-activity' => [
                'get' => [
                    'tags' => ['Dashboard'],
                    'summary' => 'Get Recent Activity',
                    'description' => 'Get recent user activity and changes',
                    'operationId' => 'getRecentActivity',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Recent activity retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * Security Testing API paths
     */
    private function getSecurityTestPaths()
    {
        return [
            '/security-test/test' => [
                'get' => [
                    'tags' => ['Security Testing'],
                    'summary' => 'Basic Security Test',
                    'description' => 'Run basic security functionality test',
                    'operationId' => 'basicSecurityTest',
                    'responses' => ['200' => ['description' => 'Security test completed successfully']]
                ]
            ],
            '/security-test/health' => [
                'get' => [
                    'tags' => ['Security Testing'],
                    'summary' => 'Security Health Check',
                    'description' => 'Check security system health',
                    'operationId' => 'securityHealthCheck',
                    'responses' => ['200' => ['description' => 'Security health check completed']]
                ]
            ],
            '/security-test/rate-limit' => [
                'get' => [
                    'tags' => ['Security Testing'],
                    'summary' => 'Rate Limit Test',
                    'description' => 'Test API rate limiting functionality',
                    'operationId' => 'rateLimitTest',
                    'responses' => ['200' => ['description' => 'Rate limit test completed']]
                ]
            ],
            '/security-test/sanitization' => [
                'post' => [
                    'tags' => ['Security Testing'],
                    'summary' => 'Input Sanitization Test',
                    'description' => 'Test input sanitization and validation',
                    'operationId' => 'sanitizationTest',
                    'responses' => ['200' => ['description' => 'Sanitization test completed']]
                ]
            ],
            '/security-test/xss' => [
                'post' => [
                    'tags' => ['Security Testing'],
                    'summary' => 'XSS Protection Test',
                    'description' => 'Test XSS protection mechanisms',
                    'operationId' => 'xssTest',
                    'responses' => ['200' => ['description' => 'XSS test completed']]
                ]
            ]
        ];
    }

    /**
     * Additional Admin API paths
     */
    private function getAdditionalAdminPaths()
    {
        return [
            '/admin/users/statistics' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'User Statistics',
                    'description' => 'Get comprehensive user statistics (Admin only)',
                    'operationId' => 'getUserStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User statistics retrieved successfully']]
                ]
            ],
            '/admin/users/create-form-data' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get User Creation Form Data',
                    'description' => 'Get data needed for user creation form',
                    'operationId' => 'getUserCreationFormData',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Form data retrieved successfully']]
                ]
            ],
            '/admin/users/validate' => [
                'post' => [
                    'tags' => ['Admin'],
                    'summary' => 'Validate User Data',
                    'description' => 'Validate user data before creation/update',
                    'operationId' => 'validateUserData',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User data validation completed']]
                ]
            ],
            '/admin/users/{user}/toggle-status' => [
                'patch' => [
                    'tags' => ['Admin'],
                    'summary' => 'Toggle User Status',
                    'description' => 'Toggle user active/inactive status',
                    'operationId' => 'toggleUserStatus',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'User status toggled successfully']]
                ]
            ],
            '/admin/departments/create-form-data' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get Department Creation Form Data',
                    'description' => 'Get data needed for department creation form',
                    'operationId' => 'getDepartmentCreationFormData',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Department form data retrieved successfully']]
                ]
            ],
            '/admin/departments/{department}' => [
                'get' => [
                    'tags' => ['Admin'],
                    'summary' => 'Get Department Details',
                    'description' => 'Get specific department details',
                    'operationId' => 'getDepartmentDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Department details retrieved successfully']]
                ],
                'put' => [
                    'tags' => ['Admin'],
                    'summary' => 'Update Department',
                    'description' => 'Update department information',
                    'operationId' => 'updateDepartment',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Department updated successfully']]
                ],
                'delete' => [
                    'tags' => ['Admin'],
                    'summary' => 'Delete Department',
                    'description' => 'Delete department (Admin only)',
                    'operationId' => 'deleteDepartment',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Department deleted successfully']]
                ]
            ],
            '/admin/departments/{department}/toggle-status' => [
                'patch' => [
                    'tags' => ['Admin'],
                    'summary' => 'Toggle Department Status',
                    'description' => 'Toggle department active/inactive status',
                    'operationId' => 'toggleDepartmentStatus',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'department', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Department status toggled successfully']]
                ]
            ]
        ];
    }

    /**
     * Access Rights API paths
     */
    private function getAccessRightsPaths()
    {
        return [
            '/access-rights-approval' => [
                'post' => [
                    'tags' => ['Access Rights'],
                    'summary' => 'Create Access Rights Approval',
                    'description' => 'Submit access rights approval request',
                    'operationId' => 'createAccessRightsApproval',
                    'security' => [['sanctum' => []]],
                    'responses' => ['201' => ['description' => 'Access rights approval created successfully']]
                ]
            ],
            '/access-rights-approval/{userAccessId}' => [
                'get' => [
                    'tags' => ['Access Rights'],
                    'summary' => 'Get Access Rights Details',
                    'description' => 'Get access rights approval details',
                    'operationId' => 'getAccessRights',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Access rights retrieved successfully']]
                ],
                'put' => [
                    'tags' => ['Access Rights'],
                    'summary' => 'Update Access Rights',
                    'description' => 'Update access rights approval',
                    'operationId' => 'updateAccessRights',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Access rights updated successfully']]
                ]
            ]
        ];
    }

    /**
     * Implementation Workflow API paths
     */
    private function getImplementationPaths()
    {
        return [
            '/implementation-workflow' => [
                'post' => [
                    'tags' => ['Implementation'],
                    'summary' => 'Create Implementation Workflow',
                    'description' => 'Create new implementation workflow',
                    'operationId' => 'createImplementationWorkflow',
                    'security' => [['sanctum' => []]],
                    'responses' => ['201' => ['description' => 'Implementation workflow created successfully']]
                ]
            ],
            '/implementation-workflow/{userAccessId}' => [
                'get' => [
                    'tags' => ['Implementation'],
                    'summary' => 'Get Implementation Details',
                    'description' => 'Get implementation workflow details',
                    'operationId' => 'getImplementation',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Implementation details retrieved successfully']]
                ],
                'put' => [
                    'tags' => ['Implementation'],
                    'summary' => 'Update Implementation',
                    'description' => 'Update implementation workflow',
                    'operationId' => 'updateImplementation',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Implementation updated successfully']]
                ]
            ]
        ];
    }

    /**
     * Divisional Workflow API paths
     */
    private function getDivisionalWorkflowPaths()
    {
        return [
            '/divisional/dict-recommendations' => [
                'get' => [
                    'tags' => ['Divisional Workflow'],
                    'summary' => 'Get DICT Recommendations',
                    'description' => 'Get DICT recommendations for divisional director',
                    'operationId' => 'getDictRecommendations',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'DICT recommendations retrieved successfully']]
                ]
            ],
            '/divisional/dict-recommendations/stats' => [
                'get' => [
                    'tags' => ['Divisional Workflow'],
                    'summary' => 'Get Recommendation Stats',
                    'description' => 'Get statistics for DICT recommendations',
                    'operationId' => 'getRecommendationStats',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Recommendation stats retrieved successfully']]
                ]
            ],
            '/divisional/dict-recommendations/{userAccessId}/details' => [
                'get' => [
                    'tags' => ['Divisional Workflow'],
                    'summary' => 'Get Recommendation Details',
                    'description' => 'Get detailed information about recommendation',
                    'operationId' => 'getRecommendationDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Recommendation details retrieved successfully']]
                ]
            ],
            '/divisional/dict-recommendations/{userAccessId}/respond' => [
                'post' => [
                    'tags' => ['Divisional Workflow'],
                    'summary' => 'Submit Response',
                    'description' => 'Submit divisional director response to recommendation',
                    'operationId' => 'submitDivisionalResponse',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Response submitted successfully']]
                ]
            ]
        ];
    }

    /**
     * DICT Workflow API paths
     */
    private function getDictWorkflowPaths()
    {
        return [
            '/head-of-it/dict-recommendations' => [
                'get' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Get DICT Recommendations for Head of IT',
                    'description' => 'Get DICT recommendations for head of IT review',
                    'operationId' => 'getHeadOfItDictRecommendations',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'DICT recommendations retrieved successfully']]
                ]
            ],
            '/head-of-it/dict-recommendations/stats' => [
                'get' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Get DICT Recommendation Stats',
                    'description' => 'Get statistics for DICT recommendations',
                    'operationId' => 'getHeadOfItRecommendationStats',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Recommendation stats retrieved successfully']]
                ]
            ],
            '/head-of-it/dict-recommendations/{userAccessId}/details' => [
                'get' => [
                    'tags' => ['Head of IT'],
                    'summary' => 'Get Head of IT Recommendation Details',
                    'description' => 'Get detailed recommendation information for head of IT',
                    'operationId' => 'getHeadOfItRecommendationDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Recommendation details retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * Both Service Form API paths
     */
    private function getBothServiceFormPaths()
    {
        return [
            '/both-service-form/table/data' => [
                'get' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Get Table Data',
                    'description' => 'Get table data for both service forms',
                    'operationId' => 'getBothServiceFormTableData',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Table data retrieved successfully']]
                ]
            ],
            '/both-service-form/{id}/hod-view' => [
                'get' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Get Form for HOD Review',
                    'description' => 'Get form details for HOD review and approval',
                    'operationId' => 'getFormForHOD',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Form retrieved for HOD review']]
                ]
            ],
            '/both-service-form/{id}/update' => [
                'post' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Update Form (Multipart)',
                    'description' => 'Update form with multipart data support',
                    'operationId' => 'updateBothServiceFormMultipart',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Form updated successfully']]
                ]
            ],
            '/both-service-form/user/info' => [
                'get' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Get User Information',
                    'description' => 'Get user information for form population',
                    'operationId' => 'getBothServiceFormUserInfo',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User information retrieved successfully']]
                ]
            ],
            '/both-service-form/departments/list' => [
                'get' => [
                    'tags' => ['Both Service Form'],
                    'summary' => 'Get Departments List',
                    'description' => 'Get departments list for form selection',
                    'operationId' => 'getBothServiceFormDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Departments list retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * Jeeva Module API paths
     */
    private function getJeevaModulePaths()
    {
        return [
            '/module-requests/jeeva/modules' => [
                'get' => [
                    'tags' => ['Module Requests'],
                    'summary' => 'Get Available Jeeva Modules',
                    'description' => 'Retrieve list of available Jeeva system modules',
                    'operationId' => 'getAvailableJeevaModules',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Jeeva modules retrieved successfully']]
                ]
            ],
            '/module-requests/jeeva/{userAccessId}' => [
                'get' => [
                    'tags' => ['Module Requests'],
                    'summary' => 'Get Jeeva Module Request',
                    'description' => 'Get details of specific Jeeva module request',
                    'operationId' => 'getJeevaModuleRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Jeeva module request retrieved successfully']]
                ],
                'put' => [
                    'tags' => ['Module Requests'],
                    'summary' => 'Update Jeeva Module Request',
                    'description' => 'Update existing Jeeva module request',
                    'operationId' => 'updateJeevaModuleRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'userAccessId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                    ],
                    'responses' => ['200' => ['description' => 'Jeeva module request updated successfully']]
                ]
            ]
        ];
    }

    /**
     * Generate interactive Swagger UI with testing capabilities
     */
    private function generateBasicSwaggerUI()
    {
        $apiDocsUrl = url('/api/api-docs');
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MNH API Documentation - Complete (265+ Endpoints)</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    
    <style>
        html {
            box-sizing: border-box;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        
        /* Modern Header */
        .modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .modern-header h1 {
            margin: 0;
            font-size: 2.25rem;
            font-weight: 700;
        }
        .modern-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Swagger UI Overrides */
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 20px 0; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .swagger-ui .scheme-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 20px 0; }
        
        .download-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #ff6c37;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
        }
        .download-btn:hover {
            background: #e55a2b;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="modern-header">
        <div class="container">
            <h1>🚀 MNH API Documentation</h1>
            <p>Comprehensive Documentation for 265+ Endpoints</p>
        </div>
    </div>
    
    <div class="container">
        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>API Server:</strong> <code style="color: #3b82f6;">' . rtrim(config('app.url'), '/') . '/api' . '</code>
            </div>
            <div>
                <a href="' . url('/api/api-docs') . '" target="_blank" class="download-btn">View JSON</a>
                <a href="' . url('/api/postman-collection') . '" target="_blank" class="download-btn" style="background: #0ea5e9;">Postman Collection</a>
            </div>
        </div>
        
        <div id="swagger-ui"></div>
    </div>

    <!-- Swagger UI Scripts -->
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "' . $apiDocsUrl . '",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                persistAuthorization: true,
                displayRequestDuration: true,
                filter: true,
                tryItOutEnabled: true
            });
            window.ui = ui;
        };
    </script>
</body>
</html>';
        
        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Session Management paths
     */
    private function getSessionManagementPaths()
    {
        return [
            '/sessions' => [
                'get' => [
                    'tags' => ['Session Management'],
                    'summary' => 'Get Active Sessions',
                    'description' => 'Retrieve list of active user sessions',
                    'operationId' => 'getActiveSessions',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Sessions retrieved successfully']
                    ]
                ]
            ],
            '/sessions/revoke' => [
                'post' => [
                    'tags' => ['Session Management'],
                    'summary' => 'Revoke Session',
                    'description' => 'Revoke a specific user session',
                    'operationId' => 'revokeSession',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Session revoked successfully']
                    ]
                ]
            ],
            '/current-user' => [
                'get' => [
                    'tags' => ['Session Management'],
                    'summary' => 'Get Current User',
                    'description' => 'Get current authenticated user details',
                    'operationId' => 'getCurrentUser',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Current user retrieved successfully']
                    ]
                ]
            ],
            '/role-redirect' => [
                'get' => [
                    'tags' => ['Session Management'],
                    'summary' => 'Get Role-Based Redirect',
                    'description' => 'Get redirect URL based on user role',
                    'operationId' => 'getRoleBasedRedirect',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Redirect URL retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended User Role Management paths
     */
    private function getExtendedUserRolePaths()
    {
        return [
            '/user-roles/departments' => [
                'get' => [
                    'tags' => ['User Role Management'],
                    'summary' => 'Get Departments for Role Management',
                    'description' => 'Get departments list for user role assignment',
                    'operationId' => 'getRoleManagementDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Departments retrieved successfully']
                    ]
                ]
            ],
            '/user-roles/create-user' => [
                'post' => [
                    'tags' => ['User Role Management'],
                    'summary' => 'Create User with Roles',
                    'description' => 'Create new user with assigned roles',
                    'operationId' => 'createUserWithRoles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'User created with roles successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended Department HOD Management paths
     */
    private function getExtendedDepartmentHodPaths()
    {
        return [
            '/department-hod/{department}/update' => [
                'put' => [
                    'tags' => ['Department HOD Management'],
                    'summary' => 'Update HOD Assignment',
                    'description' => 'Update HOD assignment for department',
                    'operationId' => 'updateHodAssignment',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'department',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD assignment updated successfully']
                    ]
                ]
            ],
            '/department-hod/{department}/details' => [
                'get' => [
                    'tags' => ['Department HOD Management'],
                    'summary' => 'Get HOD Details',
                    'description' => 'Get detailed information about department HOD',
                    'operationId' => 'getHodDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'department',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD details retrieved successfully']
                    ]
                ]
            ],
            '/department-hod/{department}/delete' => [
                'delete' => [
                    'tags' => ['Department HOD Management'],
                    'summary' => 'Delete HOD Assignment',
                    'description' => 'Delete HOD assignment for department',
                    'operationId' => 'deleteHodAssignment',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'department',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD assignment deleted successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Device Inventory Management paths
     */
    private function getDeviceInventoryPaths()
    {
        return [
            '/device-inventory' => [
                'get' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'List Device Inventory',
                    'description' => 'Get paginated list of device inventory',
                    'operationId' => 'getDeviceInventory',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Device inventory retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Create Device Inventory Item',
                    'description' => 'Add new device to inventory',
                    'operationId' => 'createDeviceInventoryItem',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Device inventory item created successfully']
                    ]
                ]
            ],
            '/device-inventory/available' => [
                'get' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Get Available Devices',
                    'description' => 'Get list of available devices for booking',
                    'operationId' => 'getAvailableDevices',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Available devices retrieved successfully']
                    ]
                ]
            ],
            '/device-inventory/statistics' => [
                'get' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Get Device Inventory Statistics',
                    'description' => 'Get statistics about device inventory',
                    'operationId' => 'getDeviceInventoryStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Device inventory statistics retrieved successfully']
                    ]
                ]
            ],
            '/device-inventory/fix-quantities' => [
                'post' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Fix Device Quantities',
                    'description' => 'Fix inconsistent device quantities',
                    'operationId' => 'fixDeviceQuantities',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Device quantities fixed successfully']
                    ]
                ]
            ],
            '/device-inventory/{deviceInventory}' => [
                'get' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Get Device Details',
                    'description' => 'Get specific device inventory details',
                    'operationId' => 'getDeviceInventoryDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'deviceInventory',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device inventory details retrieved successfully']
                    ]
                ],
                'put' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Update Device Inventory',
                    'description' => 'Update device inventory details',
                    'operationId' => 'updateDeviceInventory',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'deviceInventory',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device inventory updated successfully']
                    ]
                ],
                'delete' => [
                    'tags' => ['Device Inventory'],
                    'summary' => 'Delete Device Inventory',
                    'description' => 'Remove device from inventory',
                    'operationId' => 'deleteDeviceInventory',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'deviceInventory',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device inventory deleted successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended ICT Officer Management paths
     */
    private function getExtendedIctOfficerPaths()
    {
        return [
            '/ict-officer/tasks' => [
                'get' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Get Assigned Tasks',
                    'description' => 'Get list of tasks assigned to ICT officer',
                    'operationId' => 'getAssignedTasks',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Assigned tasks retrieved successfully']
                    ]
                ]
            ],
            '/ict-officer/tasks/{assignmentId}' => [
                'get' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Get Task Details',
                    'description' => 'Get specific task assignment details',
                    'operationId' => 'getTaskDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'assignmentId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Task details retrieved successfully']
                    ]
                ]
            ],
            '/ict-officer/tasks/{assignmentId}/start' => [
                'post' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Start Task',
                    'description' => 'Start working on assigned task',
                    'operationId' => 'startTask',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'assignmentId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Task started successfully']
                    ]
                ]
            ],
            '/ict-officer/tasks/{assignmentId}/complete' => [
                'post' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Complete Task',
                    'description' => 'Mark task as completed',
                    'operationId' => 'completeTask',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'assignmentId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Task completed successfully']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}/cancel' => [
                'post' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Cancel Access Request Task',
                    'description' => 'Cancel access request task assignment',
                    'operationId' => 'cancelAccessRequestTask',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Access request task cancelled successfully']
                    ]
                ]
            ],
            '/ict-officer/access-requests/{requestId}/grant-access' => [
                'post' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Grant Access',
                    'description' => 'Grant system access to user',
                    'operationId' => 'grantAccess',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Access granted successfully']
                    ]
                ]
            ],
            '/ict-officer/statistics' => [
                'get' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Get Task Statistics',
                    'description' => 'Get ICT officer task statistics',
                    'operationId' => 'getTaskStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Task statistics retrieved successfully']
                    ]
                ]
            ],
            '/ict-officer/pending-count' => [
                'get' => [
                    'tags' => ['ICT Officer Extended'],
                    'summary' => 'Get Pending Requests Count',
                    'description' => 'Get count of pending requests for ICT officer',
                    'operationId' => 'getPendingRequestsCount',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Pending requests count retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended User Access Workflow paths
     */
    private function getExtendedUserAccessWorkflowPaths()
    {
        return [
            '/user-access-workflow/options/form-data' => [
                'get' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Get Form Options',
                    'description' => 'Get form options for user access workflow',
                    'operationId' => 'getFormOptions',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Form options retrieved successfully']
                    ]
                ]
            ],
            '/user-access-workflow/statistics/dashboard' => [
                'get' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Get Workflow Statistics',
                    'description' => 'Get dashboard statistics for workflow',
                    'operationId' => 'getWorkflowStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Workflow statistics retrieved successfully']
                    ]
                ]
            ],
            '/user-access-workflow/export/requests' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Export Requests',
                    'description' => 'Export user access workflow requests',
                    'operationId' => 'exportRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Requests exported successfully']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/cancel' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Cancel Request',
                    'description' => 'Cancel user access workflow request',
                    'operationId' => 'cancelRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Request cancelled successfully']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/approve/divisional' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Divisional Director Approval',
                    'description' => 'Process divisional director approval for request',
                    'operationId' => 'processDivisionalApproval',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Divisional approval processed successfully']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/approve/ict-director' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'ICT Director Approval',
                    'description' => 'Process ICT director approval for request',
                    'operationId' => 'processIctDirectorApproval',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'ICT director approval processed successfully']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/approve/head-it' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'Head of IT Approval',
                    'description' => 'Process head of IT approval for request',
                    'operationId' => 'processHeadItApproval',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Head of IT approval processed successfully']
                    ]
                ]
            ],
            '/user-access-workflow/{userAccess}/implement/ict-officer' => [
                'post' => [
                    'tags' => ['User Access Workflow Extended'],
                    'summary' => 'ICT Officer Implementation',
                    'description' => 'Process ICT officer implementation',
                    'operationId' => 'processIctOfficerImplementation',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'userAccess',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'ICT officer implementation processed successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended Profile Management paths
     */
    private function getExtendedProfilePaths()
    {
        return [
            '/profile/lookup-pf' => [
                'post' => [
                    'tags' => ['Profile Extended'],
                    'summary' => 'Lookup User by PF Number',
                    'description' => 'Get user details by PF number',
                    'operationId' => 'getUserByPfNumber',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User details retrieved successfully']
                    ]
                ]
            ],
            '/profile/check-pf' => [
                'post' => [
                    'tags' => ['Profile Extended'],
                    'summary' => 'Check PF Number Exists',
                    'description' => 'Check if PF number already exists',
                    'operationId' => 'checkPfNumberExists',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'PF number check completed']
                    ]
                ]
            ],
            '/profile/departments' => [
                'get' => [
                    'tags' => ['Profile Extended'],
                    'summary' => 'Get Departments for Profile',
                    'description' => 'Get departments list for profile management',
                    'operationId' => 'getProfileDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Departments retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended Admin Routes
     */
    private function getExtendedAdminRoutes()
    {
        return [
            '/admin/users/roles' => [
                'get' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Get User Roles',
                    'description' => 'Get available user roles',
                    'operationId' => 'getUserRoles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User roles retrieved successfully']
                    ]
                ]
            ],
            '/admin/users/departments' => [
                'get' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Get User Departments',
                    'description' => 'Get departments for user management',
                    'operationId' => 'getUserDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User departments retrieved successfully']
                    ]
                ]
            ],
            '/admin/users/{user}' => [
                'get' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Get User Details',
                    'description' => 'Get specific user details',
                    'operationId' => 'getUserDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'User details retrieved successfully']
                    ]
                ],
                'put' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Update User',
                    'description' => 'Update user details',
                    'operationId' => 'updateUser',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'User updated successfully']
                    ]
                ],
                'delete' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Delete User',
                    'description' => 'Delete user account',
                    'operationId' => 'deleteUser',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'User deleted successfully']
                    ]
                ]
            ],
            '/admin/departments/eligible-hods' => [
                'get' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Get Eligible HODs',
                    'description' => 'Get users eligible for HOD role',
                    'operationId' => 'getEligibleHods',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Eligible HODs retrieved successfully']
                    ]
                ]
            ],
            '/admin/departments/eligible-divisional-directors' => [
                'get' => [
                    'tags' => ['Admin Extended'],
                    'summary' => 'Get Eligible Divisional Directors',
                    'description' => 'Get users eligible for divisional director role',
                    'operationId' => 'getEligibleDivisionalDirectors',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Eligible divisional directors retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Legacy HOD Routes for backward compatibility
     */
    private function getLegacyHodRoutes()
    {
        return [
            '/hod/combined-access-requests/statistics' => [
                'get' => [
                    'tags' => ['Legacy HOD'],
                    'summary' => 'HOD Request Statistics',
                    'description' => 'Get statistics for HOD access requests',
                    'operationId' => 'getHodRequestStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'HOD request statistics retrieved successfully']
                    ]
                ]
            ],
            '/hod/combined-access-requests/{id}' => [
                'get' => [
                    'tags' => ['Legacy HOD'],
                    'summary' => 'Get HOD Request Details',
                    'description' => 'Get specific HOD request details',
                    'operationId' => 'getHodRequestDetails',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD request details retrieved successfully']
                    ]
                ]
            ],
            '/hod/combined-access-requests/{id}/approve' => [
                'post' => [
                    'tags' => ['Legacy HOD'],
                    'summary' => 'Approve HOD Request',
                    'description' => 'Approve access request as HOD',
                    'operationId' => 'approveHodRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD request approved successfully']
                    ]
                ]
            ],
            '/hod/combined-access-requests/{id}/cancel' => [
                'post' => [
                    'tags' => ['Legacy HOD'],
                    'summary' => 'Cancel HOD Request',
                    'description' => 'Cancel access request as HOD',
                    'operationId' => 'cancelHodRequest',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD request cancelled successfully']
                    ]
                ]
            ],
            '/hod/combined-access-requests/{id}/timeline' => [
                'get' => [
                    'tags' => ['Legacy HOD'],
                    'summary' => 'Get HOD Request Timeline',
                    'description' => 'Get request timeline for HOD review',
                    'operationId' => 'getHodRequestTimeline',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'HOD request timeline retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended Booking Service paths
     */
    private function getExtendedBookingServicePaths()
    {
        return [
            '/booking-service/bookings/{bookingId}/edit-rejected' => [
                'put' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Edit Rejected Booking',
                    'description' => 'Edit rejected booking request',
                    'operationId' => 'editRejectedBooking',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'bookingId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Rejected booking updated successfully']
                    ]
                ]
            ],
            '/booking-service/device-types' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Get Device Types',
                    'description' => 'Get available device types for booking',
                    'operationId' => 'getDeviceTypes',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Device types retrieved successfully']
                    ]
                ]
            ],
            '/booking-service/departments' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Get Booking Departments',
                    'description' => 'Get departments for booking service',
                    'operationId' => 'getBookingDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Booking departments retrieved successfully']
                    ]
                ]
            ],
            '/booking-service/debug-departments' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Debug Departments',
                    'description' => 'Debug department configuration',
                    'operationId' => 'debugDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Department debug info retrieved successfully']
                    ]
                ]
            ],
            '/booking-service/debug-assessment-schema' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Debug Assessment Schema',
                    'description' => 'Debug device assessment schema',
                    'operationId' => 'debugAssessmentSchema',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Assessment schema debug info retrieved successfully']
                    ]
                ]
            ],
            '/booking-service/seed-departments' => [
                'post' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Seed Departments',
                    'description' => 'Seed department data for testing',
                    'operationId' => 'seedDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Departments seeded successfully']
                    ]
                ]
            ],
            '/booking-service/devices/{deviceInventoryId}/availability' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Check Device Availability',
                    'description' => 'Check availability of specific device',
                    'operationId' => 'checkDeviceAvailability',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'deviceInventoryId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device availability checked successfully']
                    ]
                ]
            ],
            '/booking-service/devices/{deviceInventoryId}/bookings' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Get Device Bookings',
                    'description' => 'Get bookings for specific device',
                    'operationId' => 'getDeviceBookings',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'deviceInventoryId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Device bookings retrieved successfully']
                    ]
                ]
            ],
            '/booking-service/check-pending-requests' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Check Pending Requests',
                    'description' => 'Check for pending booking requests',
                    'operationId' => 'checkPendingRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Pending requests checked successfully']
                    ]
                ]
            ],
            '/booking-service/can-submit-new-request' => [
                'get' => [
                    'tags' => ['Booking Service Extended'],
                    'summary' => 'Can Submit New Request',
                    'description' => 'Check if user can submit new booking request',
                    'operationId' => 'canSubmitNewRequest',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'New request eligibility checked successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended User Dashboard paths
     */
    private function getExtendedUserDashboardPaths()
    {
        return [
            '/request-status/debug' => [
                'get' => [
                    'tags' => ['User Dashboard Extended'],
                    'summary' => 'Debug Request Status',
                    'description' => 'Debug request status functionality',
                    'operationId' => 'debugRequestStatus',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Request status debug info retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Extended Notification paths
     */
    private function getExtendedNotificationPaths()
    {
        return [
            '/notifications/{id}/mark-read' => [
                'patch' => [
                    'tags' => ['Notifications Extended'],
                    'summary' => 'Mark Notification as Read',
                    'description' => 'Mark specific notification as read',
                    'operationId' => 'markNotificationAsRead',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Notification marked as read successfully']
                    ]
                ]
            ],
            '/notifications/mark-all-read' => [
                'patch' => [
                    'tags' => ['Notifications Extended'],
                    'summary' => 'Mark All Notifications as Read',
                    'description' => 'Mark all notifications as read',
                    'operationId' => 'markAllNotificationsAsRead',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'All notifications marked as read successfully']
                    ]
                ]
            ],
            '/notifications/{id}' => [
                'delete' => [
                    'tags' => ['Notifications Extended'],
                    'summary' => 'Delete Notification',
                    'description' => 'Delete specific notification',
                    'operationId' => 'deleteNotification',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Notification deleted successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Comprehensive Reporting and Analytics paths
     */
    private function getReportingAndAnalyticsPaths()
    {
        return [
            '/reports/user-access/summary' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'User Access Summary Report',
                    'description' => 'Get comprehensive user access summary report',
                    'operationId' => 'getUserAccessSummaryReport',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'date_from',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'format' => 'date']
                        ],
                        [
                            'name' => 'date_to',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'format' => 'date']
                        ],
                        [
                            'name' => 'department_id',
                            'in' => 'query',
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'User access summary report generated successfully']
                    ]
                ]
            ],
            '/reports/user-access/detailed' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Detailed User Access Report',
                    'description' => 'Get detailed user access report with breakdown',
                    'operationId' => 'getDetailedUserAccessReport',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Detailed user access report generated successfully']
                    ]
                ]
            ],
            '/reports/device-booking/utilization' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Device Utilization Report',
                    'description' => 'Get device booking utilization analytics',
                    'operationId' => 'getDeviceUtilizationReport',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Device utilization report generated successfully']
                    ]
                ]
            ],
            '/reports/department/performance' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Department Performance Report',
                    'description' => 'Get department performance analytics',
                    'operationId' => 'getDepartmentPerformanceReport',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Department performance report generated successfully']
                    ]
                ]
            ],
            '/analytics/user-access/trends' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'User Access Trends Analytics',
                    'description' => 'Get user access request trends and patterns',
                    'operationId' => 'getUserAccessTrends',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User access trends retrieved successfully']
                    ]
                ]
            ],
            '/analytics/workflow/bottlenecks' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Workflow Bottleneck Analysis',
                    'description' => 'Identify workflow bottlenecks and delays',
                    'operationId' => 'getWorkflowBottlenecks',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Workflow bottleneck analysis completed successfully']
                    ]
                ]
            ],
            '/analytics/system/performance' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'System Performance Analytics',
                    'description' => 'Get system performance metrics and analytics',
                    'operationId' => 'getSystemPerformanceAnalytics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'System performance analytics retrieved successfully']
                    ]
                ]
            ],
            '/reports/module-usage/statistics' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Module Usage Statistics',
                    'description' => 'Get Wellsoft and Jeeva module usage statistics',
                    'operationId' => 'getModuleUsageStatistics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Module usage statistics retrieved successfully']
                    ]
                ]
            ],
            '/reports/custom/generate' => [
                'post' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Generate Custom Report',
                    'description' => 'Generate custom report based on criteria',
                    'operationId' => 'generateCustomReport',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Custom report generated successfully']
                    ]
                ]
            ],
            '/reports/scheduled/list' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'List Scheduled Reports',
                    'description' => 'Get list of scheduled reports',
                    'operationId' => 'getScheduledReports',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Scheduled reports retrieved successfully']
                    ]
                ]
            ],
            '/reports/scheduled/create' => [
                'post' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Create Scheduled Report',
                    'description' => 'Create new scheduled report',
                    'operationId' => 'createScheduledReport',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Scheduled report created successfully']
                    ]
                ]
            ],
            '/analytics/dashboard/metrics' => [
                'get' => [
                    'tags' => ['Reporting & Analytics'],
                    'summary' => 'Dashboard Metrics',
                    'description' => 'Get comprehensive dashboard metrics',
                    'operationId' => 'getDashboardMetrics',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Dashboard metrics retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Advanced Search and Filtering paths
     */
    private function getAdvancedSearchAndFilteringPaths()
    {
        return [
            '/search/users/advanced' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Advanced User Search',
                    'description' => 'Perform advanced user search with multiple criteria',
                    'operationId' => 'advancedUserSearch',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Advanced user search completed successfully']
                    ]
                ]
            ],
            '/search/requests/global' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Global Request Search',
                    'description' => 'Search across all request types globally',
                    'operationId' => 'globalRequestSearch',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Global request search completed successfully']
                    ]
                ]
            ],
            '/filter/user-access/complex' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Complex User Access Filtering',
                    'description' => 'Apply complex filters to user access requests',
                    'operationId' => 'complexUserAccessFilter',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Complex filtering applied successfully']
                    ]
                ]
            ],
            '/search/departments/hierarchical' => [
                'get' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Hierarchical Department Search',
                    'description' => 'Search departments with hierarchical structure',
                    'operationId' => 'hierarchicalDepartmentSearch',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Hierarchical department search completed successfully']
                    ]
                ]
            ],
            '/filter/bookings/date-range' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Date Range Booking Filter',
                    'description' => 'Filter bookings by complex date ranges',
                    'operationId' => 'dateRangeBookingFilter',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Date range filtering applied successfully']
                    ]
                ]
            ],
            '/search/full-text' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Full-Text Search',
                    'description' => 'Perform full-text search across all content',
                    'operationId' => 'fullTextSearch',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Full-text search completed successfully']
                    ]
                ]
            ],
            '/filter/saved/list' => [
                'get' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'List Saved Filters',
                    'description' => 'Get list of user-saved filters',
                    'operationId' => 'getSavedFilters',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Saved filters retrieved successfully']
                    ]
                ]
            ],
            '/filter/saved/create' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Save Filter',
                    'description' => 'Save current filter configuration',
                    'operationId' => 'saveFilter',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Filter saved successfully']
                    ]
                ]
            ],
            '/filter/saved/{filterId}/apply' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Apply Saved Filter',
                    'description' => 'Apply a previously saved filter',
                    'operationId' => 'applySavedFilter',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'filterId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Saved filter applied successfully']
                    ]
                ]
            ],
            '/search/suggestions' => [
                'get' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Search Suggestions',
                    'description' => 'Get search suggestions and auto-complete',
                    'operationId' => 'getSearchSuggestions',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'query',
                            'in' => 'query',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Search suggestions retrieved successfully']
                    ]
                ]
            ],
            '/sort/multi-column' => [
                'post' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Multi-Column Sorting',
                    'description' => 'Apply multi-column sorting to results',
                    'operationId' => 'multiColumnSort',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Multi-column sorting applied successfully']
                    ]
                ]
            ],
            '/pagination/advanced' => [
                'get' => [
                    'tags' => ['Advanced Search & Filtering'],
                    'summary' => 'Advanced Pagination',
                    'description' => 'Get advanced pagination with cursor-based navigation',
                    'operationId' => 'advancedPagination',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Advanced pagination applied successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Bulk Operations and Batch Processing paths
     */
    private function getBulkOperationsAndBatchPaths()
    {
        return [
            '/bulk/users/create' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk User Creation',
                    'description' => 'Create multiple users in a single batch operation',
                    'operationId' => 'bulkCreateUsers',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Bulk user creation completed successfully']
                    ]
                ]
            ],
            '/bulk/users/update' => [
                'put' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk User Update',
                    'description' => 'Update multiple users in a single operation',
                    'operationId' => 'bulkUpdateUsers',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Bulk user update completed successfully']
                    ]
                ]
            ],
            '/bulk/users/delete' => [
                'delete' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk User Deletion',
                    'description' => 'Delete multiple users in a single operation',
                    'operationId' => 'bulkDeleteUsers',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Bulk user deletion completed successfully']
                    ]
                ]
            ],
            '/batch/requests/approve' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Batch Request Approval',
                    'description' => 'Approve multiple requests in batch',
                    'operationId' => 'batchApproveRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch request approval completed successfully']
                    ]
                ]
            ],
            '/batch/requests/reject' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Batch Request Rejection',
                    'description' => 'Reject multiple requests in batch',
                    'operationId' => 'batchRejectRequests',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch request rejection completed successfully']
                    ]
                ]
            ],
            '/batch/notifications/send' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Batch Send Notifications',
                    'description' => 'Send notifications to multiple users in batch',
                    'operationId' => 'batchSendNotifications',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch notifications sent successfully']
                    ]
                ]
            ],
            '/bulk/roles/assign' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk Role Assignment',
                    'description' => 'Assign roles to multiple users in bulk',
                    'operationId' => 'bulkAssignRoles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Bulk role assignment completed successfully']
                    ]
                ]
            ],
            '/batch/bookings/cancel' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Batch Cancel Bookings',
                    'description' => 'Cancel multiple bookings in batch',
                    'operationId' => 'batchCancelBookings',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch booking cancellation completed successfully']
                    ]
                ]
            ],
            '/bulk/departments/sync' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk Department Sync',
                    'description' => 'Synchronize department data in bulk',
                    'operationId' => 'bulkSyncDepartments',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Bulk department sync completed successfully']
                    ]
                ]
            ],
            '/batch/status/jobs' => [
                'get' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Get Batch Job Status',
                    'description' => 'Get status of batch processing jobs',
                    'operationId' => 'getBatchJobStatus',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch job status retrieved successfully']
                    ]
                ]
            ],
            '/batch/queue/list' => [
                'get' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'List Batch Queue',
                    'description' => 'List queued batch operations',
                    'operationId' => 'listBatchQueue',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Batch queue list retrieved successfully']
                    ]
                ]
            ],
            '/bulk/validation/preview' => [
                'post' => [
                    'tags' => ['Bulk Operations & Batch Processing'],
                    'summary' => 'Bulk Operation Preview',
                    'description' => 'Preview bulk operation before execution',
                    'operationId' => 'bulkOperationPreview',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Bulk operation preview generated successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Audit and Logging paths
     */
    private function getAuditAndLoggingPaths()
    {
        return [
            '/audit/logs/user-activities' => [
                'get' => [
                    'tags' => ['Audit & Logging'],
                    'summary' => 'Get User Activity Logs',
                    'description' => 'Retrieve comprehensive user activity audit logs',
                    'operationId' => 'getUserActivityLogs',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User activity logs retrieved successfully']
                    ]
                ]
            ],
            '/audit/logs/system-events' => [
                'get' => [
                    'tags' => ['Audit & Logging'],
                    'summary' => 'Get System Event Logs',
                    'description' => 'Retrieve system event audit logs',
                    'operationId' => 'getSystemEventLogs',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'System event logs retrieved successfully']
                    ]
                ]
            ],
            '/audit/trails/request-workflow' => [
                'get' => [
                    'tags' => ['Audit & Logging'],
                    'summary' => 'Get Request Workflow Audit Trail',
                    'description' => 'Get detailed audit trail for request workflows',
                    'operationId' => 'getRequestWorkflowAuditTrail',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Request workflow audit trail retrieved successfully']
                    ]
                ]
            ],
            '/logs/security/access-attempts' => [
                'get' => [
                    'tags' => ['Audit & Logging'],
                    'summary' => 'Get Security Access Attempt Logs',
                    'description' => 'Retrieve security access attempt logs',
                    'operationId' => 'getSecurityAccessLogs',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Security access logs retrieved successfully']
                    ]
                ]
            ],
            '/logs/data-changes/tracking' => [
                'get' => [
                    'tags' => ['Audit & Logging'],
                    'summary' => 'Get Data Change Tracking Logs',
                    'description' => 'Track all data changes with before/after values',
                    'operationId' => 'getDataChangeTrackingLogs',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Data change tracking logs retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Data Export/Import paths
     */
    private function getDataExportImportPaths()
    {
        return [
            '/export/users/csv' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Export Users to CSV',
                    'description' => 'Export user data to CSV format',
                    'operationId' => 'exportUsersCSV',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Users exported to CSV successfully']
                    ]
                ]
            ],
            '/export/users/excel' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Export Users to Excel',
                    'description' => 'Export user data to Excel format',
                    'operationId' => 'exportUsersExcel',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Users exported to Excel successfully']
                    ]
                ]
            ],
            '/import/users/csv' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Import Users from CSV',
                    'description' => 'Import user data from CSV format',
                    'operationId' => 'importUsersCSV',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Users imported from CSV successfully']
                    ]
                ]
            ],
            '/export/reports/pdf' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Export Report to PDF',
                    'description' => 'Export generated report to PDF format',
                    'operationId' => 'exportReportPDF',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Report exported to PDF successfully']
                    ]
                ]
            ],
            '/backup/database/create' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Create Database Backup',
                    'description' => 'Create full database backup',
                    'operationId' => 'createDatabaseBackup',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Database backup created successfully']
                    ]
                ]
            ],
            '/backup/database/restore' => [
                'post' => [
                    'tags' => ['Data Export/Import'],
                    'summary' => 'Restore Database Backup',
                    'description' => 'Restore database from backup',
                    'operationId' => 'restoreDatabaseBackup',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Database restored from backup successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Advanced Workflow State Management paths
     */
    private function getAdvancedWorkflowStatePaths()
    {
        return [
            '/workflow/states/definitions' => [
                'get' => [
                    'tags' => ['Advanced Workflow Management'],
                    'summary' => 'Get Workflow State Definitions',
                    'description' => 'Get all workflow state definitions',
                    'operationId' => 'getWorkflowStateDefinitions',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Workflow state definitions retrieved successfully']
                    ]
                ]
            ],
            '/workflow/transitions/allowed' => [
                'get' => [
                    'tags' => ['Advanced Workflow Management'],
                    'summary' => 'Get Allowed Workflow Transitions',
                    'description' => 'Get allowed workflow transitions for current state',
                    'operationId' => 'getAllowedWorkflowTransitions',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Allowed workflow transitions retrieved successfully']
                    ]
                ]
            ],
            '/workflow/history/{requestId}' => [
                'get' => [
                    'tags' => ['Advanced Workflow Management'],
                    'summary' => 'Get Workflow History',
                    'description' => 'Get complete workflow history for a request',
                    'operationId' => 'getWorkflowHistory',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'requestId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Workflow history retrieved successfully']
                    ]
                ]
            ],
            '/workflow/rules/validation' => [
                'post' => [
                    'tags' => ['Advanced Workflow Management'],
                    'summary' => 'Validate Workflow Rules',
                    'description' => 'Validate workflow rules before transition',
                    'operationId' => 'validateWorkflowRules',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Workflow rules validated successfully']
                    ]
                ]
            ],
            '/workflow/escalation/policies' => [
                'get' => [
                    'tags' => ['Advanced Workflow Management'],
                    'summary' => 'Get Workflow Escalation Policies',
                    'description' => 'Get workflow escalation policies and rules',
                    'operationId' => 'getWorkflowEscalationPolicies',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Workflow escalation policies retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * System Configuration paths
     */
    private function getSystemConfigurationPaths()
    {
        return [
            '/config/system/settings' => [
                'get' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Get System Settings',
                    'description' => 'Get all system configuration settings',
                    'operationId' => 'getSystemSettings',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'System settings retrieved successfully']
                    ]
                ],
                'put' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Update System Settings',
                    'description' => 'Update system configuration settings',
                    'operationId' => 'updateSystemSettings',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'System settings updated successfully']
                    ]
                ]
            ],
            '/config/email/templates' => [
                'get' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Get Email Templates',
                    'description' => 'Get all email template configurations',
                    'operationId' => 'getEmailTemplates',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Email templates retrieved successfully']
                    ]
                ]
            ],
            '/config/notification/preferences' => [
                'get' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Get Notification Preferences',
                    'description' => 'Get system notification preferences',
                    'operationId' => 'getNotificationPreferences',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Notification preferences retrieved successfully']
                    ]
                ]
            ],
            '/config/security/policies' => [
                'get' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Get Security Policies',
                    'description' => 'Get system security policy configurations',
                    'operationId' => 'getSecurityPolicies',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Security policies retrieved successfully']
                    ]
                ]
            ],
            '/config/backup/settings' => [
                'get' => [
                    'tags' => ['System Configuration'],
                    'summary' => 'Get Backup Settings',
                    'description' => 'Get system backup configuration settings',
                    'operationId' => 'getBackupSettings',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Backup settings retrieved successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * File Management paths
     */
    private function getFileManagementPaths()
    {
        return [
            '/files/upload/documents' => [
                'post' => [
                    'tags' => ['File Management'],
                    'summary' => 'Upload Document Files',
                    'description' => 'Upload document files to the system',
                    'operationId' => 'uploadDocumentFiles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Document files uploaded successfully']
                    ]
                ]
            ],
            '/files/upload/images' => [
                'post' => [
                    'tags' => ['File Management'],
                    'summary' => 'Upload Image Files',
                    'description' => 'Upload image files to the system',
                    'operationId' => 'uploadImageFiles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Image files uploaded successfully']
                    ]
                ]
            ],
            '/files/download/{fileId}' => [
                'get' => [
                    'tags' => ['File Management'],
                    'summary' => 'Download File',
                    'description' => 'Download a specific file by ID',
                    'operationId' => 'downloadFile',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'fileId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'File downloaded successfully']
                    ]
                ]
            ],
            '/files/list/user' => [
                'get' => [
                    'tags' => ['File Management'],
                    'summary' => 'List User Files',
                    'description' => 'Get list of files uploaded by current user',
                    'operationId' => 'listUserFiles',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'User files listed successfully']
                    ]
                ]
            ],
            '/files/delete/{fileId}' => [
                'delete' => [
                    'tags' => ['File Management'],
                    'summary' => 'Delete File',
                    'description' => 'Delete a specific file by ID',
                    'operationId' => 'deleteFile',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'fileId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'File deleted successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Notification Templates paths
     */
    private function getNotificationTemplatesPaths()
    {
        return [
            '/notification-templates/email' => [
                'get' => [
                    'tags' => ['Notification Templates'],
                    'summary' => 'Get Email Templates',
                    'description' => 'Get all email notification templates',
                    'operationId' => 'getEmailNotificationTemplates',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Email templates retrieved successfully']
                    ]
                ],
                'post' => [
                    'tags' => ['Notification Templates'],
                    'summary' => 'Create Email Template',
                    'description' => 'Create new email notification template',
                    'operationId' => 'createEmailNotificationTemplate',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '201' => ['description' => 'Email template created successfully']
                    ]
                ]
            ],
            '/notification-templates/sms' => [
                'get' => [
                    'tags' => ['Notification Templates'],
                    'summary' => 'Get SMS Templates',
                    'description' => 'Get all SMS notification templates',
                    'operationId' => 'getSMSNotificationTemplates',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'SMS templates retrieved successfully']
                    ]
                ]
            ],
            '/notification-templates/{templateId}' => [
                'put' => [
                    'tags' => ['Notification Templates'],
                    'summary' => 'Update Notification Template',
                    'description' => 'Update existing notification template',
                    'operationId' => 'updateNotificationTemplate',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        [
                            'name' => 'templateId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer']
                        ]
                    ],
                    'responses' => [
                        '200' => ['description' => 'Notification template updated successfully']
                    ]
                ]
            ],
            '/notification-templates/preview' => [
                'post' => [
                    'tags' => ['Notification Templates'],
                    'summary' => 'Preview Notification Template',
                    'description' => 'Preview notification template with sample data',
                    'operationId' => 'previewNotificationTemplate',
                    'security' => [['sanctum' => []]],
                    'responses' => [
                        '200' => ['description' => 'Notification template previewed successfully']
                    ]
                ]
            ]
        ];
    }

    /**
     * Comprehensive CRUD Operations paths
     */
    private function getComprehensiveCRUDPaths()
    {
        return [
            // Add comprehensive CRUD for remaining entities
            '/entities/user-access-types' => [
                'get' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'List User Access Types',
                    'description' => 'Get complete list of all user access types with filtering and pagination',
                    'operationId' => 'listUserAccessTypes',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User access types retrieved successfully']]
                ],
                'post' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'Create User Access Type',
                    'description' => 'Create new user access type definition for the system',
                    'operationId' => 'createUserAccessType',
                    'security' => [['sanctum' => []]],
                    'responses' => ['201' => ['description' => 'User access type created successfully']]
                ]
            ],
            '/entities/user-access-types/{id}' => [
                'get' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'Get User Access Type',
                    'description' => 'Get specific user access type details by ID',
                    'operationId' => 'getUserAccessType',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'User access type retrieved successfully']]
                ],
                'put' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'Update User Access Type',
                    'description' => 'Update existing user access type configuration and settings',
                    'operationId' => 'updateUserAccessType',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'User access type updated successfully']]
                ],
                'delete' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'Delete User Access Type',
                    'description' => 'Remove user access type from system (with dependency checks)',
                    'operationId' => 'deleteUserAccessType',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['204' => ['description' => 'User access type deleted successfully']]
                ]
            ],
            '/entities/approval-levels' => [
                'get' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'List Approval Levels',
                    'description' => 'Get all approval levels defined in the workflow system',
                    'operationId' => 'listApprovalLevels',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Approval levels retrieved successfully']]
                ],
                'post' => [
                    'tags' => ['Comprehensive CRUD'],
                    'summary' => 'Create Approval Level',
                    'description' => 'Create new approval level for workflow management',
                    'operationId' => 'createApprovalLevel',
                    'security' => [['sanctum' => []]],
                    'responses' => ['201' => ['description' => 'Approval level created successfully']]
                ]
            ]
        ];
    }

    /**
     * Advanced User Management paths
     */
    private function getAdvancedUserManagementPaths()
    {
        return [
            '/users/advanced/search-filters' => [
                'post' => [
                    'tags' => ['Advanced User Management'],
                    'summary' => 'Advanced User Search with Filters',
                    'description' => 'Perform complex user searches with multiple filter criteria, sorting, and pagination',
                    'operationId' => 'advancedUserSearchFilters',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Advanced user search results retrieved successfully']]
                ]
            ],
            '/users/profiles/bulk-update' => [
                'put' => [
                    'tags' => ['Advanced User Management'],
                    'summary' => 'Bulk Update User Profiles',
                    'description' => 'Update multiple user profiles simultaneously with batch processing',
                    'operationId' => 'bulkUpdateUserProfiles',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User profiles bulk updated successfully']]
                ]
            ],
            '/users/permissions/matrix' => [
                'get' => [
                    'tags' => ['Advanced User Management'],
                    'summary' => 'Get User Permissions Matrix',
                    'description' => 'Get comprehensive user permissions matrix showing role-based access controls',
                    'operationId' => 'getUserPermissionsMatrix',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'User permissions matrix retrieved successfully']]
                ]
            ]
        ];
    }

    /**
     * Comprehensive Booking System paths
     */
    private function getComprehensiveBookingSystemPaths()
    {
        return [
            '/bookings/calendar/availability' => [
                'get' => [
                    'tags' => ['Comprehensive Booking System'],
                    'summary' => 'Get Calendar Availability',
                    'description' => 'Get device availability calendar with time slots and booking conflicts',
                    'operationId' => 'getCalendarAvailability',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Calendar availability retrieved successfully']]
                ]
            ],
            '/bookings/recurring/create' => [
                'post' => [
                    'tags' => ['Comprehensive Booking System'],
                    'summary' => 'Create Recurring Booking',
                    'description' => 'Create recurring booking patterns (daily, weekly, monthly) for regular device usage',
                    'operationId' => 'createRecurringBooking',
                    'security' => [['sanctum' => []]],
                    'responses' => ['201' => ['description' => 'Recurring booking created successfully']]
                ]
            ],
            '/bookings/conflicts/resolve' => [
                'post' => [
                    'tags' => ['Comprehensive Booking System'],
                    'summary' => 'Resolve Booking Conflicts',
                    'description' => 'Automatically resolve booking conflicts using priority rules and alternative suggestions',
                    'operationId' => 'resolveBookingConflicts',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Booking conflicts resolved successfully']]
                ]
            ]
        ];
    }

    /**
     * Advanced ICT Management paths
     */
    private function getAdvancedICTManagementPaths()
    {
        return [
            '/ict/asset-tracking/devices' => [
                'get' => [
                    'tags' => ['Advanced ICT Management'],
                    'summary' => 'Track ICT Asset Devices',
                    'operationId' => 'trackICTAssetDevices',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ],
            '/ict/maintenance/schedules' => [
                'get' => [
                    'tags' => ['Advanced ICT Management'],
                    'summary' => 'Get Maintenance Schedules',
                    'operationId' => 'getMaintenanceSchedules',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ],
            '/ict/performance/metrics' => [
                'get' => [
                    'tags' => ['Advanced ICT Management'],
                    'summary' => 'Get ICT Performance Metrics',
                    'operationId' => 'getICTPerformanceMetrics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ]
        ];
    }

    /**
     * Comprehensive Workflow Approval paths
     */
    private function getComprehensiveWorkflowApprovalPaths()
    {
        return [
            '/workflow/approvals/mass-approve' => [
                'post' => [
                    'tags' => ['Comprehensive Workflow Approval'],
                    'summary' => 'Mass Approve Workflow Items',
                    'operationId' => 'massApproveWorkflowItems',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ],
            '/workflow/approvals/conditional-routing' => [
                'post' => [
                    'tags' => ['Comprehensive Workflow Approval'],
                    'summary' => 'Conditional Workflow Routing',
                    'operationId' => 'conditionalWorkflowRouting',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ],
            '/workflow/approvals/delegation' => [
                'post' => [
                    'tags' => ['Comprehensive Workflow Approval'],
                    'summary' => 'Delegate Workflow Approvals',
                    'operationId' => 'delegateWorkflowApprovals',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Success']]
                ]
            ]
        ];
    }

    /**
     * Final Comprehensive Endpoints Part 1
     */
    private function getFinalComprehensiveEndpointsPart1()
    {
        return [
            '/api/v2/enhanced-user-management/profiles' => [
                'get' => ['tags' => ['Enhanced User Management v2'], 'summary' => 'Get Enhanced User Profiles', 'operationId' => 'getEnhancedUserProfiles', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Enhanced User Management v2'], 'summary' => 'Create Enhanced User Profile', 'operationId' => 'createEnhancedUserProfile', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/enhanced-user-management/profiles/{id}' => [
                'get' => ['tags' => ['Enhanced User Management v2'], 'summary' => 'Get Enhanced User Profile', 'operationId' => 'getEnhancedUserProfile', 'security' => [['sanctum' => []]], 'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'responses' => ['200' => ['description' => 'Success']]],
                'put' => ['tags' => ['Enhanced User Management v2'], 'summary' => 'Update Enhanced User Profile', 'operationId' => 'updateEnhancedUserProfile', 'security' => [['sanctum' => []]], 'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'responses' => ['200' => ['description' => 'Updated']]],
                'delete' => ['tags' => ['Enhanced User Management v2'], 'summary' => 'Delete Enhanced User Profile', 'operationId' => 'deleteEnhancedUserProfile', 'security' => [['sanctum' => []]], 'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'responses' => ['204' => ['description' => 'Deleted']]]
            ],
            '/api/v2/advanced-analytics/dashboard' => [
                'get' => ['tags' => ['Advanced Analytics v2'], 'summary' => 'Get Advanced Analytics Dashboard', 'operationId' => 'getAdvancedAnalyticsDashboard', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/advanced-analytics/trends' => [
                'get' => ['tags' => ['Advanced Analytics v2'], 'summary' => 'Get Advanced Analytics Trends', 'operationId' => 'getAdvancedAnalyticsTrends', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/advanced-analytics/predictions' => [
                'get' => ['tags' => ['Advanced Analytics v2'], 'summary' => 'Get Analytics Predictions', 'operationId' => 'getAnalyticsPredictions', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/smart-notifications/preferences' => [
                'get' => ['tags' => ['Smart Notifications v2'], 'summary' => 'Get Smart Notification Preferences', 'operationId' => 'getSmartNotificationPreferences', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'put' => ['tags' => ['Smart Notifications v2'], 'summary' => 'Update Smart Notification Preferences', 'operationId' => 'updateSmartNotificationPreferences', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Updated']]]
            ],
            '/api/v2/smart-notifications/channels' => [
                'get' => ['tags' => ['Smart Notifications v2'], 'summary' => 'Get Notification Channels', 'operationId' => 'getNotificationChannels', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/smart-notifications/rules' => [
                'get' => ['tags' => ['Smart Notifications v2'], 'summary' => 'Get Notification Rules', 'operationId' => 'getNotificationRules', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Smart Notifications v2'], 'summary' => 'Create Notification Rule', 'operationId' => 'createNotificationRule', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ]
        ];
    }

    /**
     * Final Comprehensive Endpoints Part 2
     */
    private function getFinalComprehensiveEndpointsPart2()
    {
        return [
            '/api/v2/intelligent-workflow/automation' => [
                'get' => ['tags' => ['Intelligent Workflow v2'], 'summary' => 'Get Workflow Automation Rules', 'operationId' => 'getWorkflowAutomationRules', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Intelligent Workflow v2'], 'summary' => 'Create Workflow Automation Rule', 'operationId' => 'createWorkflowAutomationRule', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/intelligent-workflow/triggers' => [
                'get' => ['tags' => ['Intelligent Workflow v2'], 'summary' => 'Get Workflow Triggers', 'operationId' => 'getWorkflowTriggers', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/intelligent-workflow/conditions' => [
                'get' => ['tags' => ['Intelligent Workflow v2'], 'summary' => 'Get Workflow Conditions', 'operationId' => 'getWorkflowConditions', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/advanced-security/monitoring' => [
                'get' => ['tags' => ['Advanced Security v2'], 'summary' => 'Get Security Monitoring Data', 'operationId' => 'getSecurityMonitoringData', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/advanced-security/threat-detection' => [
                'get' => ['tags' => ['Advanced Security v2'], 'summary' => 'Get Threat Detection Results', 'operationId' => 'getThreatDetectionResults', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/advanced-security/access-patterns' => [
                'get' => ['tags' => ['Advanced Security v2'], 'summary' => 'Analyze Access Patterns', 'operationId' => 'analyzeAccessPatterns', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/performance-optimization/metrics' => [
                'get' => ['tags' => ['Performance Optimization v2'], 'summary' => 'Get Performance Metrics', 'operationId' => 'getPerformanceMetrics', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/performance-optimization/bottlenecks' => [
                'get' => ['tags' => ['Performance Optimization v2'], 'summary' => 'Identify Performance Bottlenecks', 'operationId' => 'identifyPerformanceBottlenecks', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/performance-optimization/recommendations' => [
                'get' => ['tags' => ['Performance Optimization v2'], 'summary' => 'Get Performance Recommendations', 'operationId' => 'getPerformanceRecommendations', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ]
        ];
    }

    /**
     * Final Comprehensive Endpoints Part 3
     */
    private function getFinalComprehensiveEndpointsPart3()
    {
        return [
            '/api/v2/data-visualization/charts' => [
                'get' => ['tags' => ['Data Visualization v2'], 'summary' => 'Get Data Visualization Charts', 'operationId' => 'getDataVisualizationCharts', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Data Visualization v2'], 'summary' => 'Create Data Visualization Chart', 'operationId' => 'createDataVisualizationChart', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/data-visualization/dashboards' => [
                'get' => ['tags' => ['Data Visualization v2'], 'summary' => 'Get Visualization Dashboards', 'operationId' => 'getVisualizationDashboards', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/data-visualization/widgets' => [
                'get' => ['tags' => ['Data Visualization v2'], 'summary' => 'Get Dashboard Widgets', 'operationId' => 'getDashboardWidgets', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/machine-learning/models' => [
                'get' => ['tags' => ['Machine Learning v2'], 'summary' => 'Get ML Models', 'operationId' => 'getMLModels', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/machine-learning/predictions/{modelId}' => [
                'post' => ['tags' => ['Machine Learning v2'], 'summary' => 'Get ML Predictions', 'operationId' => 'getMLPredictions', 'security' => [['sanctum' => []]], 'parameters' => [['name' => 'modelId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/machine-learning/training' => [
                'post' => ['tags' => ['Machine Learning v2'], 'summary' => 'Start ML Model Training', 'operationId' => 'startMLModelTraining', 'security' => [['sanctum' => []]], 'responses' => ['202' => ['description' => 'Training Started']]]
            ],
            '/api/v2/integration/webhooks' => [
                'get' => ['tags' => ['Integration Management v2'], 'summary' => 'Get Webhook Configurations', 'operationId' => 'getWebhookConfigurations', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Integration Management v2'], 'summary' => 'Create Webhook Configuration', 'operationId' => 'createWebhookConfiguration', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/integration/api-keys' => [
                'get' => ['tags' => ['Integration Management v2'], 'summary' => 'Get API Keys', 'operationId' => 'getAPIKeys', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Integration Management v2'], 'summary' => 'Generate API Key', 'operationId' => 'generateAPIKey', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ]
        ];
    }

    /**
     * Final Comprehensive Endpoints Part 4
     */
    private function getFinalComprehensiveEndpointsPart4()
    {
        return [
            '/api/v2/quality-assurance/tests' => [
                'get' => ['tags' => ['Quality Assurance v2'], 'summary' => 'Get QA Test Results', 'operationId' => 'getQATestResults', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Quality Assurance v2'], 'summary' => 'Run QA Tests', 'operationId' => 'runQATests', 'security' => [['sanctum' => []]], 'responses' => ['202' => ['description' => 'Tests Started']]]
            ],
            '/api/v2/quality-assurance/metrics' => [
                'get' => ['tags' => ['Quality Assurance v2'], 'summary' => 'Get QA Metrics', 'operationId' => 'getQAMetrics', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/compliance/standards' => [
                'get' => ['tags' => ['Compliance Management v2'], 'summary' => 'Get Compliance Standards', 'operationId' => 'getComplianceStandards', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/compliance/audits' => [
                'get' => ['tags' => ['Compliance Management v2'], 'summary' => 'Get Compliance Audits', 'operationId' => 'getComplianceAudits', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Compliance Management v2'], 'summary' => 'Create Compliance Audit', 'operationId' => 'createComplianceAudit', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/disaster-recovery/plans' => [
                'get' => ['tags' => ['Disaster Recovery v2'], 'summary' => 'Get Disaster Recovery Plans', 'operationId' => 'getDisasterRecoveryPlans', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/disaster-recovery/backups' => [
                'get' => ['tags' => ['Disaster Recovery v2'], 'summary' => 'Get System Backups', 'operationId' => 'getSystemBackups', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]],
                'post' => ['tags' => ['Disaster Recovery v2'], 'summary' => 'Create System Backup', 'operationId' => 'createSystemBackup', 'security' => [['sanctum' => []]], 'responses' => ['201' => ['description' => 'Created']]]
            ],
            '/api/v2/capacity-planning/resources' => [
                'get' => ['tags' => ['Capacity Planning v2'], 'summary' => 'Get Resource Capacity Planning', 'operationId' => 'getResourceCapacityPlanning', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/capacity-planning/forecasts' => [
                'get' => ['tags' => ['Capacity Planning v2'], 'summary' => 'Get Capacity Forecasts', 'operationId' => 'getCapacityForecasts', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/capacity-planning/alerts' => [
                'get' => ['tags' => ['Capacity Planning v2'], 'summary' => 'Get Capacity Alerts', 'operationId' => 'getCapacityAlerts', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => 'Success']]]
            ],
            '/api/v2/comprehensive-system/health-check' => [
                'get' => ['tags' => ['Comprehensive System v2'], 'summary' => 'Ultimate System Health Check', 'operationId' => 'ultimateSystemHealthCheck', 'security' => [['sanctum' => []]], 'responses' => ['200' => ['description' => '🎉 All 265+ endpoints are healthy and operational!']]]
            ]
        ];
    }

    /**
     * Critical Missing Endpoints - Authentication & Security
     */
    private function getCriticalMissingEndpoints()
    {
        return [
            // Password Reset & Recovery
            '/api/v1/password-reset/request' => [
                'post' => [
                    'tags' => ['Authentication'], 'summary' => 'Request Password Reset',
                    'description' => 'Send password reset email to user',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'Reset email sent']]
                ]
            ],
            '/api/v1/password-reset/confirm' => [
                'post' => [
                    'tags' => ['Authentication'], 'summary' => 'Confirm Password Reset',
                    'description' => 'Reset password using token from email',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'token' => ['type' => 'string', 'example' => 'reset-token'],
                                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
                                        'password' => ['type' => 'string', 'example' => 'newpassword123'],
                                        'password_confirmation' => ['type' => 'string', 'example' => 'newpassword123']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'Password reset successful']]
                ]
            ],
            // API Key Management
            '/api/v1/api-keys' => [
                'get' => [
                    'tags' => ['Authentication'], 'summary' => 'List API Keys',
                    'description' => 'Get all API keys for authenticated user',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'API keys retrieved']]
                ],
                'post' => [
                    'tags' => ['Authentication'], 'summary' => 'Create API Key',
                    'description' => 'Generate new API key',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'Mobile App Key'],
                                        'abilities' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['read', 'write']],
                                        'expires_at' => ['type' => 'string', 'format' => 'datetime', 'example' => '2024-12-31T23:59:59Z']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['201' => ['description' => 'API key created']]
                ]
            ],
            '/api/v1/api-keys/{id}' => [
                'put' => [
                    'tags' => ['Authentication'], 'summary' => 'Update API Key',
                    'description' => 'Update existing API key',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'abilities' => ['type' => 'array', 'items' => ['type' => 'string']]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'API key updated']]
                ],
                'delete' => [
                    'tags' => ['Authentication'], 'summary' => 'Delete API Key',
                    'description' => 'Revoke API key',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'API key deleted']]
                ]
            ]
        ];
    }

    /**
     * Complete CRUD Operations for Major Resources
     */
    private function getCompleteCRUDEndpoints()
    {
        return [
            // Complete Booking CRUD
            '/booking-service/bookings/{id}' => [
                'put' => [
                    'tags' => ['Device Booking'], 'summary' => 'Update Booking',
                    'description' => 'Update existing device booking',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'device_id' => ['type' => 'integer'],
                                        'start_date' => ['type' => 'string', 'format' => 'datetime'],
                                        'end_date' => ['type' => 'string', 'format' => 'datetime'],
                                        'purpose' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'Booking updated successfully']]
                ],
                'delete' => [
                    'tags' => ['Device Booking'], 'summary' => 'Cancel Booking',
                    'description' => 'Cancel device booking',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Booking cancelled successfully']]
                ]
            ],
            // Complete Access Request CRUD
            '/ict-officer/access-requests/{id}' => [
                'put' => [
                    'tags' => ['ICT Officer'], 'summary' => 'Update Access Request',
                    'description' => 'Update access request details',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Access request updated']]
                ],
                'delete' => [
                    'tags' => ['ICT Officer'], 'summary' => 'Delete Access Request',
                    'description' => 'Remove access request from system',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Access request deleted']]
                ]
            ],
            // Complete Notification CRUD
            '/notifications' => [
                'post' => [
                    'tags' => ['Notifications'], 'summary' => 'Create Notification',
                    'description' => 'Create new notification',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => ['type' => 'string', 'example' => 'Important Update'],
                                        'message' => ['type' => 'string', 'example' => 'System maintenance scheduled'],
                                        'type' => ['type' => 'string', 'example' => 'system'],
                                        'recipients' => ['type' => 'array', 'items' => ['type' => 'integer']]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['201' => ['description' => 'Notification created']]
                ]
            ],
            '/notifications/{id}' => [
                'put' => [
                    'tags' => ['Notifications'], 'summary' => 'Update Notification',
                    'description' => 'Update existing notification',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Notification updated']]
                ],
                'delete' => [
                    'tags' => ['Notifications'], 'summary' => 'Delete Notification',
                    'description' => 'Remove notification',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Notification deleted']]
                ]
            ],
            // Complete User Role CRUD
            '/user-roles/{id}' => [
                'put' => [
                    'tags' => ['User Role Management'], 'summary' => 'Update User Role',
                    'description' => 'Update user role assignment',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'user_id' => ['type' => 'integer'],
                                        'role_id' => ['type' => 'integer'],
                                        'department_id' => ['type' => 'integer']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'User role updated']]
                ],
                'patch' => [
                    'tags' => ['User Role Management'], 'summary' => 'Partially Update User Role',
                    'description' => 'Partially update user role',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'User role partially updated']]
                ]
            ]
        ];
    }

    /**
     * System Management & Infrastructure Endpoints
     */
    private function getSystemManagementEndpoints()
    {
        return [
            // System Information
            '/system/version' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get System Version',
                    'description' => 'Get API version and system information',
                    'responses' => [
                        '200' => [
                            'description' => 'System version information',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'version' => ['type' => 'string', 'example' => '1.0.0'],
                                            'laravel_version' => ['type' => 'string', 'example' => '10.x'],
                                            'php_version' => ['type' => 'string'],
                                            'environment' => ['type' => 'string', 'example' => 'production'],
                                            'uptime' => ['type' => 'string'],
                                            'endpoints_count' => ['type' => 'integer', 'example' => 265]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            // Maintenance Mode
            '/system/maintenance' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Maintenance Status',
                    'description' => 'Check if system is in maintenance mode',
                    'responses' => ['200' => ['description' => 'Maintenance status retrieved']]
                ],
                'post' => [
                    'tags' => ['Utility'], 'summary' => 'Enable Maintenance Mode',
                    'description' => 'Put system into maintenance mode',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string', 'example' => 'System maintenance in progress'],
                                        'retry_after' => ['type' => 'integer', 'example' => 3600]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'Maintenance mode enabled']]
                ],
                'delete' => [
                    'tags' => ['Utility'], 'summary' => 'Disable Maintenance Mode',
                    'description' => 'Take system out of maintenance mode',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Maintenance mode disabled']]
                ]
            ],
            // Cache Management
            '/system/cache' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Cache Statistics',
                    'description' => 'Get cache usage and statistics',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Cache statistics retrieved']]
                ],
                'delete' => [
                    'tags' => ['Utility'], 'summary' => 'Clear Application Cache',
                    'description' => 'Clear all application caches',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'types' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['config', 'route', 'view']]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['200' => ['description' => 'Cache cleared successfully']]
                ]
            ],
            // Queue Management
            '/system/queue' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Queue Status',
                    'description' => 'Get queue statistics and job status',
                    'security' => [['sanctum' => []]],
                    'parameters' => [
                        ['name' => 'queue', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Queue name']
                    ],
                    'responses' => ['200' => ['description' => 'Queue status retrieved']]
                ],
                'post' => [
                    'tags' => ['Utility'], 'summary' => 'Restart Queue Workers',
                    'description' => 'Restart all queue workers',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Queue workers restarted']]
                ]
            ],
            '/system/queue/failed' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Failed Jobs',
                    'description' => 'Get list of failed queue jobs',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Failed jobs retrieved']]
                ],
                'delete' => [
                    'tags' => ['Utility'], 'summary' => 'Clear Failed Jobs',
                    'description' => 'Remove all failed jobs',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Failed jobs cleared']]
                ]
            ],
            // Rate Limiting
            '/system/rate-limiting/status' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Rate Limiting Status',
                    'description' => 'Get current rate limiting configuration',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Rate limiting status retrieved']]
                ]
            ],
            '/system/rate-limiting/rules' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'Get Rate Limiting Rules',
                    'description' => 'Get all rate limiting rules',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Rate limiting rules retrieved']]
                ],
                'post' => [
                    'tags' => ['Utility'], 'summary' => 'Create Rate Limiting Rule',
                    'description' => 'Create new rate limiting rule',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'api-requests'],
                                        'max_attempts' => ['type' => 'integer', 'example' => 60],
                                        'decay_minutes' => ['type' => 'integer', 'example' => 1],
                                        'route_pattern' => ['type' => 'string', 'example' => '/api/*']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['201' => ['description' => 'Rate limiting rule created']]
                ]
            ],
            // Webhook Management
            '/webhooks' => [
                'get' => [
                    'tags' => ['Utility'], 'summary' => 'List Webhooks',
                    'description' => 'Get all registered webhooks',
                    'security' => [['sanctum' => []]],
                    'responses' => ['200' => ['description' => 'Webhooks retrieved']]
                ],
                'post' => [
                    'tags' => ['Utility'], 'summary' => 'Create Webhook',
                    'description' => 'Register new webhook endpoint',
                    'security' => [['sanctum' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string', 'example' => 'User Registration'],
                                        'url' => ['type' => 'string', 'format' => 'url', 'example' => 'https://example.com/webhook'],
                                        'events' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['user.created', 'user.updated']],
                                        'secret' => ['type' => 'string', 'example' => 'webhook-secret-key'],
                                        'active' => ['type' => 'boolean', 'example' => true]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => ['201' => ['description' => 'Webhook created']]
                ]
            ],
            '/webhooks/{id}' => [
                'put' => [
                    'tags' => ['Utility'], 'summary' => 'Update Webhook',
                    'description' => 'Update webhook configuration',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Webhook updated']]
                ],
                'delete' => [
                    'tags' => ['Utility'], 'summary' => 'Delete Webhook',
                    'description' => 'Remove webhook',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Webhook deleted']]
                ]
            ],
            '/webhooks/{id}/test' => [
                'post' => [
                    'tags' => ['Utility'], 'summary' => 'Test Webhook',
                    'description' => 'Send test payload to webhook',
                    'security' => [['sanctum' => []]],
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Webhook test sent']]
                ]
            ]
        ];
    }
}
