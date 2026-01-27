<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\OnboardingController;
use App\Http\Controllers\Api\v1\AdminController;
use App\Http\Controllers\Api\v1\UserAccessController;
use App\Http\Controllers\Api\v1\BookingServiceController;
use App\Http\Controllers\Api\v1\ICTApprovalController;
use App\Http\Controllers\Api\v1\DeclarationController;
use App\Http\Controllers\Api\v1\BothServiceFormController;
use App\Http\Controllers\Api\v1\AdminUserController;
use App\Http\Controllers\Api\v1\AdminDepartmentController;
use App\Http\Controllers\Api\v1\DeviceInventoryController;
use App\Http\Controllers\Api\v1\HodCombinedAccessController;
use App\Http\Controllers\Api\v1\DivisionalCombinedAccessController;
use App\Http\Controllers\Api\v1\DictCombinedAccessController;
use App\Http\Controllers\Api\v1\ModuleAccessApprovalController;
use App\Http\Controllers\Api\v1\ModuleRequestController;
use App\Http\Controllers\Api\v1\JeevaModuleRequestController;
use App\Http\Controllers\Api\v1\AccessRightsApprovalController;
use App\Http\Controllers\Api\v1\ImplementationWorkflowController;
use App\Http\Controllers\Api\v1\HeadOfItController;
use App\Http\Controllers\Api\v1\HeadOfItDictRecommendationsController;
use App\Http\Controllers\Api\v1\HodUserController;
use App\Http\Controllers\Api\v1\IctOfficerController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\SwaggerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\DocumentController;
use App\Http\Controllers\Api\v1\PasswordResetController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the User Access Management API',
        'version' => '1.0.0',
        'total_endpoints' => '265+',
        'comprehensive_docs' => url('/api-docs-modern'),
        'api_schema_json' => url('/api/api-docs'),
        'postman_collection' => url('/api/postman-collection'),
        'status' => 'active',
        'key_endpoints' => [
            'authentication' => url('/api/login'),
            'user_profile' => url('/api/user'),
            'user_access_requests' => url('/api/v1/user-access'),
            'device_bookings' => url('/api/booking-service/bookings'),
            'ict_approvals' => url('/api/ict-approval/device-requests')
        ],
        'instructions' => [
            'visit_docs' => 'Go to /api-docs-modern for comprehensive API documentation with all 265+ endpoints',
            'download_postman' => 'Download Postman collection from /api/postman-collection',
            'json_schema' => 'Access raw OpenAPI JSON from /api/docs'
        ]
    ]);
});

// Swagger Documentation Routes
Route::get('/documentation', [SwaggerController::class, 'documentation'])->name('api.documentation');
Route::get('/api-docs', [SwaggerController::class, 'apiDocs'])->name('api.docs');
Route::get('/docs.json', [SwaggerController::class, 'apiDocs'])->name('api.docs.json');
Route::get('/postman-collection', [SwaggerController::class, 'postmanCollection'])->name('api.postman-collection');

// Public routes
// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'database' => 'checking...'
    ]);
});

