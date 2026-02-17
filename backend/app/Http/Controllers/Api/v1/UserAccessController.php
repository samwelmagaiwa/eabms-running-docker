<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAccessRequest;
use App\Models\UserAccess;
use App\Models\User;
use App\Models\Department;
use App\Services\SignatureService;
use App\Services\StatusMigrationService;
use App\Services\SmsModule;
use App\Traits\HandlesStatusQueries;
use App\Models\Signature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserAccessController extends Controller
{
    use HandlesStatusQueries;

    /**
     * The signature service instance.
     */
    private SignatureService $signatureService;

    /**
     * The status migration service instance.
     */
    private StatusMigrationService $statusMigrationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(SignatureService $signatureService, StatusMigrationService $statusMigrationService)
    {
        $this->middleware('auth:sanctum');
        $this->signatureService = $signatureService;
        $this->statusMigrationService = $statusMigrationService;
    }

    /**
     * Display a listing of user access requests.
     *
     * @OA\Get(
     *     path="/api/v1/user-access",
     *     summary="Get User Access Requests",
     *     description="Retrieve a paginated list of user access requests for the authenticated user",
     *     operationId="getUserAccessRequests",
     *     tags={"User Access"},
     *     security={"sanctum": {}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by request status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="request_type",
     *         in="query",
     *         description="Filter by request type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"wellsoft_access", "jeeva_access", "internet_access_request"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in PF number, staff name, or phone number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort by field",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User access requests retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User access requests retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=42),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="pf_number", type="string", example="PF12345"),
     *                         @OA\Property(property="staff_name", type="string", example="John Doe"),
     *                         @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                         @OA\Property(property="status", type="string", example="pending"),
     *                         @OA\Property(property="request_type", type="array", @OA\Items(type="string"), example={"wellsoft_access"}),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve user access requests.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = UserAccess::with(['user', 'department'])
                ->where('user_id', Auth::id());

            // Apply filters
            if ($request->has('status') && $request->status !== '') {
                $statusFilter = $request->status;
                
                // Check if it's a legacy status or specific stage status
                if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'completed', 'implemented'])) {
                    // Handle common status filters
                    switch ($statusFilter) {
                        case 'pending':
                            // Show all pending requests across stages
                            $query->where(function($q) {
                                $q->where('hod_status', 'pending')
                                  ->orWhere(function($q2) {
                                      $q2->where('hod_status', 'approved')
                                         ->where('divisional_status', 'pending');
                                  })
                                  ->orWhere(function($q3) {
                                      $q3->where('hod_status', 'approved')
                                         ->where('divisional_status', 'approved')
                                         ->where('ict_director_status', 'pending');
                                  })
                                  ->orWhere(function($q4) {
                                      $q4->where('hod_status', 'approved')
                                         ->where('divisional_status', 'approved')
                                         ->where('ict_director_status', 'approved')
                                         ->where('head_it_status', 'pending');
                                  })
                                  ->orWhere(function($q5) {
                                      $q5->where('hod_status', 'approved')
                                         ->where('divisional_status', 'approved')
                                         ->where('ict_director_status', 'approved')
                                         ->where('head_it_status', 'approved')
                                         ->where('ict_officer_status', 'pending');
                                  });
                            });
                            break;
                        case 'rejected':
                            $query->where(function($q) {
                                $q->where('hod_status', 'rejected')
                                  ->orWhere('divisional_status', 'rejected')
                                  ->orWhere('ict_director_status', 'rejected')
                                  ->orWhere('head_it_status', 'rejected');
                            });
                            break;
                        case 'completed':
                        case 'implemented':
                            $query->where('ict_officer_status', 'implemented');
                            break;
                        case 'approved':
                            // Show fully approved (implemented) requests
                            $query->where('ict_officer_status', 'implemented');
                            break;
                    }
                } else {
                    // Fall back to original status column for backward compatibility
                    $query->where('status', $statusFilter);
                }
            }

            if ($request->has('request_type') && $request->request_type !== '') {
                $query->whereJsonContains('request_type', $request->request_type);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('pf_number', 'like', "%{$search}%")
                      ->orWhere('staff_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            // Sort by created_at desc by default
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $requests = $query->paginate($perPage);

            // Add signature URLs to the response
            $requests->getCollection()->transform(function ($userAccess) {
                $userAccess->signature_url = $this->signatureService->getSignatureUrl($userAccess->signature_path);
                $userAccess->signature_info = $this->signatureService->getSignatureInfo($userAccess->signature_path);
                return $userAccess;
            });

            return response()->json([
                'success' => true,
                'data' => $requests,
                'message' => 'User access requests retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user access requests: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user access requests.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created combined user access request.
     *
     * @OA\Post(
     *     path="/api/v1/user-access",
     *     summary="Create User Access Request",
     *     description="Submit a new user access request with digital signature",
     *     operationId="createUserAccessRequest",
     *     tags={"User Access"},
     *     security={"sanctum": {}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"pf_number", "staff_name", "phone_number", "department_id", "signature", "request_type"},
     *                 @OA\Property(property="pf_number", type="string", example="PF12345", description="Personal File Number"),
     *                 @OA\Property(property="staff_name", type="string", example="John Doe", description="Staff full name"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890", description="Phone number"),
     *                 @OA\Property(property="department_id", type="integer", example=1, description="Department ID"),
     *                 @OA\Property(property="signature", type="string", format="binary", description="Digital signature file"),
     *                 @OA\Property(
     *                     property="request_type",
     *                     type="array",
     *                     @OA\Items(type="string", enum={"wellsoft_access", "jeeva_access", "internet_access_request"}),
     *                     description="Array of requested access types"
     *                 ),
     *                 @OA\Property(property="accessType", type="string", enum={"permanent", "temporary"}, default="permanent"),
     *                 @OA\Property(property="temporaryUntil", type="string", format="date", description="End date for temporary access"),
     *                 @OA\Property(
     *                     property="internetPurposes",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     description="Purposes for internet access (required if requesting internet access)"
     *                 ),
     *                 @OA\Property(property="wellsoftRequestType", type="string", enum={"use", "admin"}, default="use"),
     *                 @OA\Property(property="hodName", type="string", description="Head of Department name"),
     *                 @OA\Property(property="hodSignature", type="string", format="binary", description="HOD signature file"),
     *                 @OA\Property(property="hodDate", type="string", format="date", description="HOD approval date"),
     *                 @OA\Property(property="hodComments", type="string", description="HOD comments")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User access request created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User access request submitted successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="pf_number", type="string", example="PF12345"),
     *                 @OA\Property(property="staff_name", type="string", example="John Doe"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="request_type", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create user access request.")
     *         )
     *     )
     * )
     */
    public function store(UserAccessRequest $request): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Debug: Log incoming request data
            Log::info('\ud83d\udd0d USER ACCESS CONTROLLER DEBUG:', [
                'request_method' => $request->method(),
                'request_url' => $request->url(),
                'content_type' => $request->header('Content-Type'),
                'all_data' => $request->except(['signature']),
                'selectedWellsoft' => $request->input('selectedWellsoft', 'NOT_PROVIDED'),
                'selectedJeeva' => $request->input('selectedJeeva', 'NOT_PROVIDED'),
                'wellsoftRequestType' => $request->input('wellsoftRequestType', 'NOT_PROVIDED'),
                'accessType' => $request->input('accessType', 'NOT_PROVIDED'),
                'temporaryUntil' => $request->input('temporaryUntil', 'NOT_PROVIDED'),
                'request_types' => $request->input('request_types', 'NOT_PROVIDED'),
                'request_type' => $request->input('request_type'),
                'services' => $request->input('services'),
                'internetPurposes' => $request->input('internetPurposes')
            ]);
            
            $validatedData = $request->validated();
            
            // Digital-only signatures: do not accept file uploads here.
            // Signature presence is validated in the FormRequest via signatures table.
            $signaturePath = null;

            // Get selected services directly from request_type
            $selectedServices = $validatedData['request_type'];
            
            // Process internet purposes if internet access is selected
            $purposes = null;
            if (in_array('internet_access_request', $selectedServices) && isset($validatedData['internetPurposes'])) {
                $purposes = array_filter($validatedData['internetPurposes'], function($purpose) {
                    return !empty(trim($purpose));
                });
                $purposes = array_values($purposes); // Re-index array
            }
            
            // Process module selections with comprehensive fallback strategies (same as BothServiceFormController)
            $selectedWellsoft = $this->processModuleArray($request, 'selectedWellsoft');
            $selectedJeeva = $this->processModuleArray($request, 'selectedJeeva');
            
            Log::info('📊 Processed module selections in UserAccessController', [
                'selectedWellsoft' => $selectedWellsoft,
                'selectedJeeva' => $selectedJeeva,
                'selectedWellsoft_count' => count($selectedWellsoft),
                'selectedJeeva_count' => count($selectedJeeva),
                'wellsoftRequestType' => $request->input('wellsoftRequestType', 'use')
            ]);
            
            // Store staff module selections so HOD can see what was requested
            Log::info('✅ Storing staff module selections and access rights in database', [
                'user_id' => $user->id,
                'selected_services' => $selectedServices,
                'wellsoft_modules_selected' => $selectedWellsoft,
                'wellsoft_modules_count' => count($selectedWellsoft),
                'jeeva_modules_selected' => $selectedJeeva,
                'jeeva_modules_count' => count($selectedJeeva),
                'module_requested_for' => $request->input('wellsoftRequestType', 'use'),
                'access_type' => $request->input('accessType', 'permanent'),
                'temporary_until' => $request->input('temporaryUntil', null)
            ]);
            
            Log::info('Creating combined user access request', [
                'selected_services' => $selectedServices,
                'purposes' => $purposes,
                'wellsoft_modules_count' => count($selectedWellsoft),
                'jeeva_modules_count' => count($selectedJeeva)
            ]);
            
            // Determine initial workflow based on submitter role (ICT Officer skips HOD/Divisional)
            $isIctOfficer = false;
            try {
                if (method_exists($user, 'roles')) {
                    $isIctOfficer = $user->roles()->where('name', 'ict_officer')->exists();
                }
                if (!$isIctOfficer && property_exists($user, 'role') && isset($user->role->name)) {
                    $isIctOfficer = strtolower($user->role->name) === 'ict_officer';
                }
                if (!$isIctOfficer && method_exists($user, 'getPrimaryRoleName')) {
                    $isIctOfficer = strtolower($user->getPrimaryRoleName()) === 'ict_officer';
                }
            } catch (\Throwable $e) {
                $isIctOfficer = false;
            }

            $initialStatus = $isIctOfficer ? 'pending_ict_director' : 'pending';
            $initialHod = $isIctOfficer ? 'skipped' : 'pending';
            $initialDiv = $isIctOfficer ? 'skipped' : 'pending';

            // Create the combined access request - store multiple services in one row
            $userAccess = UserAccess::create([
                'user_id' => $user->id,
                'pf_number' => $validatedData['pf_number'],
                'staff_name' => $validatedData['staff_name'],
                'phone_number' => $validatedData['phone_number'],
                'department_id' => $validatedData['department_id'],
                'signature_path' => $signaturePath, // legacy field kept for response shape, set to null
                'request_type' => $selectedServices, // Array stored as JSON
                'status' => $initialStatus,
                
                // Initialize new status columns
                'hod_status' => $initialHod,
                'divisional_status' => $initialDiv,
                'ict_director_status' => 'pending',
                'head_it_status' => 'pending',
                'ict_officer_status' => 'pending',
                
                // Store staff's module selections so HOD can see what was requested
                'wellsoft_modules_selected' => $selectedWellsoft, // Staff selected modules
                'jeeva_modules_selected' => $selectedJeeva, // Staff selected modules
                'module_requested_for' => $request->input('wellsoftRequestType', 'use'),
                'internet_purposes' => array_filter($request->input('internetPurposes', []), function($purpose) {
                    return !empty(trim($purpose));
                }),
                
                // \u2705 ADD ACCESS TYPE AND TEMPORARY DATE
                'access_type' => $request->input('accessType', 'permanent'),
                'temporary_until' => $request->input('temporaryUntil'),
                
                // \u2705 ADD APPROVAL DATA
                'hod_name' => $request->input('hodName'),
                'hod_signature_path' => $this->handleSignatureUpload($request, 'hodSignature', $validatedData['pf_number'], 'hod'),
                'hod_approved_at' => $request->input('hodDate') ? $request->input('hodDate') : null,
                'hod_comments' => $request->input('hodComments'),
                
                'divisional_director_name' => $request->input('divisionalDirectorName'),
                'divisional_director_signature_path' => $this->handleSignatureUpload($request, 'divisionalDirectorSignature', $validatedData['pf_number'], 'divisional_director'),
                'divisional_approved_at' => $request->input('divisionalDirectorDate') ? $request->input('divisionalDirectorDate') : null,
                'divisional_director_comments' => $request->input('divisionalDirectorComments'),
                
                'ict_director_name' => $request->input('ictDirectorName'),
                'ict_director_signature_path' => $this->handleSignatureUpload($request, 'ictDirectorSignature', $validatedData['pf_number'], 'ict_director'),
                'ict_director_approved_at' => $request->input('ictDirectorDate') ? $request->input('ictDirectorDate') : null,
                'ict_director_comments' => $request->input('ictDirectorComments'),
                
                // \u2705 ADD IMPLEMENTATION DATA
                'head_it_name' => $request->input('headITName'),
                'head_it_signature_path' => $this->handleSignatureUpload($request, 'headITSignature', $validatedData['pf_number'], 'head_it'),
                'head_it_approved_at' => $request->input('headITDate') ? $request->input('headITDate') : null,
                'head_it_comments' => $request->input('headITComments'),
                
                'ict_officer_name' => $request->input('ictOfficerName'),
                'ict_officer_signature_path' => $this->handleSignatureUpload($request, 'ictOfficerSignature', $validatedData['pf_number'], 'ict_officer'),
                'ict_officer_implemented_at' => $request->input('ictOfficerDate') ? $request->input('ictOfficerDate') : null,
                'ict_officer_comments' => $request->input('ictOfficerComments'),
                'implementation_comments' => $request->input('implementationComments')
            ]);
            
            // Move pre-signed digital signature from synthetic doc id to real request id (if exists)
            try {
                $syntheticDocId = 900000000 + (int) $user->id;
                Signature::where('document_id', $syntheticDocId)
                    ->where('user_id', $user->id)
                    ->update(['document_id' => $userAccess->id]);
            } catch (\Exception $e) {
                Log::warning('Failed to rebind digital signature to real document id', [
                    'user_id' => $user->id,
                    'request_id' => $userAccess->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Refresh the model to get actual stored values
            $userAccess->refresh();
            
            Log::info('\u2705 ACTUAL DATA STORED IN DATABASE (UserAccessController):', [
                'record_id' => $userAccess->id,
                'wellsoft_modules' => $userAccess->wellsoft_modules,
                'wellsoft_modules_selected' => $userAccess->wellsoft_modules_selected,
                'jeeva_modules' => $userAccess->jeeva_modules,
                'jeeva_modules_selected' => $userAccess->jeeva_modules_selected,
                'module_requested_for' => $userAccess->module_requested_for,
                'internet_purposes' => $userAccess->internet_purposes,
                'access_type' => $userAccess->access_type,
                'temporary_until' => $userAccess->temporary_until,
                'request_type' => $userAccess->request_type,
                'hod_name' => $userAccess->hod_name,
                'hod_signature_path' => $userAccess->hod_signature_path
            ]);
            
            Log::info('Successfully created combined request', [
                'request_id' => $userAccess->id,
                'request_types' => $selectedServices,
                'pf_number' => $validatedData['pf_number']
            ]);

            // Load relationships for response
            $userAccess->load(['user', 'department']);
            
            // Add signature info
            $userAccess->signature_url = $this->signatureService->getSignatureUrl($userAccess->signature_path);
            $userAccess->signature_info = $this->signatureService->getSignatureInfo($userAccess->signature_path);
            
            // Forward to appropriate queues for each service type (keep legacy behavior)
            foreach ($selectedServices as $serviceType) {
                $this->forwardToHodQueue($userAccess, $serviceType);
            }
            
            Log::info("Combined user access request created", [
                'user_id' => $user->id,
                'request_id' => $userAccess->id,
                'pf_number' => $validatedData['pf_number'],
                'request_types' => $selectedServices,
                'service_count' => count($selectedServices),
                'has_purposes' => !empty($purposes)
            ]);
            
            // Send initial SMS BEFORE commit
            try {
                // Build request type label once
                $types = [];
                if (in_array('jeeva_access_request', $selectedServices) || in_array('jeeva_access', $selectedServices) || in_array('jeeva', $selectedServices)) $types[] = 'Jeeva';
                if (in_array('wellsoft_access_request', $selectedServices) || in_array('wellsoft_access', $selectedServices) || in_array('wellsoft', $selectedServices)) $types[] = 'Wellsoft';
                if (in_array('internet_access_request', $selectedServices) || in_array('internet_access', $selectedServices) || in_array('internet', $selectedServices)) $types[] = 'Internet';
                $requestType = implode(' & ', $types) ?: 'Access';
                $requestId = 'MLG-REQ' . str_pad($userAccess->id, 6, '0', STR_PAD_LEFT);

                $sms = app(SmsModule::class);

                if ($isIctOfficer) {
                    // ICT Officer submission: notify ICT Director(s)
                    $ictDirectors = User::whereHas('roles', function($q) { $q->where('name', 'ict_director'); })
                        ->whereNotNull('phone')->get();
                    if ($ictDirectors->count() > 0) {
                        $message = "PENDING APPROVAL: {$requestType} request from {$userAccess->staff_name} requires your review. Ref: {$requestId}. Please check the system. - EABMS";
                        $result = $sms->sendBulkSms($ictDirectors->toArray(), $message, 'pending_notification');
                        
                        // Check if SMS was actually sent or just skipped
                        $actualSent = ($result['sent'] ?? 0) > 0;
                        $smsStatus = 'failed';
                        $smsSentAt = null;
                        
                        if ($actualSent) {
                            // Check the status from the result data
                            $smsStatus = $result['data']['status_to_use'] ?? 'sent';
                            $smsSentAt = ($result['data']['actually_sent'] ?? true) ? now() : null;
                        } elseif (isset($result['data']['disabled']) && $result['data']['disabled']) {
                            $smsStatus = 'disabled';
                        } elseif (isset($result['data']['test_mode']) && $result['data']['test_mode']) {
                            $smsStatus = 'test_mode';
                        }
                        
                        DB::table('user_access')
                            ->where('id', $userAccess->id)
                            ->update([
                                'sms_sent_to_ict_director_at' => $smsSentAt,
                                'sms_to_ict_director_status' => $smsStatus,
                                'updated_at' => now()
                            ]);
                        Log::info('Initial SMS to ICT Director(s) processed', [
                            'request_id' => $userAccess->id,
                            'recipients' => $ictDirectors->count(),
                            'actual_sent' => $actualSent,
                            'status_set' => $smsStatus
                        ]);
                    }
                } else {
                    // Normal staff flow: notify HOD of the department
                    // First try to get HOD via department relationship (more reliable)
                    $department = Department::with('hod')->find($userAccess->department_id);
                    $hod = $department?->hod;
                    
                    // Fallback: try the reverse relationship query if hod relation didn't work
                    if (!$hod) {
                        $hod = User::whereHas('departmentsAsHOD', function($q) use ($userAccess) {
                            $q->where('id', $userAccess->department_id);
                        })->first();
                    }
                    
                    Log::info('HOD lookup for SMS notification', [
                        'request_id' => $userAccess->id,
                        'department_id' => $userAccess->department_id,
                        'department_name' => $department?->name,
                        'department_hod_user_id' => $department?->hod_user_id,
                        'hod_found' => !is_null($hod),
                        'hod_id' => $hod?->id,
                        'hod_name' => $hod?->name,
                        'hod_phone' => $hod?->phone ? substr($hod->phone, 0, 6) . '***' : null
                    ]);

                    if ($hod && $hod->phone) {
                        $message = "PENDING APPROVAL: {$requestType} request from {$userAccess->staff_name} requires your review. Ref: {$requestId}. Please check the system. - EABMS";
                        $smsResult = $sms->sendSms($hod->phone, $message, 'pending_notification', $userAccess->id, get_class($userAccess));
                        
                        // Determine the correct SMS status based on what actually happened:
                        // - 'sent' = SMS was actually sent to provider and accepted
                        // - 'disabled' = SMS service is disabled, nothing was sent
                        // - 'test_mode' = SMS is in test mode, nothing was sent
                        // - 'failed' = SMS sending failed
                        $smsStatus = 'failed';
                        $smsSentAt = null;
                        if ($smsResult['success']) {
                            // Check the status_to_use field to know what actually happened
                            $smsStatus = $smsResult['data']['status_to_use'] ?? 'sent';
                            // Only set sent_at if SMS was actually sent
                            $smsSentAt = ($smsResult['data']['actually_sent'] ?? false) ? now() : null;
                        }
                        
                        DB::table('user_access')
                            ->where('id', $userAccess->id)
                            ->update([
                                'sms_sent_to_hod_at' => $smsSentAt,
                                'sms_to_hod_status' => $smsStatus,
                                'updated_at' => now()
                            ]);
                        
                        Log::info('SMS to HOD result', [
                            'request_id' => $userAccess->id,
                            'hod_id' => $hod->id,
                            'success' => $smsResult['success'],
                            'actually_sent' => $smsResult['data']['actually_sent'] ?? false,
                            'status_set' => $smsStatus,
                            'message' => $smsResult['message'] ?? 'No message'
                        ]);
                    } else {
                        // HOD not found or no phone - update status to indicate failure
                        $failReason = !$hod ? 'no_hod_assigned' : 'no_phone_number';
                        DB::table('user_access')
                            ->where('id', $userAccess->id)
                            ->update([
                                'sms_sent_to_hod_at' => null,
                                'sms_to_hod_status' => 'failed',
                                'updated_at' => now()
                            ]);
                        
                        Log::warning('Cannot send SMS to HOD', [
                            'request_id' => $userAccess->id,
                            'department_id' => $userAccess->department_id,
                            'reason' => $failReason,
                            'hod_found' => !is_null($hod),
                            'hod_has_phone' => $hod?->phone ? true : false
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to process initial SMS', [
                    'error' => $e->getMessage(),
                    'request_id' => $userAccess->id,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // CRITICAL: Update SMS status to 'failed' even when exception occurs
                // Otherwise the default 'pending' status will be misleading
                try {
                    DB::table('user_access')
                        ->where('id', $userAccess->id)
                        ->update([
                            'sms_sent_to_hod_at' => null,
                            'sms_to_hod_status' => 'failed',
                            'updated_at' => now()
                        ]);
                } catch (\Exception $dbEx) {
                    Log::error('Failed to update SMS status after exception', [
                        'error' => $dbEx->getMessage(),
                        'request_id' => $userAccess->id
                    ]);
                }
            }

            DB::commit();
            
            // Send database notification to HOD AFTER commit (non-critical)
            try {
                // Use the same reliable HOD lookup as SMS notification
                $deptForNotif = Department::with('hod')->find($userAccess->department_id);
                $hodForNotif = $deptForNotif?->hod;
                
                // Fallback: try the reverse relationship query
                if (!$hodForNotif) {
                    $hodForNotif = User::whereHas('departmentsAsHOD', function($q) use ($userAccess) {
                        $q->where('id', $userAccess->department_id);
                    })->first();
                }
                
                if ($hodForNotif) {
                    // Determine request type - handle all variations
                    $types = [];
                    if (in_array('jeeva_access_request', $selectedServices) || in_array('jeeva_access', $selectedServices) || in_array('jeeva', $selectedServices)) $types[] = 'Jeeva';
                    if (in_array('wellsoft_access_request', $selectedServices) || in_array('wellsoft_access', $selectedServices) || in_array('wellsoft', $selectedServices)) $types[] = 'Wellsoft';
                    if (in_array('internet_access_request', $selectedServices) || in_array('internet_access', $selectedServices) || in_array('internet', $selectedServices)) $types[] = 'Internet';
                    $requestType = implode(' & ', $types) ?: 'Access';
                    
                    $requestId = 'MLG-REQ' . str_pad($userAccess->id, 6, '0', STR_PAD_LEFT);
                    $departmentName = $userAccess->department->name ?? 'Unknown';
                    
                    DB::table('notifications')->insert([
                        'recipient_id' => $hodForNotif->id,
                        'sender_id' => $userAccess->user_id,
                        'access_request_id' => $userAccess->id,
                        'type' => 'new_access_request',
                        'title' => 'New Access Request',
                        'message' => "New {$requestType} request from {$userAccess->staff_name} ({$departmentName})",
                        'data' => json_encode([
                            'request_id' => $userAccess->id,
                            'request_number' => $requestId,
                            'staff_name' => $userAccess->staff_name,
                            'department' => $departmentName,
                            'request_type' => $requestType,
                            'status' => 'pending',
                            'action_url' => "/hod/combined-access-requests/{$userAccess->id}",
                        ]),
                        'read_at' => null,
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $hodForNotif->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('Database notification sent to HOD', [
                        'hod_id' => $hodForNotif->id,
                        'hod_name' => $hodForNotif->name,
                        'request_id' => $userAccess->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send database notification to HOD', [
                    'error' => $e->getMessage(),
                    'request_id' => $userAccess->id
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $userAccess,
                'message' => 'Combined access request submitted successfully with ' . count($selectedServices) . ' service type(s).',
                'signature_info' => $this->signatureService->getSignatureInfo($signaturePath),
                'services_requested' => $selectedServices
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating combined user access request: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['signature']),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit combined access request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified user access request.
     */
    public function show(UserAccess $userAccess): JsonResponse
    {
        try {
            // Check if user owns this request
            if ($userAccess->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this request.'
                ], 403);
            }

            $userAccess->load(['user', 'department']);
            
            // Add signature info
            $userAccess->signature_url = $this->signatureService->getSignatureUrl($userAccess->signature_path);
            $userAccess->signature_info = $this->signatureService->getSignatureInfo($userAccess->signature_path);

            return response()->json([
                'success' => true,
                'data' => $userAccess,
                'message' => 'User access request retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user access request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user access request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified user access request.
     */
    public function update(UserAccessRequest $request, UserAccess $userAccess): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            // Check if user owns this request
            if ($userAccess->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this request.'
                ], 403);
            }

            // Check if request can be updated (only pending or rejected requests)
            if (!$userAccess->canBeUpdated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending or rejected requests can be updated.'
                ], 422);
            }

            // Log the incoming request for debugging
            Log::info('Updating user access request - Full debug', [
                'request_id' => $userAccess->id,
                'request_method' => $request->method(),
                'has_method_override' => $request->has('_method'),
                'method_override_value' => $request->input('_method'),
                'all_input' => $request->except(['signature']),
                'all_keys' => array_keys($request->all()),
                'has_signature_file' => $request->hasFile('signature'),
                'content_type' => $request->header('content-type'),
                'request_data_debug' => [
                    'pf_number' => $request->input('pf_number'),
                    'staff_name' => $request->input('staff_name'),
                    'phone_number' => $request->input('phone_number'),
                    'department_id' => $request->input('department_id'),
                    'request_type' => $request->input('request_type'),
                    'internetPurposes' => $request->input('internetPurposes')
                ]
            ]);

            $validatedData = $request->validated();
            
            // Handle signature update if new file uploaded
            $signaturePath = $userAccess->signature_path; // Keep existing signature by default
            
            if ($request->hasFile('signature')) {
                // Delete old signature if it exists and is not shared
                if ($userAccess->signature_path) {
                    $this->signatureService->deleteSignature($userAccess->signature_path);
                }
                
                // Store new signature using the same method as create
                $signaturePath = $this->storeSignature(
                    $request->file('signature'),
                    $validatedData['pf_number']
                );
            }

            // Get selected services from validated data
            $selectedServices = $validatedData['request_type'];
            
            // Process module selections with comprehensive fallback strategies (same as store method)
            $selectedWellsoft = $this->processModuleArray($request, 'selectedWellsoft');
            $selectedJeeva = $this->processModuleArray($request, 'selectedJeeva');
            
            // Process internet purposes if internet access is selected
            $purposes = null;
            if (in_array('internet_access_request', $selectedServices) && isset($validatedData['internetPurposes'])) {
                $purposes = array_filter($validatedData['internetPurposes'], function($purpose) {
                    return !empty(trim($purpose));
                });
                $purposes = array_values($purposes); // Re-index array
            }
            
            Log::info('Updating combined access request', [
                'request_id' => $userAccess->id,
                'selected_services' => $selectedServices,
                'purposes' => $purposes,
                'signature_updated' => $request->hasFile('signature'),
                'wellsoft_modules_count' => count($selectedWellsoft),
                'jeeva_modules_count' => count($selectedJeeva)
            ]);

            // Check if this is a cancelled request to clear cancellation info
            $isCancelledRequest = $userAccess->hod_status === 'cancelled';
            
            $updateData = [
                'pf_number' => $validatedData['pf_number'],
                'staff_name' => $validatedData['staff_name'],
                'phone_number' => $validatedData['phone_number'],
                'department_id' => $validatedData['department_id'],
                'signature_path' => $signaturePath,
                'request_type' => $selectedServices, // Update services
'internet_purposes' => $purposes, // Update internet purposes
                // Backward-compatibility field (legacy)
                'purpose' => $purposes,
                'status' => 'pending', // Reset status to pending for resubmission
                
                // Reset new status columns for resubmission
                'hod_status' => 'pending',
                'divisional_status' => 'pending',
                'ict_director_status' => 'pending',
                'head_it_status' => 'pending',
                'ict_officer_status' => 'pending',
                
                // Update module selections
                'wellsoft_modules' => $selectedWellsoft,
                'wellsoft_modules_selected' => $selectedWellsoft,
                'jeeva_modules' => $selectedJeeva,
                'jeeva_modules_selected' => $selectedJeeva,
                'module_requested_for' => $request->input('wellsoftRequestType', 'use'),
            ];
            
            // If this was a cancelled request, clear cancellation and approval info for resubmission
            if ($isCancelledRequest) {
                $updateData = array_merge($updateData, [
                    // Clear approval timestamps
                    'hod_approved_at' => null,
                    'divisional_approved_at' => null,
                    'ict_director_approved_at' => null,
                    'head_it_approved_at' => null,
                    'ict_officer_implemented_at' => null,
                    
                    // Clear approver information
                    'hod_name' => null,
                    'hod_approved_by' => null,
                    'hod_approved_by_name' => null,
                    'hod_comments' => null,
                    'divisional_director_name' => null,
                    'divisional_director_comments' => null,
                    'ict_director_name' => null,
                    'ict_director_comments' => null,
                    'head_it_name' => null,
                    'head_it_comments' => null,
                    'ict_officer_name' => null,
                    'ict_officer_comments' => null,
                    
                    // Clear cancellation information
                    'cancellation_reason' => null,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                ]);
            }
            
            // Update the request
            $userAccess->update($updateData);

            $userAccess->load(['user', 'department']);
            
            // Add signature info
            $userAccess->signature_url = $this->signatureService->getSignatureUrl($userAccess->signature_path);
            $userAccess->signature_info = $this->signatureService->getSignatureInfo($userAccess->signature_path);

            // Forward to appropriate queues for each service type (reprocess workflow)
            foreach ($selectedServices as $serviceType) {
                $this->forwardToHodQueue($userAccess, $serviceType);
            }

            Log::info("User access request updated and resubmitted", [
                'user_id' => Auth::id(),
                'request_id' => $userAccess->id,
                'pf_number' => $validatedData['pf_number'],
                'request_types' => $selectedServices,
                'service_count' => count($selectedServices)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $userAccess,
                'message' => 'User access request updated and resubmitted successfully with ' . count($selectedServices) . ' service type(s).',
                'signature_info' => $this->signatureService->getSignatureInfo($signaturePath),
                'services_requested' => $selectedServices
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating user access request: ' . $e->getMessage(), [
                'request_id' => $userAccess->id,
                'user_id' => Auth::id(),
                'request_data' => $request->except(['signature']),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user access request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cancel (soft delete) the specified user access request.
     * Instead of deleting, this sets the status to 'cancelled'
     * Admins can delete any request, regular users can only delete their own
     */
    public function destroy(UserAccess $userAccess): JsonResponse
    {
        try {
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole(['admin', 'super_admin']);
            
            // Check if user owns this request OR is an admin
            if ($userAccess->user_id !== Auth::id() && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this request.'
                ], 403);
            }

            // For non-admins, check if request can be cancelled (only pending requests)
            // Admins can cancel/delete any request regardless of status
            if (!$isAdmin && !$userAccess->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be cancelled.'
                ], 422);
            }

            // Cancel the request by updating status instead of deleting
            // Perform hard delete
            // Note: Related models (wellsoft_modules_selected, jeeva_modules_selected, ict_task_assignments)
            // will be deleted via database cascade constraints.
            // Signatures are NOT deleted here to avoid ID collision with BookingService signatures (shared table, no discriminator).
            $userAccess->delete();

            Log::info("User access request deleted permanently", [
                'user_id' => Auth::id(),
                'request_id' => $userAccess->id,
                'deleted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User access request deleted permanently.',
                'data' => [
                    'id' => $userAccess->id,
                    'status' => 'deleted',
                    'deleted_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling user access request: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_id' => $userAccess->id,
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel user access request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available departments for the form.
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = Department::select('id', 'name', 'code')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $departments,
                'message' => 'Departments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving departments: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve departments.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check if signature exists for a PF number.
     */
    public function checkSignature(Request $request): JsonResponse
    {
        $request->validate([
            'pf_number' => 'required|string|max:50'
        ]);

        try {
            $pfNumber = $request->pf_number;
            $signaturePath = $this->signatureService->findExistingSignature($pfNumber);
            $signatureInfo = $this->signatureService->getSignatureInfo($signaturePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'pf_number' => $pfNumber,
                    'signature_exists' => !is_null($signaturePath),
                    'signature_path' => $signaturePath,
                    'signature_url' => $this->signatureService->getSignatureUrl($signaturePath),
                    'signature_info' => $signatureInfo,
                ],
                'message' => $signaturePath ? 'Signature found.' : 'No signature found.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking signature: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check signature.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle signature upload for approval/implementation signatures
     */
    private function handleSignatureUpload(Request $request, string $fieldName, string $pfNumber, string $role): ?string
    {
        if (!$request->hasFile($fieldName)) {
            return null;
        }
        
        try {
            $signatureFile = $request->file($fieldName);
            $directory = 'signatures/' . $role;
            $filename = $role . '_signature_' . $pfNumber . '_' . time() . '.' . $signatureFile->getClientOriginalExtension();
            $path = $signatureFile->storeAs($directory, $filename, 'public');
            
            Log::info("$role signature uploaded successfully", [
                'pf_number' => $pfNumber,
                'role' => $role,
                'filename' => $filename,
                'path' => $path
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error("Error storing $role signature: " . $e->getMessage(), [
                'pf_number' => $pfNumber,
                'role' => $role
            ]);
            return null;
        }
    }
    
    /**
     * Store uploaded signature file.
     */
    private function storeSignature($signatureFile, string $pfNumber): string
    {
        try {
            // Create directory path based on PF number
            $directory = 'signatures/' . strtoupper($pfNumber);
            
            // Generate unique filename
            $filename = 'signature_' . time() . '.' . $signatureFile->getClientOriginalExtension();
            
            // Store file in storage/app/public/signatures/{PF_NUMBER}/
            $path = $signatureFile->storeAs($directory, $filename, 'public');
            
            Log::info('Signature uploaded successfully', [
                'pf_number' => $pfNumber,
                'filename' => $filename,
                'path' => $path
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Error storing signature: ' . $e->getMessage(), [
                'pf_number' => $pfNumber
            ]);
            throw $e;
        }
    }



    /**
     * Forward request to HOD queue based on request type.
     */
    private function forwardToHodQueue(UserAccess $userAccess, string $serviceType = null): void
    {
        try {
            // If no specific service type provided, process all request types
            if ($serviceType) {
                $requestTypes = [$serviceType];
            } else {
                // Get request types from the model (already handled as array by casting)
                $requestTypes = $userAccess->getRequestTypesArray();
            }
            
            Log::info("Processing request types for HOD queue", [
                'request_id' => $userAccess->id,
                'service_type_param' => $serviceType,
                'request_types_to_process' => $requestTypes
            ]);
            
            foreach ($requestTypes as $requestType) {
                $queueName = $this->getHodQueueName($requestType);
                
                Log::info("Forwarding request to HOD queue", [
                    'request_id' => $userAccess->id,
                    'request_type' => $requestType,
                    'queue_name' => $queueName,
                    'department_id' => $userAccess->department_id
                ]);

                // TODO: Implement actual queue dispatch
                // dispatch(new ProcessUserAccessRequest($userAccess, $requestType))->onQueue($queueName);
            }
            
        } catch (\Exception $e) {
            Log::error('Error forwarding to HOD queue: ' . $e->getMessage(), [
                'request_id' => $userAccess->id,
                'service_type' => $serviceType,
                'error_trace' => $e->getTraceAsString()
            ]);
        }
    }



    /**
     * Get HOD queue name based on request type.
     */
    private function getHodQueueName(string $requestType): string
    {
        $queueMap = [
            'jeeva_access' => 'hod_jeeva_queue',
            'wellsoft' => 'hod_wellsoft_queue',
            'internet_access_request' => 'hod_internet_queue',
        ];

        return $queueMap[$requestType] ?? 'hod_default_queue';
    }

    /**
     * Check if user has any pending requests.
     */
    public function checkPendingRequests(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check for any pending requests using new status columns
            $pendingRequest = UserAccess::where('user_id', $user->id)
                ->where(function($query) {
                    $query->whereIn('hod_status', ['pending', 'in_progress'])
                          ->orWhere(function($q) {
                              $q->where('hod_status', 'approved')
                                ->whereIn('divisional_status', ['pending', 'in_progress']);
                          })
                          ->orWhere(function($q) {
                              $q->where('hod_status', 'approved')
                                ->where('divisional_status', 'approved')
                                ->whereIn('ict_director_status', ['pending', 'in_progress']);
                          })
                          ->orWhere(function($q) {
                              $q->where('hod_status', 'approved')
                                ->where('divisional_status', 'approved')
                                ->where('ict_director_status', 'approved')
                                ->whereIn('head_it_status', ['pending', 'in_progress']);
                          })
                          ->orWhere(function($q) {
                              $q->where('hod_status', 'approved')
                                ->where('divisional_status', 'approved')
                                ->where('ict_director_status', 'approved')
                                ->where('head_it_status', 'approved')
                                ->whereIn('ict_officer_status', ['pending', 'in_progress']);
                          });
                })
                ->first();
                
            $hasPendingRequest = !is_null($pendingRequest);
            
            $response = [
                'success' => true,
                'has_pending_request' => $hasPendingRequest,
                'message' => $hasPendingRequest 
                    ? 'You have a pending request that needs to be processed before submitting a new one.' 
                    : 'No pending requests found. You can submit a new request.'
            ];
            
            // Include pending request details if found
            if ($hasPendingRequest) {
                // Determine current stage and workflow progress
                $currentStage = $pendingRequest->getNextPendingStageFromColumns();
                $workflowProgress = $pendingRequest->getWorkflowProgressFromColumns();
                
                $response['pending_request'] = [
                    'id' => $pendingRequest->id,
                    'request_id' => 'REQ-' . str_pad($pendingRequest->id, 6, '0', STR_PAD_LEFT),
                    'status' => $pendingRequest->status,
                    'calculated_status' => $pendingRequest->getCalculatedOverallStatus(),
                    'current_stage' => $currentStage,
                    'workflow_progress' => $workflowProgress,
                    'stage_statuses' => [
                        'hod' => $pendingRequest->hod_status ?? 'pending',
                        'divisional' => $pendingRequest->divisional_status ?? 'pending',
                        'ict_director' => $pendingRequest->ict_director_status ?? 'pending',
                        'head_it' => $pendingRequest->head_it_status ?? 'pending',
                        'ict_officer' => $pendingRequest->ict_officer_status ?? 'pending'
                    ],
                    'request_types' => $pendingRequest->request_type,
                    'created_at' => $pendingRequest->created_at,
                    'updated_at' => $pendingRequest->updated_at
                ];
            }
            
            Log::info('Pending request check completed with new status system', [
                'user_id' => $user->id,
                'has_pending_request' => $hasPendingRequest,
                'pending_request_id' => $pendingRequest?->id,
                'current_stage' => $pendingRequest?->getNextPendingStageFromColumns()
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error checking pending requests: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check pending requests.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Helper method to process module arrays with proper filtering (same as BothServiceFormController)
     */
    private function processModuleArray(Request $request, string $fieldName): array
    {
        // Strategy 1: Direct input method
        $input = $request->input($fieldName);
        $result = [];
        
        // Strategy 2: Process input if not empty
        if (!empty($input)) {
            if (is_array($input)) {
                $result = array_filter($input, function($item) {
                    return !empty($item) && $item !== null && $item !== '';
                });
            } elseif (is_string($input)) {
                try {
                    $decoded = json_decode($input, true);
                    if (is_array($decoded)) {
                        $result = array_filter($decoded, function($item) {
                            return !empty($item) && $item !== null && $item !== '';
                        });
                    } else {
                        $result = [$input];
                    }
                } catch (\Exception $e) {
                    $result = [$input];
                }
            }
        }
        
        // Strategy 3: Alternative FormData parsing (array notation)
        if (empty($result)) {
            $formKeys = array_keys($request->all());
            $moduleKeys = array_filter($formKeys, function($key) use ($fieldName) {
                return preg_match('/^' . preg_quote($fieldName, '/') . '\[\d+\]$/', $key);
            });
            
            foreach ($moduleKeys as $key) {
                $value = $request->input($key);
                if (!empty($value) && $value !== null && $value !== '') {
                    $result[] = $value;
                }
            }
            
            if (!empty($moduleKeys)) {
                Log::info('🔄 FormData parsing for ' . $fieldName . ' in UserAccessController', [
                    'found_keys' => $moduleKeys,
                    'extracted_values' => $result
                ]);
            }
        }
        
        // Ensure arrays are properly indexed and cleaned
        return array_values(array_filter($result));
    }

    /**
     * Get user requests by workflow stage.
     */
    public function getRequestsByStage(Request $request, string $stage): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!in_array($stage, ['hod', 'divisional', 'ict_director', 'head_it', 'ict_officer'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stage parameter.'
                ], 400);
            }

            $status = $request->get('status', 'pending'); // pending, approved, rejected
            
            $query = UserAccess::with(['user', 'department'])
                ->where('user_id', $user->id);

            // Apply stage and status filtering
            switch ($stage) {
                case 'hod':
                    $query->where('hod_status', $status);
                    break;
                case 'divisional':
                    $query->where('divisional_status', $status)
                          ->where('hod_status', 'approved');
                    break;
                case 'ict_director':
                    $query->where('ict_director_status', $status)
                          ->where('hod_status', 'approved')
                          ->where('divisional_status', 'approved');
                    break;
                case 'head_it':
                    $query->where('head_it_status', $status)
                          ->where('hod_status', 'approved')
                          ->where('divisional_status', 'approved')
                          ->where('ict_director_status', 'approved');
                    break;
                case 'ict_officer':
                    if ($status === 'implemented') {
                        $query->where('ict_officer_status', 'implemented');
                    } else {
                        $query->where('ict_officer_status', $status)
                              ->where('hod_status', 'approved')
                              ->where('divisional_status', 'approved')
                              ->where('ict_director_status', 'approved')
                              ->where('head_it_status', 'approved');
                    }
                    break;
            }

            $requests = $query->orderBy('updated_at', 'desc')->get();

            // Add workflow progress to each request
            $requests->transform(function ($userAccess) {
                $userAccess->workflow_progress = $userAccess->getWorkflowProgressFromColumns();
                $userAccess->current_stage = $userAccess->getNextPendingStageFromColumns();
                $userAccess->calculated_status = $userAccess->getCalculatedOverallStatus();
                $userAccess->signature_url = $this->signatureService->getSignatureUrl($userAccess->signature_path);
                return $userAccess;
            });

            return response()->json([
                'success' => true,
                'data' => $requests,
                'stage' => $stage,
                'status' => $status,
                'count' => $requests->count(),
                'message' => "Requests at {$stage} stage retrieved successfully."
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving requests by stage: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests by stage.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's workflow statistics.
     */
    public function getWorkflowStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $baseQuery = UserAccess::where('user_id', $user->id);
            
            $stats = [
                'total_requests' => $baseQuery->clone()->count(),
                'stages' => [
                    'hod' => [
                        'pending' => $baseQuery->clone()->where('hod_status', 'pending')->count(),
                        'approved' => $baseQuery->clone()->where('hod_status', 'approved')->count(),
                        'rejected' => $baseQuery->clone()->where('hod_status', 'rejected')->count(),
                    ],
                    'divisional' => [
                        'pending' => $baseQuery->clone()->where('divisional_status', 'pending')
                            ->where('hod_status', 'approved')->count(),
                        'approved' => $baseQuery->clone()->where('divisional_status', 'approved')->count(),
                        'rejected' => $baseQuery->clone()->where('divisional_status', 'rejected')->count(),
                    ],
                    'ict_director' => [
                        'pending' => $baseQuery->clone()->where('ict_director_status', 'pending')
                            ->where('hod_status', 'approved')
                            ->where('divisional_status', 'approved')->count(),
                        'approved' => $baseQuery->clone()->where('ict_director_status', 'approved')->count(),
                        'rejected' => $baseQuery->clone()->where('ict_director_status', 'rejected')->count(),
                    ],
                    'head_it' => [
                        'pending' => $baseQuery->clone()->where('head_it_status', 'pending')
                            ->where('hod_status', 'approved')
                            ->where('divisional_status', 'approved')
                            ->where('ict_director_status', 'approved')->count(),
                        'approved' => $baseQuery->clone()->where('head_it_status', 'approved')->count(),
                        'rejected' => $baseQuery->clone()->where('head_it_status', 'rejected')->count(),
                    ],
                    'ict_officer' => [
                        'pending' => $baseQuery->clone()->where('ict_officer_status', 'pending')
                            ->where('hod_status', 'approved')
                            ->where('divisional_status', 'approved')
                            ->where('ict_director_status', 'approved')
                            ->where('head_it_status', 'approved')->count(),
                        'implemented' => $baseQuery->clone()->where('ict_officer_status', 'implemented')->count(),
                    ]
                ],
                'overall' => [
                    'completed' => $baseQuery->clone()->where('ict_officer_status', 'implemented')->count(),
                    'rejected' => $baseQuery->clone()->where(function($query) {
                        $query->where('hod_status', 'rejected')
                              ->orWhere('divisional_status', 'rejected')
                              ->orWhere('ict_director_status', 'rejected')
                              ->orWhere('head_it_status', 'rejected');
                    })->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Workflow statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving workflow statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get Jeeva users with their access details.
     */
    public function getJeevaUsers(Request $request): JsonResponse
    {
        try {
            // Handle multiple variations of jeeva request type
            return $this->getFilteredUsers($request, ['jeeva_access', 'jeeva', 'Jeeva', 'jeeva_access_request'], 'Jeeva users');
        } catch (\Exception $e) {
            Log::error('Error retrieving Jeeva users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jeeva users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get Wellsoft users with their access details.
     */
    public function getWellsoftUsers(Request $request): JsonResponse
    {
        try {
            // Handle multiple variations of wellsoft request type
            return $this->getFilteredUsers($request, ['wellsoft', 'wellsoft_access', 'Wellsoft', 'wellsoft_access_request'], 'Wellsoft users');
        } catch (\Exception $e) {
            Log::error('Error retrieving Wellsoft users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Wellsoft users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get Internet users with their access details.
     */
    public function getInternetUsers(Request $request): JsonResponse
    {
        try {
            // Handle multiple variations of internet request type
            return $this->getFilteredUsers($request, ['internet_access_request', 'internet', 'Internet', 'internet_access'], 'Internet users');
        } catch (\Exception $e) {
            Log::error('Error retrieving Internet users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Internet users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to get filtered users by request type.
     * @param Request $request
     * @param string|array $requestTypes - Single type or array of type variations to match
     * @param string $userTypeName
     */
    private function getFilteredUsers(Request $request, string|array $requestTypes, string $userTypeName): JsonResponse
    {
        $query = UserAccess::with(['user', 'department', 'signatures']);
        
        // Handle single type or array of types
        $types = is_array($requestTypes) ? $requestTypes : [$requestTypes];
        
        // Build query to match any of the request type variations
        $query->where(function ($q) use ($types) {
            foreach ($types as $type) {
                $q->orWhereJsonContains('request_type', $type);
            }
        });
        
        // Note: We now show ALL records to admin including cancelled ones
        // The status filter below can be used to filter by specific statuses

        // Apply search filter
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pf_number', 'like', "%{$search}%")
                  ->orWhere('staff_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('department', function ($dept) use ($search) {
                      $dept->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $statusFilter = $request->status;
            
            switch ($statusFilter) {
                case 'pending':
                    $query->where(function($q) {
                        $q->where('hod_status', 'pending')
                          ->orWhere(function($q2) {
                              $q2->where('hod_status', 'approved')
                                 ->where('divisional_status', 'pending');
                          })
                          ->orWhere(function($q3) {
                              $q3->where('hod_status', 'approved')
                                 ->where('divisional_status', 'approved')
                                 ->where('ict_director_status', 'pending');
                          })
                          ->orWhere(function($q4) {
                              $q4->where('hod_status', 'approved')
                                 ->where('divisional_status', 'approved')
                                 ->where('ict_director_status', 'approved')
                                 ->where('head_it_status', 'pending');
                          })
                          ->orWhere(function($q5) {
                              $q5->where('hod_status', 'approved')
                                 ->where('divisional_status', 'approved')
                                 ->where('ict_director_status', 'approved')
                                 ->where('head_it_status', 'approved')
                                 ->where('ict_officer_status', 'pending');
                          });
                    });
                    break;
                case 'approved':
                case 'completed':
                case 'implemented':
                    $query->where('ict_officer_status', 'implemented');
                    break;
                case 'rejected':
                    $query->where(function($q) {
                        $q->where('hod_status', 'rejected')
                          ->orWhere('divisional_status', 'rejected')
                          ->orWhere('ict_director_status', 'rejected')
                          ->orWhere('head_it_status', 'rejected');
                    });
                    break;
                case 'cancelled':
                case 'inactive':
                    // Show only cancelled/deleted requests
                    $query->where('hod_status', 'cancelled');
                    break;
                case 'active':
                    // Exclude cancelled requests (show only active ones)
                    $query->where(function($q) {
                        $q->whereNull('hod_status')
                          ->orWhere('hod_status', '!=', 'cancelled');
                    });
                    break;
            }
        }

        // Sort by created_at desc by default
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = min($request->get('perPage', $request->get('per_page', 15)), 100);
        $users = $query->paginate($perPage);

        // Use the first type for transformation (they're all variations of the same type)
        $primaryType = is_array($requestTypes) ? $requestTypes[0] : $requestTypes;
        
        // Transform the data to match the expected format
        $transformedData = $users->getCollection()->map(function ($userAccess) use ($primaryType) {
            return $this->transformUserAccessData($userAccess, $primaryType);
        });

        return response()->json([
            'success' => true,
            'items' => $transformedData,
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'message' => "{$userTypeName} retrieved successfully."
        ]);
    }

    /**
     * Transform user access data to match the expected frontend format.
     */
    private function transformUserAccessData($userAccess, string $requestType): array
    {
        $data = [
            'id' => $userAccess->id,
            'requestNumber' => 'MLG-REQ' . str_pad($userAccess->id, 6, '0', STR_PAD_LEFT),
            'pfNumber' => $userAccess->pf_number,
            'staffName' => $userAccess->staff_name,
            'employeeFullName' => $userAccess->staff_name, // Alias for internet users
            'phoneNumber' => $userAccess->phone_number,
            'department' => $userAccess->department ? $userAccess->department->name : 'N/A',
            'signature' => ($userAccess->signature_path || $userAccess->signatures->isNotEmpty()) ? 'Available' : 'N/A',
            'date' => $userAccess->created_at ? $userAccess->created_at->format('Y-m-d') : 'N/A',
            'requestType' => $requestType,
            'accessType' => $userAccess->access_type ?? 'permanent',
            'status' => $this->getDisplayStatus($userAccess),
        ];

        // Add temporary date if applicable
        if ($userAccess->temporary_until) {
            $data['tempDate'] = [
                'day' => date('d', strtotime($userAccess->temporary_until)),
                'month' => date('m', strtotime($userAccess->temporary_until)),
                'year' => date('Y', strtotime($userAccess->temporary_until))
            ];
        } else {
            $data['tempDate'] = null;
        }

        // Add modules based on request type (handle variations)
        $normalizedType = strtolower(str_replace(['_access', '_request', '_access_request'], '', $requestType));
        
        if (in_array($normalizedType, ['jeeva'])) {
            $data['selectedModules'] = $userAccess->jeeva_modules_selected ?? $userAccess->jeeva_modules ?? [];
        } elseif (in_array($normalizedType, ['wellsoft'])) {
            $data['selectedModules'] = $userAccess->wellsoft_modules_selected ?? $userAccess->wellsoft_modules ?? [];
        } elseif (in_array($normalizedType, ['internet'])) {
            $data['internetPurposes'] = $userAccess->internet_purposes ?? $userAccess->purpose ?? [];
            $data['designation'] = 'Staff'; // Default designation
        }

        // Add approval information
        $data['approvals'] = [
            'userHod' => ['name' => $userAccess->hod_name ?? 'N/A'],
            'hod' => ['comment' => $userAccess->hod_comments ?? 'N/A'],
            'divisionalDirector' => ['name' => $userAccess->divisional_director_name ?? 'N/A'],
            'directorICT' => ['name' => $userAccess->ict_director_name ?? 'N/A']
        ];

        // Add implementation information
        $data['implementation'] = [
            'hod(IT)' => ['name' => $userAccess->head_it_name ?? 'N/A'],
            'ICT' => ['name' => $userAccess->ict_officer_name ?? 'N/A']
        ];

        // Add direct access to specific fields for table columns
        // Show appropriate status for ICT officer based on workflow stage
        if ($userAccess->ict_officer_status === 'implemented' && $userAccess->ict_officer_name) {
            $data['ict_officer_name'] = $userAccess->ict_officer_name;
        } elseif ($userAccess->ict_officer_status === 'pending' && $userAccess->ict_officer_name) {
            $data['ict_officer_name'] = $userAccess->ict_officer_name . ' (In Progress)';
        } elseif (in_array($userAccess->head_it_status, ['pending', 'approved']) && 
                  $userAccess->ict_officer_status === 'pending') {
            $data['ict_officer_name'] = 'Pending Assignment';
        } else {
            $data['ict_officer_name'] = $userAccess->ict_officer_name ?? 'Not Assigned';
        }
        
        $data['head_it_name'] = $userAccess->head_it_name ?? 'N/A';
        $data['hod_name'] = $userAccess->hod_name ?? 'N/A';
        $data['divisional_director_name'] = $userAccess->divisional_director_name ?? 'N/A';
        $data['ict_director_name'] = $userAccess->ict_director_name ?? 'N/A';
        $data['hod_comments'] = $userAccess->hod_comments ?? 'N/A';

        return $data;
    }

    /**
     * Get display status for user access record.
     */
    private function getDisplayStatus($userAccess): string
    {
        // Check for cancelled status first
        if ($userAccess->hod_status === 'cancelled') {
            return 'Cancelled';
        }
        
        if ($userAccess->ict_officer_status === 'implemented') {
            return 'Completed';
        }

        if (in_array('rejected', [
            $userAccess->hod_status,
            $userAccess->divisional_status,
            $userAccess->ict_director_status,
            $userAccess->head_it_status
        ])) {
            return 'Rejected';
        }

        // Determine current pending stage
        if ($userAccess->hod_status === 'pending') {
            return 'Pending HOD Approval';
        } elseif ($userAccess->hod_status === 'approved' && $userAccess->divisional_status === 'pending') {
            return 'Pending Divisional Approval';
        } elseif ($userAccess->divisional_status === 'approved' && $userAccess->ict_director_status === 'pending') {
            return 'Pending ICT Director Approval';
        } elseif ($userAccess->ict_director_status === 'approved' && $userAccess->head_it_status === 'pending') {
            return 'Pending Head of IT Approval';
        } elseif ($userAccess->head_it_status === 'approved' && $userAccess->ict_officer_status === 'pending') {
            return 'Pending ICT Officer Implementation';
        }

        return 'Pending';
    }
}
