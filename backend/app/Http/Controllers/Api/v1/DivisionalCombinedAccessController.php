<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\UserAccess;
use App\Models\User;
use App\Models\Department;
use App\Services\UserAccessWorkflowService;
use App\Services\SmsModule;
use App\Traits\HandlesStatusQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DivisionalCombinedAccessController extends Controller
{
    use HandlesStatusQueries;

    protected $workflowService;

    public function __construct(UserAccessWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }
    /**
     * Get all HOD-approved combined access requests for Divisional Director approval
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('Divisional Combined Access: Fetching requests', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Unknown',
                'user_email' => auth()->user()->email ?? 'Unknown',
                'filters' => $request->all()
            ]);

            $query = UserAccess::with(['user', 'department'])
                ->whereNotNull('request_type')
                ->where('hod_status', 'approved'); // Only show HOD-approved requests for Divisional approval

            // DEPARTMENT FILTERING: Divisional Director only sees requests from their department(s)
            $currentUser = auth()->user();
            $divisionalDepartmentIds = $currentUser->departmentsAsDivisionalDirector()->pluck('id')->toArray();
            
            if (!empty($divisionalDepartmentIds)) {
                $query->whereIn('department_id', $divisionalDepartmentIds);

                // Avoid heavy counting queries on every request; keep extra stats only when debugging.
                if (config('app.debug')) {
                    try {
                        $totalRequestsCount = UserAccess::whereNotNull('request_type')->count();
                        $filteredRequestsCount = UserAccess::whereNotNull('request_type')
                            ->whereIn('department_id', $divisionalDepartmentIds)
                            ->count();

                        Log::debug('Divisional Department Filter Applied', [
                            'user_id' => $currentUser->id,
                            'divisional_department_ids' => $divisionalDepartmentIds,
                            'requests_before_dept_filter' => $totalRequestsCount,
                            'requests_after_dept_filter' => $filteredRequestsCount
                        ]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            } else {
                // If user is not Divisional Director of any department, show no requests
                Log::warning('Divisional Access Attempt: User is not Divisional Director of any department', [
                    'user_id' => $currentUser->id,
                    'user_name' => $currentUser->name,
                    'user_email' => $currentUser->email,
                    'user_roles' => $currentUser->roles()->pluck('name')->toArray() ?? 'No roles loaded'
                ]);
                $query->whereRaw('1 = 0'); // This will return no results
            }

            // Apply filters
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('staff_name', 'like', "%{$searchTerm}%")
                        ->orWhere('pf_number', 'like', "%{$searchTerm}%")
                        ->orWhereHas('department', function ($dq) use ($searchTerm) {
                            $dq->where('name', 'like', "%{$searchTerm}%");
                        })
                        ->orWhere('id', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('department')) {
                $query->where('department_id', $request->department);
            }

            // Order by HOD approval date (FIFO - oldest HOD approval first)
            $query->orderBy('hod_approved_at', 'asc');

            $perPage = $request->get('per_page', 50);
            $requests = $query->paginate($perPage);

            // Transform the data for frontend
            $transformedData = $requests->through(function ($request) {
                return $this->transformRequestData($request);
            });

            Log::info('Divisional Combined Access: Requests retrieved successfully', [
                'count' => $requests->total()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'HOD-approved combined access requests retrieved successfully',
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            Log::error('Divisional Combined Access: Error fetching requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve HOD-approved combined access requests',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific HOD-approved combined access request by ID
     */
    public function show($id): JsonResponse
    {
        try {
            Log::info('Divisional Combined Access: Fetching specific request', [
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);

            $request = UserAccess::with(['user', 'department'])
                ->findOrFail($id);

            // DEPARTMENT AUTHORIZATION: Ensure Divisional Director can only access requests from their department(s)
            $currentUser = auth()->user();
            $divisionalDepartmentIds = $currentUser->departmentsAsDivisionalDirector()->pluck('id')->toArray();
            
            if (!empty($divisionalDepartmentIds) && !in_array($request->department_id, $divisionalDepartmentIds)) {
                Log::warning('Divisional Access Denied: Request not from Divisional Director department', [
                    'user_id' => $currentUser->id,
                    'request_department_id' => $request->department_id,
                    'divisional_department_ids' => $divisionalDepartmentIds
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You can only view requests from your department.',
                    'error' => 'Unauthorized access'
                ], 403);
            }

            // Allow viewing all requests for complete visibility and history
            // Divisional directors can see the full request lifecycle

            $transformedData = $this->transformRequestData($request);

            Log::info('Divisional Combined Access: Request retrieved successfully', [
                'request_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request retrieved successfully',
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            Log::error('Divisional Combined Access: Error fetching request', [
                'request_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Request not found',
                'error' => app()->environment('local') ? $e->getMessage() : 'Request not found'
            ], 404);
        }
    }

    /**
     * Update Divisional Director approval status
     */
    public function updateApproval(Request $request, $id): JsonResponse
    {
        try {
            Log::info('Divisional Combined Access: Updating approval', [
                'request_id' => $id,
                'user_id' => auth()->id(),
                'approval_data' => $request->all()
            ]);

            $userAccessRequest = UserAccess::findOrFail($id);

            // DEPARTMENT AUTHORIZATION: Ensure Divisional Director can only approve requests from their department(s)
            $currentUser = auth()->user();
            $divisionalDepartmentIds = $currentUser->departmentsAsDivisionalDirector()->pluck('id')->toArray();
            
            if (!empty($divisionalDepartmentIds) && !in_array($userAccessRequest->department_id, $divisionalDepartmentIds)) {
                Log::warning('Divisional Approval Denied: Request not from Divisional Director department', [
                    'user_id' => $currentUser->id,
                    'request_id' => $id,
                    'request_department_id' => $userAccessRequest->department_id,
                    'divisional_department_ids' => $divisionalDepartmentIds
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You can only approve requests from your department.',
                    'error' => 'Unauthorized access'
                ], 403);
            }

            // Ensure request is HOD-approved before divisional can approve it
            if ($userAccessRequest->hod_status !== 'approved') {
                Log::warning('Divisional Approval Denied: Request not HOD-approved', [
                    'request_id' => $id,
                    'hod_status' => $userAccessRequest->hod_status,
                    'status' => $userAccessRequest->status
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be HOD-approved before divisional approval.',
                    'error' => 'Invalid request status for divisional approval'
                ], 400);
            }

            // Validate the approval data
            $validatedData = $request->validate([
                'divisional_status' => 'required|in:approved,rejected',
                'divisional_comments' => 'nullable|string|max:1000',
                'divisional_name' => 'nullable|string|max:255',
                'divisional_approved_at' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Update the request - automatically capture authenticated user's name
            $currentUser = auth()->user();
            $updateData = [
                'status' => $validatedData['divisional_status'] === 'approved' ? 'divisional_approved' : 'divisional_rejected',
                'divisional_status' => $validatedData['divisional_status'], // Set the new divisional_status column
                'divisional_comments' => $validatedData['divisional_comments'] ?? '',
                'divisional_name' => $currentUser->name, // Always use authenticated user's name
                'divisional_approved_by' => $currentUser->id,
                'divisional_approved_by_name' => $currentUser->name,
                'divisional_approved_at' => now(),
                'updated_at' => now()
            ];

            $userAccessRequest->update($updateData);

            DB::commit();
            
            // Send SMS notifications based on status
            try {
                $sms = app(SmsModule::class);
                
                if ($validatedData['divisional_status'] === 'approved') {
                    // Get next approver (ICT Director)
                    $nextApprover = User::whereHas('roles', fn($q) => 
                        $q->where('name', 'ict_director')
                    )->first();
                    
                    $sms->notifyRequestApproved(
                        $userAccessRequest,
                        auth()->user(),
                        'divisional',
                        $nextApprover
                    );
                    
                    Log::info('Divisional approval SMS notifications sent', [
                        'request_id' => $id,
                        'next_approver' => $nextApprover ? $nextApprover->name : 'None'
                    ]);
                } elseif ($validatedData['divisional_status'] === 'rejected') {
                    // Send rejection SMS to staff
                    $sms->notifyRequestRejected(
                        $userAccessRequest,
                        auth()->user(),
                        'divisional',
                        $validatedData['divisional_comments'] ?? null
                    );
                    
                    Log::info('Divisional rejection SMS notification sent', [
                        'request_id' => $id
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Divisional SMS notification failed', [
                    'request_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Divisional Combined Access: Approval updated successfully', [
                'request_id' => $id,
                'status' => $validatedData['divisional_status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request approval updated successfully',
                'data' => $this->transformRequestData($userAccessRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Divisional Combined Access: Error updating approval', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update request approval',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cancel a combined access request (Divisional Director action)
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            Log::info('Divisional Combined Access: Cancelling request', [
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);

            $userAccessRequest = UserAccess::findOrFail($id);

            $validatedData = $request->validate([
                'reason' => 'required|string|max:1000'
            ]);

            DB::beginTransaction();

            $userAccessRequest->update([
                'status' => 'cancelled',
                'cancellation_reason' => $validatedData['reason'],
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Send SMS notification to requester about cancellation
            try {
                $sms = app(SmsModule::class);
                $sms->notifyRequestCancelled(
                    $userAccessRequest,
                    auth()->user(),
                    $validatedData['reason']
                );
                
                Log::info('Divisional cancellation SMS notification sent', [
                    'request_id' => $id
                ]);
            } catch (\Exception $e) {
                Log::warning('Divisional cancellation SMS notification failed', [
                    'request_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Divisional Combined Access: Request cancelled successfully', [
                'request_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully',
                'data' => $this->transformRequestData($userAccessRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Divisional Combined Access: Error cancelling request', [
                'request_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for Divisional Director dashboard
     */
    public function statistics(): JsonResponse
    {
        try {
            Log::info('Divisional Combined Access: Fetching statistics', [
                'user_id' => auth()->id()
            ]);

            // DEPARTMENT FILTERING: Divisional Director statistics only for their department(s)
            $currentUser = auth()->user();
            $divisionalDepartmentIds = $currentUser->departmentsAsDivisionalDirector()->pluck('id')->toArray();
            
            $baseQuery = UserAccess::query();
            if (!empty($divisionalDepartmentIds)) {
                $baseQuery->whereIn('department_id', $divisionalDepartmentIds);
            } else {
                // If not Divisional Director of any department, return zero stats
                $baseQuery->whereRaw('1 = 0');
            }
            
            $stats = [
                'pending' => (clone $baseQuery)->where(function($q) { $q->whereNull('hod_status')->orWhere('hod_status', 'pending'); })->count(),
                'hodApproved' => (clone $baseQuery)->where('hod_status', 'approved')->count(),
                'pendingDivisional' => (clone $baseQuery)->where('hod_status', 'approved')
                    ->where(function($q) { $q->whereNull('divisional_status')->orWhere('divisional_status', 'pending'); })->count(),
                'divisionalApproved' => (clone $baseQuery)->where('divisional_status', 'approved')->count(),
                'divisionalRejected' => (clone $baseQuery)->where('divisional_status', 'rejected')->count(),
                'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                'implemented' => (clone $baseQuery)->where('status', 'implemented')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
                'total' => (clone $baseQuery)->count(),
                'thisMonth' => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'lastMonth' => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year)
                    ->count(),
                'departments' => !empty($divisionalDepartmentIds) ? Department::whereIn('id', $divisionalDepartmentIds)->pluck('name')->toArray() : []
            ];

            Log::info('Divisional Combined Access: Statistics retrieved successfully', $stats);

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Divisional Combined Access: Error fetching statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'data' => [
                    'pending' => 0,
                    'hodApproved' => 0,
                    'pendingDivisional' => 0,
                    'divisionalApproved' => 0,
                    'divisionalRejected' => 0,
                    'approved' => 0,
                    'implemented' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'total' => 0,
                    'thisMonth' => 0,
                    'lastMonth' => 0
                ]
            ]);
        }
    }

    /**
     * Transform request data for frontend consumption
     */
    private function transformRequestData($request): array
    {
        return [
            'id' => $request->id,
            'request_id' => 'REQ-' . str_pad($request->id, 6, '0', STR_PAD_LEFT),
            'pf_number' => $request->pf_number,
            'staff_name' => $request->staff_name,
            'full_name' => $request->staff_name, // Alias for compatibility
            'phone' => $request->phone_number,
            'phone_number' => $request->phone_number,
            'department' => $request->department?->name ?? 'N/A',
            'department_id' => $request->department_id,
            'department_name' => $request->department?->name ?? 'N/A',
            'request_type' => $request->getRequestTypesArray(),
            'request_type_display' => $request->request_type_name,
            'request_types' => $request->getRequestTypesArray(), // Array format
            'purpose' => $request->purpose,
            
            // Include module data for conditional display
            'wellsoft_modules' => $request->wellsoft_modules,
            'jeeva_modules' => $request->jeeva_modules,
            'internet_purposes' => $request->internet_purposes,
            'access_type' => $request->access_type,
            'temporary_until' => $request->temporary_until?->format('Y-m-d'),
            
            'status' => $request->status,
            'divisional_status' => $request->status,  // Alias for frontend
            'status_display' => $request->status_name,
            'signature_path' => $request->signature_path,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
            'submission_date' => $request->created_at,
            
            // HOD approval info (for context)
            'hod_comments' => $request->hod_comments ?? '',
            'hod_name' => $request->hod_name ?? '',
            'hod_approved_at' => $request->hod_approved_at,
            'hod_approval_date' => $request->hod_approved_at, // Alias for frontend
            
            // Divisional approval info
            'divisional_comments' => $request->divisional_comments ?? '',
            'divisional_name' => $request->divisional_name ?? '',
            'divisional_approved_at' => $request->divisional_approved_at,
            
            // Cancellation metadata for UI logic
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_by' => $request->cancelled_by,
            'cancelled_at' => $request->cancelled_at,
            'cancelled_by_name' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->name ?? 'System') : null,
            'cancelled_by_role' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->roles->first()?->name ?? null) : null,

            // Approval workflow status
            'hod_approval_status' => $this->getApprovalStatus($request, 'hod'),
            'divisional_approval_status' => $this->getApprovalStatus($request, 'divisional'),
            'dict_approval_status' => $this->getApprovalStatus($request, 'dict'),
            'head_it_approval_status' => $this->getApprovalStatus($request, 'head_it'),
            'ict_approval_status' => $this->getApprovalStatus($request, 'ict'),
            
            // SMS notification status tracking
            'sms_to_hod_status' => $request->sms_to_hod_status ?? 'pending',
            'sms_to_divisional_status' => $request->sms_to_divisional_status ?? 'pending',
            'sms_to_ict_director_status' => $request->sms_to_ict_director_status ?? 'pending',
            'sms_to_head_it_status' => $request->sms_to_head_it_status ?? 'pending',
            'sms_to_requester_status' => $request->sms_to_requester_status ?? 'pending',
        ];
    }

    /**
     * Get approval status for a specific role
     */
    private function getApprovalStatus($request, $role): string
    {
        switch ($role) {
            case 'hod':
                return $request->hod_status ?? 'pending';
            case 'divisional':
                return $request->divisional_status ?? 'pending';
            case 'dict':
            case 'ict_director':
                return $request->ict_director_status ?? 'pending';
            case 'head_it':
                return $request->head_it_status ?? 'pending';
            case 'ict':
            case 'ict_officer':
                return $request->ict_officer_status === 'implemented' ? 'approved' : ($request->ict_officer_status ?? 'pending');
            default:
                return 'pending';
        }
    }

    /**
     * Get detailed timeline for a specific access request
     */
    public function getAccessRequestTimeline($requestId)
    {
        try {
            $user = Auth::user();
            
            // Check if user has permission (Divisional Director)
            if (!$user->hasRole('divisional_director')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: User is not a Divisional Director'
                ], 403);
            }

            Log::info('DivisionalCombinedAccess: Getting access request timeline', [
                'user_id' => $user->id,
                'request_id' => $requestId
            ]);

            // Find the access request with all related data
            $request = UserAccess::with([
                'department',
                'user',
                'ictTaskAssignments.ictOfficer',
                'ictTaskAssignments.assignedBy'
            ])
            ->where('id', $requestId)
            ->firstOrFail();

            // DEPARTMENT AUTHORIZATION: Ensure Divisional Director can only access requests from their department(s)
            $divisionalDepartmentIds = $user->departmentsAsDivisionalDirector()->pluck('id')->toArray();
            
            if (!empty($divisionalDepartmentIds) && !in_array($request->department_id, $divisionalDepartmentIds)) {
                Log::warning('Divisional Timeline Access Denied: Request not from Divisional Director department', [
                    'user_id' => $user->id,
                    'request_department_id' => $request->department_id,
                    'divisional_department_ids' => $divisionalDepartmentIds
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You can only view timelines for requests from your department.'
                ], 403);
            }

            // Build comprehensive request data for timeline
            $timelineData = [
                'request' => [
                    'id' => $request->id,
                    'staff_name' => $request->staff_name,
                    'pf_number' => $request->pf_number,
                    'phone_number' => $request->phone_number,
                    'department' => [
                        'id' => $request->department->id ?? null,
                        'name' => $request->department->name ?? 'Unknown Department'
                    ],
                    'signature_path' => $request->signature_path,
                    
                    // Request details
                    'request_type' => $request->request_type,
                    'request_type_name' => $request->request_type_name,
                    'access_type' => $request->access_type,
                    'access_type_name' => $request->access_type_name,
                    'temporary_until' => $request->temporary_until,
                    'internet_purposes' => $request->internet_purposes,
                    'wellsoft_modules_selected' => $request->wellsoft_modules_selected,
                    'jeeva_modules_selected' => $request->jeeva_modules_selected,
                    
                    // HOD approval stage
                    'hod_status' => $request->hod_status,
                    'hod_name' => $request->hod_name,
                    'hod_comments' => $request->hod_comments,
                    'hod_signature_path' => $request->hod_signature_path,
                    'hod_approved_at' => $request->hod_approved_at,
                    'hod_approved_by' => $request->hod_approved_by,
                    'hod_approved_by_name' => $request->hod_approved_by_name,
                    
                    // Divisional Director approval stage
                    'divisional_status' => $request->divisional_status,
                    'divisional_director_name' => $request->divisional_director_name,
                    'divisional_director_comments' => $request->divisional_director_comments,
                    'divisional_director_signature_path' => $request->divisional_director_signature_path,
                    'divisional_approved_at' => $request->divisional_approved_at,
                    
                    // ICT Director approval stage
                    'ict_director_status' => $request->ict_director_status,
                    'ict_director_name' => $request->ict_director_name,
                    'ict_director_comments' => $request->ict_director_comments,
                    'ict_director_signature_path' => $request->ict_director_signature_path,
                    'ict_director_approved_at' => $request->ict_director_approved_at,
                    'ict_director_rejection_reasons' => $request->ict_director_rejection_reasons,
                    
                    // Head of IT approval stage
                    'head_it_status' => $request->head_it_status,
                    'head_it_name' => $request->head_it_name,
                    'head_it_comments' => $request->head_it_comments,
                    'head_it_signature_path' => $request->head_it_signature_path,
                    'head_it_approved_at' => $request->head_it_approved_at,
                    
                    // ICT Officer implementation stage
                    'ict_officer_status' => $request->ict_officer_status,
                    'ict_officer_name' => $request->ict_officer_name,
                    'ict_officer_user_id' => $request->ict_officer_user_id,
                    'ict_officer_assigned_at' => $request->ict_officer_assigned_at,
                    'ict_officer_started_at' => $request->ict_officer_started_at,
                    'ict_officer_implemented_at' => $request->ict_officer_implemented_at,
                    'ict_officer_comments' => $request->ict_officer_comments,
                    'ict_officer_signature_path' => $request->ict_officer_signature_path,
                    'implementation_comments' => $request->implementation_comments,
                    
                    // Cancellation/rejection info
                    'cancelled_at' => $request->cancelled_at,
                    'cancelled_by' => $request->cancelled_by,
                    'cancelled_by_name' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->name ?? 'System') : null,
                    'cancelled_by_role' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->roles->first()?->name ?? null) : null,
                    'cancellation_reason' => $request->cancellation_reason,
                    
                    // Timestamps
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at
                ],
                
                // ICT Task Assignment details (for more detailed implementation tracking)
                'ict_assignments' => $request->ictTaskAssignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'status' => $assignment->status,
                        'status_label' => $assignment->status_label,
                        'ict_officer' => [
                            'id' => $assignment->ictOfficer->id ?? null,
                            'name' => $assignment->ictOfficer->name ?? 'Unknown Officer',
                            'email' => $assignment->ictOfficer->email ?? null
                        ],
                        'assigned_by' => [
                            'id' => $assignment->assignedBy->id ?? null,
                            'name' => $assignment->assignedBy->name ?? 'System',
                            'email' => $assignment->assignedBy->email ?? null
                        ],
                        'assigned_at' => $assignment->assigned_at,
                        'started_at' => $assignment->started_at,
                        'completed_at' => $assignment->completed_at,
                        'assignment_notes' => $assignment->assignment_notes,
                        'progress_notes' => $assignment->progress_notes,
                        'completion_notes' => $assignment->completion_notes,
                        'created_at' => $assignment->created_at,
                        'updated_at' => $assignment->updated_at
                    ];
                })->values()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Access request timeline retrieved successfully',
                'data' => $timelineData
            ]);

        } catch (\Exception $e) {
            Log::error('DivisionalCombinedAccess: Error getting access request timeline', [
                'user_id' => Auth::id(),
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve access request timeline',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