// Detailed health check with database test
Route::get('/health/detailed', function () {
    $dbStatus = 'ok';
    $dbError = null;

    try {
        \DB::connection()->getPdo();
        \DB::table('users')->count(); // Test a simple query
    } catch (\Exception $e) {
        $dbStatus = 'error';
        $dbError = $e->getMessage();
    }

    return response()->json([
        'status' => $dbStatus === 'ok' ? 'ok' : 'error',
        'timestamp' => now()->toISOString(),
        'database' => [
            'status' => $dbStatus,
            'error' => $dbError
        ],
        'environment' => app()->environment(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ]);
});

// =====================================================
// SMS Delivery Report Webhook (public)
// =====================================================
// Configure `SMS_DELIVERY_REPORT_URL` (e.g. https://your-domain.com/api/sms/delivery-report)
// so Kilakona can POST delivery receipts back to the system.
Route::match(['GET', 'POST'], '/sms/delivery-report', function (Request $request) {
    // Optional shared-secret check (recommended if provider supports custom header/query params)
    $expectedToken = env('SMS_DELIVERY_REPORT_TOKEN');
    if (!empty($expectedToken)) {
        $providedToken = $request->header('X-Webhook-Token')
            ?? $request->header('X-Sms-Token')
            ?? $request->query('token')
            ?? $request->input('token');

        if ($providedToken !== $expectedToken) {
            \Illuminate\Support\Facades\Log::warning('SMS delivery report rejected (bad token)', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    }

    // Accept JSON or form payloads
    $payload = $request->all();
    if (empty($payload)) {
        $raw = $request->getContent();
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }
    }

    // Heuristics for Kilakona-style callbacks
    $shootId = $payload['shootId']
        ?? $payload['shoot_id']
        ?? $payload['shootID']
        ?? $payload['shoot']
        ?? null;

    $phone = $payload['contact']
        ?? $payload['phone']
        ?? $payload['phone_number']
        ?? $payload['msisdn']
        ?? $payload['to']
        ?? null;

    $deliveryStatus = $payload['delivery_status']
        ?? $payload['deliveryStatus']
        ?? $payload['status']
        ?? $payload['state']
        ?? null;

    // Normalize phone to digits only (and strip leading +)
    if (is_string($phone)) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
    }

    // Find matching SmsLog
    $smsLog = null;
    if (!empty($shootId)) {
        $smsLog = \App\Models\SmsLog::query()
            ->where(function ($q) use ($shootId) {
                // For new JSON-object rows
                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(provider_response, '$.data.data.shootId')) = ?", [$shootId]);
                // Backward compatibility: provider_response stored as JSON string
                $q->orWhere('provider_response', 'like', '%"shootId":"' . $shootId . '"%');
            })
            ->orderByDesc('id')
            ->first();
    }

    if (!$smsLog && !empty($phone)) {
        $smsLog = \App\Models\SmsLog::query()
            ->where('phone_number', $phone)
            ->orderByDesc('id')
            ->first();
    }

    if (!$smsLog) {
        \Illuminate\Support\Facades\Log::warning('SMS delivery report received but no matching SmsLog found', [
            'shootId' => $shootId,
            'phone_last4' => is_string($phone) ? substr($phone, -4) : null,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'No matching SMS log found',
        ], 404);
    }

    // Merge delivery data into provider_response (handle both old and new storage formats)
    $existing = $smsLog->provider_response;
    if (is_string($existing)) {
        $decoded = json_decode($existing, true);
        $existing = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
    if (!is_array($existing)) {
        $existing = [];
    }

    $existing['delivery_report'] = [
        'received_at' => now()->toISOString(),
        'shootId' => $shootId,
        'delivery_status' => $deliveryStatus,
        'payload' => $payload,
    ];

    // Promote delivery status to a top-level key for SmsLog::delivery_status accessor
    if (!empty($deliveryStatus)) {
        $existing['delivery_status'] = $deliveryStatus;
    }

    $smsLog->provider_response = $existing;
    $smsLog->save();

    \Illuminate\Support\Facades\Log::info('SMS delivery report stored', [
        'sms_log_id' => $smsLog->id,
        'type' => $smsLog->type,
        'shootId' => $shootId,
        'delivery_status' => $deliveryStatus,
    ]);

    return response()->json(['success' => true]);
});


// Authentication routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register');

// Public password reset (phone + OTP) routes
Route::prefix('password-reset')->group(function () {
    Route::post('/request-by-phone', [PasswordResetController::class, 'requestByPhone'])->name('password-reset.request-by-phone');
    Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp'])->name('password-reset.verify-otp');
    Route::post('/reset-with-otp', [PasswordResetController::class, 'resetWithOtp'])->name('password-reset.reset-with-otp');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        // Load the roles relationship (new system)
        $user->load('roles', 'department');

        // Get primary role for consistent role handling
        $primaryRole = $user->getPrimaryRoleName();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Return user with role information using new system
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'pf_number' => $user->pf_number,
            'staff_name' => $user->staff_name,
            'department_id' => $user->department_id,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
                'display_name' => $user->department->getFullNameAttribute()
            ] : null,
            'is_active' => $user->is_active ?? true,
            'profile_photo_url' => $user->profile_photo_url,
            'role' => $primaryRole, // Normalized role field
            'role_name' => $primaryRole, // For backward compatibility
            'primary_role' => $primaryRole, // Explicit primary role
            'roles' => $userRoles, // Array of role names
            'role_objects' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->getDisplayName()
                ];
            }),
            'display_roles' => $user->getDisplayRoleNames(),
            'permissions' => $user->getAllPermissions(),
            'needs_onboarding' => $user->needsOnboarding(),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'must_change_password' => (bool) ($user->must_change_password ?? false),
        ];
    });

    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:sensitive')->name('logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('throttle:sensitive')->name('logout-all');
    Route::get('/sessions', [AuthController::class, 'getActiveSessions'])->middleware('throttle:api')->name('sessions');
    Route::post('/sessions/revoke', [AuthController::class, 'revokeSession'])->middleware('throttle:sensitive')->name('sessions.revoke');
    Route::get('/current-user', [AuthController::class, 'getCurrentUser'])->name('current-user');
    Route::get('/role-redirect', [AuthController::class, 'getRoleBasedRedirect'])->name('role-redirect');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:sensitive')->name('password.change');

    // Module data routes for dynamic loading
    Route::get('/wellsoft-modules', function () {
        try {
            $modules = \App\Models\WellsoftModule::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']);
            
            return response()->json([
                'success' => true,
                'data' => $modules,
                'message' => 'Wellsoft modules loaded successfully',
                'count' => $modules->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load Wellsoft modules',
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    })->name('wellsoft-modules.index');

    Route::get('/jeeva-modules', function () {
        try {
            $modules = \App\Models\JeevaModule::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']);
            
            return response()->json([
                'success' => true,
                'data' => $modules,
                'message' => 'Jeeva modules loaded successfully',
                'count' => $modules->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load Jeeva modules',
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    })->name('jeeva-modules.index');

    // Digital Signature routes
    Route::post('/documents/sign', [DocumentController::class, 'signDocument']);
    Route::get('/documents/{id}/signatures', [DocumentController::class, 'listDocumentSignatures']);
    Route::get('/signatures/{signature}/verify', [DocumentController::class, 'verifySignature']);

    // Onboarding routes
    Route::prefix('onboarding')->group(function () {
        Route::get('/status', [OnboardingController::class, 'getStatus'])->name('onboarding.status');
        Route::post('/accept-terms', [OnboardingController::class, 'acceptTerms'])->name('onboarding.accept-terms');
        Route::post('/accept-ict-policy', [OnboardingController::class, 'acceptIctPolicy'])->name('onboarding.accept-ict-policy');
        Route::post('/submit-declaration', [OnboardingController::class, 'submitDeclaration'])->name('onboarding.submit-declaration');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
        Route::post('/update-step', [OnboardingController::class, 'updateStep'])->name('onboarding.update-step');
        Route::post('/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset'); // Admin only
    });

    // Declaration routes
    Route::prefix('declaration')->group(function () {
        Route::get('/departments', [DeclarationController::class, 'getDepartments'])->name('declaration.departments');
        Route::post('/submit', [DeclarationController::class, 'store'])->name('declaration.submit');
        Route::get('/show', [DeclarationController::class, 'show'])->name('declaration.show');
        Route::post('/check-pf-number', [DeclarationController::class, 'checkPfNumber'])->name('declaration.check-pf-number');
        Route::get('/all', [DeclarationController::class, 'index'])->name('declaration.index'); // Admin only
    });

    // Admin routes (Admin only)
    Route::prefix('admin')->middleware('role:admin,super_admin')->group(function () {
        // Admin dashboard
        Route::get('/dashboard-stats', [AdminUserController::class, 'getDashboardStats'])->name('admin.dashboard-stats');
        
        // Legacy admin routes
        Route::post('/users/reset-onboarding', [AdminController::class, 'resetUserOnboarding'])->name('admin.users.reset-onboarding');
        Route::post('/users/bulk-reset-onboarding', [AdminController::class, 'bulkResetOnboarding'])->name('admin.users.bulk-reset-onboarding');
        Route::get('/onboarding/stats', [AdminController::class, 'getOnboardingStats'])->name('admin.onboarding.stats');

        // New comprehensive user management routes
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::post('/', [AdminUserController::class, 'store'])->name('admin.users.store');
            Route::get('/roles', [AdminUserController::class, 'getRoles'])->name('admin.users.roles');
            Route::get('/departments', [AdminUserController::class, 'getDepartments'])->name('admin.users.departments');
            Route::get('/create-form-data', [AdminUserController::class, 'getCreateFormData'])->name('admin.users.create-form-data');
            Route::post('/validate', [AdminUserController::class, 'validateUserData'])->name('admin.users.validate');
            Route::get('/statistics', [AdminUserController::class, 'getStatistics'])->name('admin.users.statistics');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
            Route::patch('/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        });

        // Department management routes
        Route::prefix('departments')->group(function () {
            Route::get('/', [AdminDepartmentController::class, 'index'])->name('admin.departments.index');
            Route::post('/', [AdminDepartmentController::class, 'store'])->name('admin.departments.store');
            Route::get('/create-form-data', [AdminDepartmentController::class, 'getCreateFormData'])->name('admin.departments.create-form-data');
            Route::get('/eligible-hods', [AdminDepartmentController::class, 'getEligibleHods'])->name('admin.departments.eligible-hods');
            Route::get('/eligible-divisional-directors', [AdminDepartmentController::class, 'getEligibleDivisionalDirectors'])->name('admin.departments.eligible-divisional-directors');
            Route::get('/{department}', [AdminDepartmentController::class, 'show'])->name('admin.departments.show');
            Route::put('/{department}', [AdminDepartmentController::class, 'update'])->name('admin.departments.update');
            Route::delete('/{department}', [AdminDepartmentController::class, 'destroy'])->name('admin.departments.destroy');
            Route::patch('/{department}/toggle-status', [AdminDepartmentController::class, 'toggleStatus'])->name('admin.departments.toggle-status');
        });

        // Role management routes
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('admin.roles.index');
            Route::post('/', [RoleController::class, 'store'])->name('admin.roles.store');
            Route::get('/{role}', [RoleController::class, 'show'])->name('admin.roles.show');
            Route::put('/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
        });

        // Permission management routes
        Route::get('/permissions', [RoleController::class, 'getPermissions'])->name('admin.permissions.index');

        // Legacy user management routes (keep for backward compatibility)
        Route::prefix('user-management')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('admin.user-management.index');
            Route::post('/', [AdminUserController::class, 'store'])->name('admin.user-management.store');
            Route::get('/roles', [AdminUserController::class, 'getRoles'])->name('admin.user-management.roles');
            Route::get('/statistics', [AdminUserController::class, 'getStatistics'])->name('admin.user-management.statistics');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('admin.user-management.show');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('admin.user-management.update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('admin.user-management.destroy');
            Route::post('/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.user-management.toggle-status');
        });
    });



    // User Access Request routes (v1)
    Route::prefix('v1')->group(function () {
        // Specific utility routes MUST come before apiResource to avoid parameter capture
        Route::get('user-access/pending-status', [UserAccessController::class, 'checkPendingRequests']);

        // User Access Request CRUD operations
        Route::apiResource('user-access', UserAccessController::class);

        // POST route with method spoofing for updates (to handle multipart/form-data)
        Route::post('user-access/{userAccess}', [UserAccessController::class, 'update'])
            ->name('user-access.update-multipart');

        // Combined Access Request route
        Route::post('combined-access', [UserAccessController::class, 'store'])->name('combined-access.store');

        // Additional utility routes
        Route::get('departments', [UserAccessController::class, 'getDepartments']);
        Route::post('check-signature', [UserAccessController::class, 'checkSignature']);
    });

    // Request Status routes (for staff users to view their requests)
    Route::prefix('request-status')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\RequestStatusController::class, 'index'])->name('request-status.index');
        Route::get('/details', [\App\Http\Controllers\Api\v1\RequestStatusController::class, 'show'])->name('request-status.show');
        Route::get('/statistics', [\App\Http\Controllers\Api\v1\RequestStatusController::class, 'statistics'])->name('request-status.statistics');
        Route::get('/types', [\App\Http\Controllers\Api\v1\RequestStatusController::class, 'getRequestTypes'])->name('request-status.types');
        Route::get('/statuses', [\App\Http\Controllers\Api\v1\RequestStatusController::class, 'getStatusOptions'])->name('request-status.statuses');
    });

    // Booking Service routes
    Route::prefix('booking-service')->group(function () {
        // CRUD operations
        Route::apiResource('bookings', BookingServiceController::class);

        // Edit rejected booking request route
        Route::put('bookings/{bookingId}/edit-rejected', [BookingServiceController::class, 'updateRejectedRequest'])->name('booking-service.edit-rejected');

        // Utility routes
        Route::get('device-types', [BookingServiceController::class, 'getDeviceTypes'])->name('booking-service.device-types');
        Route::get('departments', [BookingServiceController::class, 'getDepartments'])->name('booking-service.departments');
        Route::get('statistics', [BookingServiceController::class, 'getStatistics'])->name('booking-service.statistics');
        Route::post('seed-departments', [BookingServiceController::class, 'seedDepartments'])->name('booking-service.seed-departments');



        // Device availability checking
        Route::get('devices/{deviceInventoryId}/availability', [BookingServiceController::class, 'checkDeviceAvailability'])->name('booking-service.device-availability');
        Route::get('devices/{deviceInventoryId}/bookings', [BookingServiceController::class, 'getDeviceBookings'])->name('booking-service.device-bookings');

        // Pending request checking
        Route::get('check-pending-requests', [BookingServiceController::class, 'checkPendingRequests'])->name('booking-service.check-pending-requests');
        Route::get('can-submit-new-request', [BookingServiceController::class, 'canUserSubmitNewRequest'])->name('booking-service.can-submit-new-request');

        // Admin actions
        Route::post('bookings/{bookingService}/approve', [BookingServiceController::class, 'approve'])->name('booking-service.approve');
        Route::post('bookings/{bookingService}/reject', [BookingServiceController::class, 'reject'])->name('booking-service.reject');

        // ICT Officer actions (ICT Officer + Secretary ICT)
        Route::get('ict-approval-requests', [BookingServiceController::class, 'getIctApprovalRequests'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.ict-approval-requests');
        // Alias for frontend compatibility
        Route::get('ict-pending-approvals', [BookingServiceController::class, 'getIctApprovalRequests'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.ict-pending-approvals');
        Route::post('bookings/{bookingService}/ict-approve', [BookingServiceController::class, 'ictApprove'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.ict-approve');
        Route::post('bookings/{bookingService}/ict-reject', [BookingServiceController::class, 'ictReject'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.ict-reject');

        // Device condition assessment routes
        Route::post('bookings/{bookingService}/assessment/issuing', [BookingServiceController::class, 'saveIssuingAssessment'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.assessment.issuing');
        Route::post('bookings/{bookingService}/assessment/receiving', [BookingServiceController::class, 'saveReceivingAssessment'])
            ->middleware('role:ict_officer,secretary_ict,admin,ict_director')
            ->name('booking-service.assessment.receiving');
    });

    // ICT Approval routes (ICT Officer + Secretary ICT)
    Route::prefix('ict-approval')->middleware('role:ict_officer,secretary_ict,admin,ict_director')->group(function () {
// Statistics (must be before parameterized routes)
        Route::get('device-requests/statistics', [ICTApprovalController::class, 'getDeviceBorrowingStatistics'])->name('ict-approval.statistics');

        // Device borrowing requests management
        Route::get('device-requests', [ICTApprovalController::class, 'getDeviceBorrowingRequests'])->name('ict-approval.device-requests');
        Route::get('device-requests/{requestId}', [ICTApprovalController::class, 'getDeviceBorrowingRequestDetails'])->name('ict-approval.device-request-details');

        // Approval/rejection actions
        Route::post('device-requests/{requestId}/approve', [ICTApprovalController::class, 'approveDeviceBorrowingRequest'])->name('ict-approval.approve');
        Route::post('device-requests/{requestId}/reject', [ICTApprovalController::class, 'rejectDeviceBorrowingRequest'])->name('ict-approval.reject');
        Route::delete('device-requests/{requestId}', [ICTApprovalController::class, 'deleteDeviceBorrowingRequest'])->name('ict-approval.delete');
        Route::post('device-requests/bulk-delete', [ICTApprovalController::class, 'bulkDeleteDeviceBorrowingRequests'])->name('ict-approval.bulk-delete');

        // User details auto-capture
        Route::post('device-requests/{bookingId}/link-user', [ICTApprovalController::class, 'linkUserDetailsToBooking'])->name('ict-approval.link-user');

        // Device condition assessment routes
        Route::post('device-requests/{requestId}/assessment/issuing', [ICTApprovalController::class, 'saveIssuingAssessment'])->name('ict-approval.assessment.issuing');
        Route::post('device-requests/{requestId}/assessment/receiving', [ICTApprovalController::class, 'saveReceivingAssessment'])->name('ict-approval.assessment.receiving');
    });

    // Both Service Form routes (HOD Dashboard)
    Route::prefix('both-service-form')->middleware('both.service.role')->group(function () {
        // Basic CRUD operations
        Route::get('/', [BothServiceFormController::class, 'index'])->name('both-service-form.index');
        Route::post('/', [BothServiceFormController::class, 'store'])->name('both-service-form.store');
        Route::get('/{id}', [BothServiceFormController::class, 'show'])->name('both-service-form.show');
        Route::put('/{id}', [BothServiceFormController::class, 'update'])->name('both-service-form.update');
        Route::post('/{id}/update', [BothServiceFormController::class, 'update'])->name('both-service-form.update-multipart'); // For multipart/form-data

        // Table data with specific columns
        Route::get('/table/data', [BothServiceFormController::class, 'getTableData'])->name('both-service-form.table-data');

        // HOD specific routes
        Route::get('/{id}/hod-view', [BothServiceFormController::class, 'getFormForHOD'])
            ->middleware('both.service.role:hod')
            ->name('both-service-form.hod-view');
        Route::post('/{id}/hod-submit', [BothServiceFormController::class, 'hodSubmitToNextStage'])
            ->middleware('both.service.role:hod')
            ->name('both-service-form.hod-submit');

        // Utility routes
        Route::get('/user/info', [BothServiceFormController::class, 'getUserInfo'])->name('both-service-form.user-info');
        Route::get('/departments/list', [BothServiceFormController::class, 'getDepartments'])->name('both-service-form.departments');

        Route::get('/{id}/export-pdf', [BothServiceFormController::class, 'exportPdf'])->name('both-service-form.export-pdf');

        // Personal information routes from user_access table
        Route::get('/user-access/{userAccessId}/personal-info', [BothServiceFormController::class, 'getPersonalInfoFromUserAccess'])
            ->name('both-service-form.personal-info');
        Route::get('/hod/user-access-requests', [BothServiceFormController::class, 'getUserAccessRequestsForHOD'])
            ->middleware('both.service.role:hod,divisional_director')
            ->name('both-service-form.hod.user-access-requests');

        // Module request data routes
        Route::get('/module-requests/{userAccessId}', [BothServiceFormController::class, 'getModuleRequestData'])
            ->middleware('both.service.role:hod,divisional_director,ict_director,head_of_it,ict_officer')
            ->name('both-service-form.module-requests.show');
        Route::get('/module-requests', [BothServiceFormController::class, 'getPendingModuleRequests'])
            ->middleware('both.service.role:hod,divisional_director,ict_director,head_of_it,ict_officer')
            ->name('both-service-form.module-requests.pending');
        Route::get('/module-requests-statistics', [BothServiceFormController::class, 'getModuleRequestStatistics'])
            ->middleware('both.service.role:hod,divisional_director,ict_director,head_of_it,ict_officer')
            ->name('both-service-form.module-requests.statistics');

        // HOD approval with access rights validation
        Route::post('/module-requests/{userAccessId}/hod-approve', [BothServiceFormController::class, 'updateHodApproval'])
            ->middleware('both.service.role:hod')
            ->name('both-service-form.module-requests.hod-approve');

        // Update HOD approval for existing record
        Route::post('/{id}/update-hod-approval', [BothServiceFormController::class, 'updateHodApprovalForRecord'])
            ->name('both-service-form.update-hod-approval');
        
        // Divisional Director approval routes
        Route::post('/module-requests/{userAccessId}/divisional-approve', [BothServiceFormController::class, 'approveDivisionalDirector'])
            ->middleware('both.service.role:divisional_director')
            ->name('both-service-form.module-requests.divisional-approve');
        Route::post('/module-requests/{userAccessId}/divisional-reject', [BothServiceFormController::class, 'rejectDivisionalDirector'])
            ->middleware('both.service.role:divisional_director')
            ->name('both-service-form.module-requests.divisional-reject');
        
        // ICT Director approval routes
        Route::post('/module-requests/{userAccessId}/ict-director-approve', [BothServiceFormController::class, 'approveIctDirector'])
            ->middleware('both.service.role:ict_director')
            ->name('both-service-form.module-requests.ict-director-approve');
        Route::post('/module-requests/{userAccessId}/ict-director-reject', [BothServiceFormController::class, 'rejectIctDirector'])
            ->middleware('both.service.role:ict_director')
            ->name('both-service-form.module-requests.ict-director-reject');
        
        // Head of IT approval routes
        Route::post('/module-requests/{userAccessId}/head-of-it-approve', [BothServiceFormController::class, 'approveHeadOfIT'])
            ->middleware('both.service.role:head_of_it')
            ->name('both-service-form.module-requests.head-of-it-approve');
        Route::post('/module-requests/{userAccessId}/head-of-it-reject', [BothServiceFormController::class, 'rejectHeadOfIT'])
            ->middleware('both.service.role:head_of_it')
            ->name('both-service-form.module-requests.head-of-it-reject');
        
        // ICT Officer implementation (grant access)
        Route::post('/module-requests/{userAccessId}/ict-officer-approve', [BothServiceFormController::class, 'approveIctOfficer'])
            ->middleware('both.service.role:ict_officer')
            ->name('both-service-form.module-requests.ict-officer-approve');
        

        // Role-based approval routes with specific role requirements
        Route::post('/{id}/approve/hod', [BothServiceFormController::class, 'approveAsHOD'])
            ->middleware('both.service.role:hod')
            ->name('both-service-form.approve.hod');
        Route::post('/{id}/approve/divisional-director', [BothServiceFormController::class, 'approveAsDivisionalDirector'])
            ->middleware('both.service.role:divisional_director')
            ->name('both-service-form.approve.divisional-director');
        Route::post('/{id}/approve/dict', [BothServiceFormController::class, 'approveAsDICT'])
            ->middleware('both.service.role:ict_director')
            ->name('both-service-form.approve.dict');
        Route::post('/{id}/approve/hod-it', [BothServiceFormController::class, 'approveAsHODIT'])
            ->middleware('both.service.role:head_of_it')
            ->name('both-service-form.approve.hod-it');
        Route::post('/{id}/approve/ict-officer', [BothServiceFormController::class, 'approveAsICTOfficer'])
            ->middleware('both.service.role:ict_officer')
            ->name('both-service-form.approve.ict-officer');
    });



    // User Role Management routes (Admin only)
    Route::prefix('user-roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\UserRoleController::class, 'index'])->name('user-roles.index');
        Route::get('/statistics', [\App\Http\Controllers\Api\v1\UserRoleController::class, 'statistics'])->name('user-roles.statistics');
        Route::post('/{user}/assign', [\App\Http\Controllers\Api\v1\UserRoleController::class, 'assignRoles'])->name('user-roles.assign');
        Route::delete('/{user}/roles/{role}', [\App\Http\Controllers\Api\v1\UserRoleController::class, 'removeRole'])->name('user-roles.remove');
        Route::get('/{user}/history', [\App\Http\Controllers\Api\v1\UserRoleController::class, 'roleHistory'])->name('user-roles.history');
    });

    // Department HOD Management routes (Admin only)
    Route::prefix('department-hod')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'index'])->name('department-hod.index');
        Route::get('/eligible-hods', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'eligibleHods'])->name('department-hod.eligible');
        Route::get('/statistics', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'statistics'])->name('department-hod.statistics');
        Route::post('/{department}/assign', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'assignHod'])->name('department-hod.assign');
        Route::put('/{department}/update', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'updateHod'])->name('department-hod.update');
        Route::get('/{department}/details', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'getHodDetails'])->name('department-hod.details');
        Route::delete('/{department}/remove', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'removeHod'])->name('department-hod.remove');
        Route::delete('/{department}/delete', [\App\Http\Controllers\Api\v1\DepartmentHodController::class, 'deleteHod'])->name('department-hod.delete');
    });

    // Device Inventory Management routes (Admin only)
    Route::prefix('device-inventory')->group(function () {
        Route::get('/', [DeviceInventoryController::class, 'index'])->name('device-inventory.index');
        Route::post('/', [DeviceInventoryController::class, 'store'])->name('device-inventory.store');
        Route::get('/available', [DeviceInventoryController::class, 'getAvailableDevices'])->name('device-inventory.available');
        Route::get('/statistics', [DeviceInventoryController::class, 'getStatistics'])->name('device-inventory.statistics');
        Route::post('/fix-quantities', [DeviceInventoryController::class, 'fixQuantities'])->name('device-inventory.fix-quantities');
        Route::get('/{deviceInventory}', [DeviceInventoryController::class, 'show'])->name('device-inventory.show');
        Route::put('/{deviceInventory}', [DeviceInventoryController::class, 'update'])->name('device-inventory.update');
        Route::delete('/{deviceInventory}', [DeviceInventoryController::class, 'destroy'])->name('device-inventory.destroy');
    });

    // User Dashboard routes (Staff only)
    Route::prefix('user')->group(function () {
        Route::get('/dashboard-stats', [\App\Http\Controllers\Api\v1\UserDashboardController::class, 'getDashboardStats'])->name('user.dashboard-stats');
        Route::get('/request-status-breakdown', [\App\Http\Controllers\Api\v1\UserDashboardController::class, 'getRequestStatusBreakdown'])->name('user.request-status-breakdown');
        Route::get('/recent-activity', [\App\Http\Controllers\Api\v1\UserDashboardController::class, 'getRecentActivity'])->name('user.recent-activity');
    });

// HOD Combined Access Request routes (HOD only) - LEGACY
Route::prefix('hod')->middleware('role:head_of_department,divisional_director,ict_director,head_of_it,admin')->group(function () {
    Route::post('users', [HodUserController::class, 'store'])
        ->middleware('role:head_of_department,head_of_it,admin')
        ->name('hod.users.store');
    Route::get('combined-access-requests', [HodCombinedAccessController::class, 'index'])
        ->name('hod.combined-access-requests.index');
    Route::get('combined-access-requests/statistics', [HodCombinedAccessController::class, 'statistics'])
        ->name('hod.combined-access-requests.statistics');
    Route::get('combined-access-requests/{id}', [HodCombinedAccessController::class, 'show'])
        ->name('hod.combined-access-requests.show');
    Route::post('combined-access-requests/{id}/approve', [HodCombinedAccessController::class, 'updateApproval'])
        ->name('hod.combined-access-requests.approve');
    Route::post('combined-access-requests/{id}/cancel', [HodCombinedAccessController::class, 'cancel'])
        ->name('hod.combined-access-requests.cancel');
    Route::delete('combined-access-requests/{id}', [HodCombinedAccessController::class, 'destroy'])
        ->name('hod.combined-access-requests.delete');
    Route::get('combined-access-requests/{id}/timeline', [HodCombinedAccessController::class, 'getAccessRequestTimeline'])
        ->name('hod.combined-access-requests.timeline');
    
    // HOD Divisional Director Recommendations
    Route::get('divisional-recommendations', [\App\Http\Controllers\Api\v1\HodDivisionalRecommendationsController::class, 'getDivisionalRecommendations'])
        ->middleware('role:head_of_department')
        ->name('hod.divisional-recommendations.list');
    Route::get('divisional-recommendations/stats', [\App\Http\Controllers\Api\v1\HodDivisionalRecommendationsController::class, 'getRecommendationStats'])
        ->middleware('role:head_of_department')
        ->name('hod.divisional-recommendations.stats');
    Route::get('divisional-recommendations/{userAccessId}/details', [\App\Http\Controllers\Api\v1\HodDivisionalRecommendationsController::class, 'getRequestDetails'])
        ->middleware('role:head_of_department')
        ->name('hod.divisional-recommendations.details');
    Route::post('divisional-recommendations/{userAccessId}/resubmit', [\App\Http\Controllers\Api\v1\HodDivisionalRecommendationsController::class, 'resubmitRequest'])
        ->middleware('role:head_of_department')
        ->name('hod.divisional-recommendations.resubmit');
    
});

    // Divisional Director Combined Access Request routes (Divisional Director only)
    Route::prefix('divisional')->middleware('role:divisional_director,ict_director,admin')->group(function () {
        Route::get('combined-access-requests', [DivisionalCombinedAccessController::class, 'index'])
            ->name('divisional.combined-access-requests.index');
        Route::get('combined-access-requests/statistics', [DivisionalCombinedAccessController::class, 'statistics'])
            ->name('divisional.combined-access-requests.statistics');
        Route::get('combined-access-requests/{id}', [DivisionalCombinedAccessController::class, 'show'])
            ->name('divisional.combined-access-requests.show');
        Route::post('combined-access-requests/{id}/approve', [DivisionalCombinedAccessController::class, 'updateApproval'])
            ->name('divisional.combined-access-requests.approve');
        Route::post('combined-access-requests/{id}/cancel', [DivisionalCombinedAccessController::class, 'cancel'])
            ->name('divisional.combined-access-requests.cancel');
        Route::get('combined-access-requests/{id}/timeline', [DivisionalCombinedAccessController::class, 'getAccessRequestTimeline'])
            ->name('divisional.combined-access-requests.timeline');
            
        // Divisional Director ICT Director Recommendations
        Route::get('ict-director-recommendations', [\App\Http\Controllers\Api\v1\DivisionalDictRecommendationsController::class, 'getDictRecommendations'])
            ->middleware('role:divisional_director')
            ->name('divisional.ict-director-recommendations.list');
        Route::get('ict-director-recommendations/stats', [\App\Http\Controllers\Api\v1\DivisionalDictRecommendationsController::class, 'getRecommendationStats'])
            ->middleware('role:divisional_director')
            ->name('divisional.ict-director-recommendations.stats');
        Route::get('ict-director-recommendations/{userAccessId}/details', [\App\Http\Controllers\Api\v1\DivisionalDictRecommendationsController::class, 'getRequestDetails'])
            ->middleware('role:divisional_director')
            ->name('divisional.ict-director-recommendations.details');
        Route::post('ict-director-recommendations/{userAccessId}/respond', [\App\Http\Controllers\Api\v1\DivisionalDictRecommendationsController::class, 'submitResponse'])
            ->middleware('role:divisional_director')
            ->name('divisional.ict-director-recommendations.respond');
    });

    // ICT Director Combined Access Request routes (ICT Director and Head of IT)
    Route::prefix('ict-director')->middleware('role:ict_director,head_of_it,admin')->group(function () {
        Route::get('combined-access-requests', [DictCombinedAccessController::class, 'index'])
            ->name('ict-director.combined-access-requests.index');
        Route::get('combined-access-requests/statistics', [DictCombinedAccessController::class, 'statistics'])
            ->name('ict-director.combined-access-requests.statistics');
        Route::get('combined-access-requests/{id}', [DictCombinedAccessController::class, 'show'])
            ->name('ict-director.combined-access-requests.show');
        Route::post('combined-access-requests/{id}/approve', [DictCombinedAccessController::class, 'updateApproval'])
            ->name('ict-director.combined-access-requests.approve');
        Route::post('combined-access-requests/{id}/cancel', [DictCombinedAccessController::class, 'cancel'])
            ->name('ict-director.combined-access-requests.cancel');
        Route::get('combined-access-requests/{id}/timeline', [DictCombinedAccessController::class, 'getAccessRequestTimeline'])
            ->name('ict-director.combined-access-requests.timeline');
    });

    // Head of IT routes (Head of IT only)
    Route::prefix('head-of-it')->middleware('role:head_of_it,admin')->group(function () {
        // Get all requests that have reached Head of IT stage (pending, approved, rejected)
        Route::get('all-requests', [HeadOfItController::class, 'getAllRequests'])
            ->name('head-of-it.all-requests');
        
        // Get requests pending Head of IT approval (backward compatibility)
        Route::get('pending-requests', [HeadOfItController::class, 'getPendingRequests'])
            ->name('head-of-it.pending-requests');
        
        // Get specific request details
        Route::get('requests/{id}', [HeadOfItController::class, 'getRequestById'])
            ->name('head-of-it.request-details');
        Route::get('requests/{id}/timeline', [HeadOfItController::class, 'getAccessRequestTimeline'])
            ->name('head-of-it.request-timeline');
        
        // Approve/Reject request actions
        Route::post('requests/{id}/approve', [HeadOfItController::class, 'approveRequest'])
            ->name('head-of-it.approve-request');
        Route::post('requests/{id}/reject', [HeadOfItController::class, 'rejectRequest'])
            ->name('head-of-it.reject-request');
        
        // ICT Officer management
        Route::get('ict-officers', [HeadOfItController::class, 'getIctOfficers'])
            ->name('head-of-it.ict-officers');
        Route::post('assign-task', [HeadOfItController::class, 'assignTaskToIctOfficer'])
            ->name('head-of-it.assign-task');
        
        // Task assignment management
        Route::get('tasks/{requestId}/history', [HeadOfItController::class, 'getTaskHistory'])
            ->name('head-of-it.task-history');
        Route::post('tasks/{requestId}/cancel', [HeadOfItController::class, 'cancelTaskAssignment'])
            ->name('head-of-it.cancel-task');
            
        // Head of IT ICT Director Recommendations
        Route::get('ict-director-recommendations', [HeadOfItDictRecommendationsController::class, 'getDictRecommendations'])
            ->middleware('role:head_of_it')
            ->name('head-of-it.ict-director-recommendations.list');
        Route::get('ict-director-recommendations/stats', [HeadOfItDictRecommendationsController::class, 'getRecommendationStats'])
            ->middleware('role:head_of_it')
            ->name('head-of-it.ict-director-recommendations.stats');
        Route::get('ict-director-recommendations/{userAccessId}/details', [HeadOfItDictRecommendationsController::class, 'getRequestDetails'])
            ->middleware('role:head_of_it')
            ->name('head-of-it.ict-director-recommendations.details');
            
        // ========================================
        // NEW ICT TASK ASSIGNMENT ROUTES (UserAccess system)
        // ========================================
        
        // Get requests pending Head of IT approval (NEW system)
        Route::get('ict-pending-requests', [HeadOfItController::class, 'getPendingIctRequests'])
            ->name('head-of-it.ict-pending-requests');
        
        // Get available ICT Officers for task assignment
        Route::get('available-ict-officers', [HeadOfItController::class, 'getAvailableIctOfficers'])
            ->name('head-of-it.available-ict-officers');
        
        // Assign ICT task to officer
        Route::post('assign-ict-task', [HeadOfItController::class, 'assignIctTask'])
            ->name('head-of-it.assign-ict-task');
        
        // ICT task assignment management
        Route::get('ict-tasks/{userAccessId}/history', [HeadOfItController::class, 'getIctTaskHistory'])
            ->name('head-of-it.ict-task-history');
        Route::post('ict-tasks/{userAccessId}/cancel', [HeadOfItController::class, 'cancelIctTaskAssignment'])
            ->name('head-of-it.cancel-ict-task');
    });

    // ICT Officer routes (ICT Officer only; Secretary ICT handles device bookings via ict-approval)
    Route::prefix('ict-officer')->middleware('role:ict_officer,admin')->group(function () {
        // Dashboard
        Route::get('dashboard', [IctOfficerController::class, 'getDashboard'])
            ->name('ict-officer.dashboard');
        
        // Task management (legacy IctTaskAssignment system)
        Route::get('tasks', [IctOfficerController::class, 'getAssignedTasks'])
            ->name('ict-officer.tasks');
        Route::get('tasks/{assignmentId}', [IctOfficerController::class, 'getTaskDetails'])
            ->name('ict-officer.task-details');
        
        // Task actions (legacy system)
        Route::post('tasks/{assignmentId}/start', [IctOfficerController::class, 'startTask'])
            ->name('ict-officer.start-task');
        Route::post('tasks/{assignmentId}/progress', [IctOfficerController::class, 'updateTaskProgress'])
            ->name('ict-officer.update-progress');
        Route::post('tasks/{assignmentId}/complete', [IctOfficerController::class, 'completeTask'])
            ->name('ict-officer.complete-task');
        
        // Access Request Management (new system for Head of IT approved requests)
        Route::get('access-requests', [IctOfficerController::class, 'getAccessRequests'])
            ->middleware('nocache')
            ->name('ict-officer.access-requests');
        Route::get('access-requests/{requestId}', [IctOfficerController::class, 'getAccessRequestById'])
            ->middleware('nocache')
            ->name('ict-officer.access-request-details');
        Route::get('access-requests/{requestId}/timeline', [IctOfficerController::class, 'getAccessRequestTimeline'])
            ->middleware('nocache')
            ->name('ict-officer.access-request-timeline');
        Route::post('access-requests/{requestId}/assign', [IctOfficerController::class, 'assignAccessRequestToSelf'])
            ->name('ict-officer.assign-access-request');
        Route::put('access-requests/{requestId}/progress', [IctOfficerController::class, 'updateAccessRequestProgress'])
            ->name('ict-officer.update-access-request-progress');
        Route::post('access-requests/{requestId}/cancel', [IctOfficerController::class, 'cancelAccessRequestTask'])
            ->name('ict-officer.cancel-access-request');
        Route::post('access-requests/{requestId}/complete', [IctOfficerController::class, 'updateAccessRequestProgress'])
            ->name('ict-officer.complete-access-request');
        Route::post('access-requests/{requestId}/grant-access', [IctOfficerController::class, 'grantAccess'])
            ->name('ict-officer.grant-access');
        
        // Statistics
        Route::get('statistics', [IctOfficerController::class, 'getTaskStatistics'])
            ->middleware('nocache')
            ->name('ict-officer.statistics');
        Route::get('pending-count', [IctOfficerController::class, 'getPendingRequestsCount'])
            ->middleware('nocache')
            ->name('ict-officer.pending-count');
    });

    // ========================================
    // NEW COMPLETE USER ACCESS WORKFLOW ROUTES
    // ========================================

    // User Access Workflow routes - Complete system for all stakeholders
    Route::prefix('user-access-workflow')->group(function () {
        // Basic CRUD operations
        Route::get('/', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'index'])
            ->name('user-access-workflow.index');
        Route::post('/', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'store'])
            ->name('user-access-workflow.store');
        Route::get('/{userAccess}', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'show'])
            ->name('user-access-workflow.show');
        Route::put('/{userAccess}', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'update'])
            ->name('user-access-workflow.update');

        // Utility routes
        Route::get('/options/form-data', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'getFormOptions'])
            ->name('user-access-workflow.form-options');
        Route::get('/statistics/dashboard', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'getStatistics'])
            ->name('user-access-workflow.statistics');
        Route::post('/export/requests', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'export'])
            ->name('user-access-workflow.export');
        Route::post('/{userAccess}/cancel', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'cancel'])
            ->name('user-access-workflow.cancel');

        // Approval workflow routes - Role-based access control
        Route::post('/{userAccess}/approve/hod', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'processHodApproval'])
            ->middleware('role:head_of_department')
            ->name('user-access-workflow.approve.hod');
        Route::post('/{userAccess}/approve/divisional', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'processDivisionalApproval'])
            ->middleware('role:divisional_director')
            ->name('user-access-workflow.approve.divisional');
        Route::post('/{userAccess}/approve/ict-director', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'processIctDirectorApproval'])
            ->middleware('role:ict_director')
            ->name('user-access-workflow.approve.ict-director');
        Route::post('/{userAccess}/approve/head-it', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'processHeadItApproval'])
            ->middleware('role:head_it')
            ->name('user-access-workflow.approve.head-it');
        Route::post('/{userAccess}/implement/ict-officer', [\App\Http\Controllers\Api\UserAccessWorkflowController::class, 'processIctOfficerImplementation'])
            ->middleware('role:ict_officer')
            ->name('user-access-workflow.implement.ict-officer');
    });

    // User Profile routes (for form auto-population)
    Route::prefix('profile')->group(function () {
        Route::get('/current', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'getCurrentUserProfile'])->name('profile.current');
        Route::put('/current', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'updateCurrentUserProfile'])->name('profile.update');
        Route::post('/lookup-pf', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'getUserByPfNumber'])->name('profile.lookup-pf');
        Route::post('/check-pf', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'checkPfNumberExists'])->name('profile.check-pf');
        Route::get('/departments', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'getDepartments'])->name('profile.departments');
        Route::post('/avatar', [\App\Http\Controllers\Api\v1\UserProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    });

    // Notification routes (Universal for all authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('pending-count', [\App\Http\Controllers\Api\v1\NotificationController::class, 'getPendingRequestsCount'])
            ->middleware('nocache')
            ->name('notifications.pending-count');
        Route::get('breakdown', [\App\Http\Controllers\Api\v1\NotificationController::class, 'getPendingRequestsBreakdown'])
            ->middleware('role:admin')
            ->middleware('nocache')
            ->name('notifications.breakdown');
    });


    // Module Access Approval routes - Universal approval handling
    Route::prefix('module-access-approval')->group(function () {
        Route::get('/{id}', [ModuleAccessApprovalController::class, 'getRequestForApproval'])
            ->name('module-access-approval.get');
        Route::post('/{id}/process', [ModuleAccessApprovalController::class, 'processApproval'])
            ->name('module-access-approval.process');
    });

    // Module Request routes - For handling Wellsoft module requests
    Route::prefix('module-requests')->group(function () {
        Route::post('/', [ModuleRequestController::class, 'store'])
            ->name('module-requests.store');
        Route::get('/modules', [ModuleRequestController::class, 'getAvailableModules'])
            ->name('module-requests.modules');
        Route::get('/{userAccessId}', [ModuleRequestController::class, 'show'])
            ->name('module-requests.show');
        Route::put('/{userAccessId}', [ModuleRequestController::class, 'update'])
            ->name('module-requests.update');

        // Jeeva Module Request routes
        Route::prefix('jeeva')->group(function () {
            Route::post('/', [JeevaModuleRequestController::class, 'store'])
                ->name('module-requests.jeeva.store');
            Route::get('/modules', [JeevaModuleRequestController::class, 'getAvailableModules'])
                ->name('module-requests.jeeva.modules');
            Route::get('/{userAccessId}', [JeevaModuleRequestController::class, 'show'])
                ->name('module-requests.jeeva.show');
            Route::put('/{userAccessId}', [JeevaModuleRequestController::class, 'update'])
                ->name('module-requests.jeeva.update');
        });
    });

    // Access Rights and Approval Workflow routes
    Route::prefix('access-rights-approval')->group(function () {
        Route::post('/', [AccessRightsApprovalController::class, 'store'])
            ->name('access-rights-approval.store');
        Route::get('/{userAccessId}', [AccessRightsApprovalController::class, 'show'])
            ->name('access-rights-approval.show');
        Route::put('/{userAccessId}', [AccessRightsApprovalController::class, 'update'])
            ->name('access-rights-approval.update');
    });

    // Implementation Workflow routes (Head of IT and ICT Officer)
    Route::prefix('implementation-workflow')->group(function () {
        Route::post('/', [ImplementationWorkflowController::class, 'store'])
            ->name('implementation-workflow.store');
        Route::get('/{userAccessId}', [ImplementationWorkflowController::class, 'show'])
            ->name('implementation-workflow.show');
        Route::put('/{userAccessId}', [ImplementationWorkflowController::class, 'update'])
            ->name('implementation-workflow.update');
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\v1\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread-count', [\App\Http\Controllers\Api\v1\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/{id}/mark-read', [\App\Http\Controllers\Api\v1\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::patch('/mark-all-read', [\App\Http\Controllers\Api\v1\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/{id}', [\App\Http\Controllers\Api\v1\NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // Filtered User Access routes (for admin user management)
    Route::get('/jeeva-users', [UserAccessController::class, 'getJeevaUsers'])
        ->middleware('role:admin')
        ->name('admin.jeeva-users');
    Route::get('/wellsoft-users', [UserAccessController::class, 'getWellsoftUsers'])
        ->middleware('role:admin')
        ->name('admin.wellsoft-users');
    Route::get('/internet-users', [UserAccessController::class, 'getInternetUsers'])
        ->middleware('role:admin')
        ->name('admin.internet-users');
});
