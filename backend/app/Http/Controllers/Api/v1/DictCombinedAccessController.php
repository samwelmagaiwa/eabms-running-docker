<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\UserAccess;
use App\Models\User;
use App\Models\Department;
use App\Services\StatusMigrationService;
use App\Services\SmsModule;
use App\Traits\HandlesStatusQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DictCombinedAccessController extends Controller
{
    use HandlesStatusQueries;
    
    /**
     * Helper method to format signature status with visual indicators
     */
    private function formatSignatureStatus($signaturePath, $approvalDate = null, $approverName = null, $status = null)
    {
        if (!empty($signaturePath)) {
            return [
                'signature_status' => 'Signed',
                'signature_status_color' => 'green',
                'has_signature_file' => true,
                'signature_display' => 'Signed'
            ];
        }
        
        return [
            'signature_status' => 'No signature',
            'signature_status_color' => 'red',
            'has_signature_file' => false,
            'signature_display' => 'No signature'
        ];
    }
    
    protected $statusMigrationService;
    
    public function __construct(StatusMigrationService $statusMigrationService)
    {
        $this->statusMigrationService = $statusMigrationService;
    }
    /**
     * Get combined access requests for ICT Director or Head of IT approval based on user role
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $isHeadOfIT = $user->hasRole('head_of_it');
            
            Log::info('Combined Access: Fetching requests', [
                'user_id' => $user->id,
                'user_name' => $user->name ?? 'Unknown',
                'user_email' => $user->email ?? 'Unknown',
                'role' => $isHeadOfIT ? 'head_of_it' : 'ict_director',
                'filters' => $request->all()
            ]);

            $query = UserAccess::with(['user', 'department'])
                ->whereNotNull('request_type')
                // Must have HOD and Divisional approved, but allow 'skipped' for ICT Officer submissions
                ->whereIn('hod_status', ['approved', 'skipped'])
                ->whereIn('divisional_status', ['approved', 'skipped']);

            if ($isHeadOfIT) {
                // Head of IT sees requests that ICT Director has approved and are pending Head IT approval
                $query->where('ict_director_status', 'approved') // ICT Director already approved
                      ->where(function ($q) {
                          $q->where('head_it_status', 'pending') // Pending Head IT approval
                            ->orWhere('head_it_status', 'approved') // Already approved by Head IT
                            ->orWhere('head_it_status', 'rejected'); // Already rejected by Head IT
                      });
            } else {
                // ICT Director sees requests pending their approval or already processed by them
                $query->where(function ($q) {
                    $q->where('ict_director_status', 'pending') // Pending ICT Director approval
                      ->orWhere('ict_director_status', 'approved') // Already approved by ICT Director
                      ->orWhere('ict_director_status', 'rejected'); // Already rejected by ICT Director
                });
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
                // Map frontend status values to appropriate granular status filtering based on user role
                $statusFilter = $request->status;
                
                if ($isHeadOfIT) {
                    switch ($statusFilter) {
                        case 'pending':
                            $query->where('head_it_status', 'pending');
                            break;
                        case 'approved':
                            $query->where('head_it_status', 'approved');
                            break;
                        case 'rejected':
                            $query->where('head_it_status', 'rejected');
                            break;
                        default:
                            $query->where('head_it_status', $statusFilter);
                            break;
                    }
                } else {
                    switch ($statusFilter) {
                        case 'pending':
                            $query->where('ict_director_status', 'pending');
                            break;
                        case 'approved':
                            $query->where('ict_director_status', 'approved');
                            break;
                        case 'rejected':
                            $query->where('ict_director_status', 'rejected');
                            break;
                        default:
                            // For backward compatibility, also check legacy status column
                            $query->where(function($q) use ($statusFilter) {
                                $q->where('ict_director_status', $statusFilter)
                                  ->orWhere('status', $statusFilter);
                            });
                            break;
                    }
                }
            }

            if ($request->filled('department')) {
                $query->where('department_id', $request->department);
            }

            // Order by approval priority based on user role
            if ($isHeadOfIT) {
                // Head of IT: pending Head IT items first, then by ICT Director approval date
                $query->orderByRaw("CASE WHEN head_it_status = 'pending' THEN 0 ELSE 1 END")
                      ->orderBy('ict_director_approved_at', 'asc');
            } else {
                // ICT Director: pending ICT Director items first, then by divisional approval date
                $query->orderByRaw("CASE WHEN ict_director_status = 'pending' THEN 0 ELSE 1 END")
                      ->orderBy('divisional_approved_at', 'asc');
            }

            $perPage = $request->get('per_page', 50);
            $requests = $query->paginate($perPage);

            // Transform the data for frontend
            $transformedData = $requests->through(function ($request) {
                return $this->transformRequestData($request);
            });

            Log::info('Combined Access: Requests retrieved successfully', [
                'role' => $isHeadOfIT ? 'head_of_it' : 'ict_director',
                'count' => $requests->total()
            ]);

            $roleName = $isHeadOfIT ? 'Head of IT' : 'ICT Director';
            return response()->json([
                'success' => true,
                'message' => "Combined access requests for {$roleName} retrieved successfully",
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            Log::error('ICT Director Combined Access: Error fetching requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Divisional Director-approved combined access requests',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific Divisional Director-approved combined access request by ID
     */
    public function show($id): JsonResponse
    {
        try {
            Log::info('ICT Director Combined Access: Fetching specific request', [
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);

            $request = UserAccess::with(['user', 'department'])
                ->findOrFail($id);

            // ICT Director can view all requests (for complete visibility and history)
            $transformedData = $this->transformRequestData($request);

            Log::info('ICT Director Combined Access: Request retrieved successfully', [
                'request_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request retrieved successfully',
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            Log::error('ICT Director Combined Access: Error fetching request', [
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
     * Update approval status for ICT Director or Head of IT based on user role
     */
    public function updateApproval(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $isHeadOfIT = $user->hasRole('head_of_it');
            
            Log::info('Combined Access: Updating approval', [
                'request_id' => $id,
                'user_id' => $user->id,
                'role' => $isHeadOfIT ? 'head_of_it' : 'ict_director',
                'approval_data' => $request->all()
            ]);

            $userAccessRequest = UserAccess::findOrFail($id);

            // Validate workflow state based on user role
            if ($isHeadOfIT) {
                // Head of IT approval requirements
                // Allow 'skipped' for hod and divisional (ICT Officer submissions skip HOD+Divisional;
                // departments without a divisional director skip divisional)
                $hodOk    = in_array($userAccessRequest->hod_status, ['approved', 'skipped'], true);
                $divOk    = in_array($userAccessRequest->divisional_status, ['approved', 'skipped'], true);
                $dictOk   = $userAccessRequest->ict_director_status === 'approved';
                $headPend = $userAccessRequest->head_it_status === 'pending';

                if (!$hodOk || !$divOk || !$dictOk || !$headPend) {
                        
                    Log::warning('Head of IT Approval Denied: Invalid approval workflow state', [
                        'request_id' => $id,
                        'hod_status' => $userAccessRequest->hod_status,
                        'divisional_status' => $userAccessRequest->divisional_status,
                        'ict_director_status' => $userAccessRequest->ict_director_status,
                        'head_it_status' => $userAccessRequest->head_it_status
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Request must be approved by ICT Director before Head of IT can process it.',
                        'error' => 'Invalid workflow state for Head of IT approval',
                        'current_statuses' => [
                            'hod_status' => $userAccessRequest->hod_status,
                            'divisional_status' => $userAccessRequest->divisional_status,
                            'ict_director_status' => $userAccessRequest->ict_director_status,
                            'head_it_status' => $userAccessRequest->head_it_status
                        ]
                    ], 400);
                }
            } else {
                // ICT Director approval requirements
                $hodOk = in_array($userAccessRequest->hod_status, ['approved','skipped'], true);
                $divOk = in_array($userAccessRequest->divisional_status, ['approved','skipped'], true);
                if (!$hodOk || 
                    !$divOk ||
                    $userAccessRequest->ict_director_status !== 'pending') {
                        
                    Log::warning('ICT Director Approval Denied: Invalid approval workflow state', [
                        'request_id' => $id,
                        'hod_status' => $userAccessRequest->hod_status,
                        'divisional_status' => $userAccessRequest->divisional_status,
                        'ict_director_status' => $userAccessRequest->ict_director_status
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Request must have HOD and Divisional approval before ICT Director can process it.',
                        'error' => 'Invalid workflow state for ICT Director approval',
                        'hod_status' => $userAccessRequest->hod_status,
                        'divisional_status' => $userAccessRequest->divisional_status,
                        'ict_director_status' => $userAccessRequest->ict_director_status
                    ], 400);
                }
            }

            // Validate the approval data with role-appropriate field names
            if ($isHeadOfIT) {
                $validatedData = $request->validate([
                    'head_it_status' => 'required|in:approved,rejected',
                    'head_it_comments' => 'nullable|string|max:1000',
                    'head_it_name' => 'nullable|string|max:255',
                    // Accept legacy field names for backward compatibility
                    'dict_status' => 'nullable|in:approved,rejected',
                    'dict_comments' => 'nullable|string|max:1000',
                ]);
                // Map legacy field names if present
                if (!isset($validatedData['head_it_status']) && isset($validatedData['dict_status'])) {
                    $validatedData['head_it_status'] = $validatedData['dict_status'];
                }
                if (!isset($validatedData['head_it_comments']) && isset($validatedData['dict_comments'])) {
                    $validatedData['head_it_comments'] = $validatedData['dict_comments'];
                }
            } else {
                $validatedData = $request->validate([
                    'dict_status' => 'required|in:approved,rejected',
                    'dict_comments' => 'nullable|string|max:1000',
                    'dict_name' => 'nullable|string|max:255',
                    'dict_approved_at' => 'nullable|string',
                ]);
            }

            DB::beginTransaction();

            // Update the request based on user role
            $currentUser = auth()->user();
            
            if ($isHeadOfIT) {
                // Head of IT approval logic
                $approvalStatus = $validatedData['head_it_status'];
                $updateData = [
                    'head_it_status' => $approvalStatus,
                    'head_it_name' => $currentUser->name,
                    'head_it_approved_at' => now(),
                    'updated_at' => now()
                ];
                
                if ($approvalStatus === 'approved') {
                    $updateData['status'] = 'pending_ict_officer';
                    $updateData['head_it_comments'] = $validatedData['head_it_comments'] ?? '';
                    // Advance to ICT Officer for implementation
                    $updateData['ict_officer_status'] = 'pending';
                } else {
                    $updateData['status'] = 'head_it_rejected';
                    // Store rejection reasons for Head of IT
                    $updateData['head_it_comments'] = $validatedData['head_it_comments'] ?? '';
                }
                
            } else {
                // ICT Director approval logic
                $approvalStatus = $validatedData['dict_status'];
                $updateData = [
                    'status' => $approvalStatus === 'approved' ? 'pending_head_it' : 'ict_director_rejected',
                    'ict_director_status' => $approvalStatus,
                    'dict_name' => $currentUser->name,
                    'dict_approved_by' => $currentUser->id,
                    'dict_approved_by_name' => $currentUser->name,
                    'dict_approved_at' => now(),
                    'updated_at' => now()
                ];
                
                if ($approvalStatus === 'rejected') {
                    $updateData['ict_director_rejection_reasons'] = $validatedData['dict_comments'] ?? '';
                    $updateData['dict_comments'] = null;
                    $updateData['ict_director_comments'] = null;
                } else {
                    $updateData['dict_comments'] = $validatedData['dict_comments'] ?? '';
                    $updateData['ict_director_comments'] = $validatedData['dict_comments'] ?? '';
                    $updateData['ict_director_rejection_reasons'] = null;
                    
                    // CRITICAL: When ICT Director approves, advance workflow to Head of IT
                    $updateData['head_it_status'] = 'pending';
                }
            }

            $userAccessRequest->update($updateData);

            DB::commit();
            
            // Send SMS notifications based on status
            try {
                $sms = app(SmsModule::class);
                $roleName = $isHeadOfIT ? 'Head of IT' : 'ICT Director';
                $smsLevel = $isHeadOfIT ? 'head_it' : 'ict_director';
                
                if ($approvalStatus === 'approved') {
                    $nextApprover = null;
                    
                    if (!$isHeadOfIT) {
                        // ICT Director approved - next is Head of IT
                        $nextApprover = User::whereHas('roles', fn($q) => 
                            $q->where('name', 'head_of_it')
                        )->first();
                    }
                    
                    $sms->notifyRequestApproved(
                        $userAccessRequest,
                        auth()->user(),
                        $smsLevel,
                        $nextApprover
                    );
                    
                    Log::info($roleName . ' approval SMS notifications sent', [
                        'request_id' => $id,
                        'next_approver' => $nextApprover ? $nextApprover->name : 'None (ICT Officer)'
                    ]);
                } elseif ($approvalStatus === 'rejected') {
                    // Send rejection SMS to staff
                    $rejectionReason = $isHeadOfIT 
                        ? ($validatedData['head_it_comments'] ?? null)
                        : ($validatedData['dict_comments'] ?? null);
                    
                    $sms->notifyRequestRejected(
                        $userAccessRequest,
                        auth()->user(),
                        $smsLevel,
                        $rejectionReason
                    );
                    
                    Log::info($roleName . ' rejection SMS notification sent', [
                        'request_id' => $id
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning(($isHeadOfIT ? 'Head of IT' : 'ICT Director') . ' SMS notification failed', [
                    'request_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            $roleName = $isHeadOfIT ? 'Head of IT' : 'ICT Director';
            $statusKey = $isHeadOfIT ? 'head_it_status' : 'dict_status';
            $statusValue = $isHeadOfIT ? $validatedData['head_it_status'] : $validatedData['dict_status'];
            
            Log::info($roleName . ' Combined Access: Approval updated successfully', [
                'request_id' => $id,
                'role' => $isHeadOfIT ? 'head_of_it' : 'ict_director',
                'status' => $statusValue
            ]);

            return response()->json([
                'success' => true,
                'message' => $roleName . ' approval updated successfully',
                'data' => $this->transformRequestData($userAccessRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ICT Director Combined Access: Error updating approval', [
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
     * Cancel a combined access request (ICT Director action)
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            Log::info('ICT Director Combined Access: Cancelling request', [
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
                
                Log::info('ICT Director cancellation SMS notification sent', [
                    'request_id' => $id
                ]);
            } catch (\Exception $e) {
                Log::warning('ICT Director cancellation SMS notification failed', [
                    'request_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('ICT Director Combined Access: Request cancelled successfully', [
                'request_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully',
                'data' => $this->transformRequestData($userAccessRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ICT Director Combined Access: Error cancelling request', [
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
     * Get statistics for ICT Director dashboard
     */
    public function statistics(): JsonResponse
    {
        try {
            Log::info('ICT Director Combined Access: Fetching statistics', [
                'user_id' => auth()->id()
            ]);

            // ICT Director sees statistics using new granular status columns
            $baseQuery = UserAccess::query();

            // Lightweight normalization: infer ict_director_status for legacy rows (no file additions)
            try {
                // Limit normalization per call to avoid long-running requests
                $toNormalize = UserAccess::where('divisional_status', 'approved')
                    ->whereNull('ict_director_status')
                    ->limit(300)
                    ->get(['id','status','divisional_status','ict_director_status','ict_director_approved_at','ict_director_rejection_reasons']);

                foreach ($toNormalize as $row) {
                    $inferred = null;
                    $legacy = $row->status;

                    // Map legacy/overall status to granular ict_director_status where possible
                    if (in_array($legacy, ['dict_approved','ict_director_approved','approved'])) {
                        $inferred = 'approved';
                    } elseif (in_array($legacy, ['dict_rejected','ict_director_rejected','rejected'])) {
                        $inferred = 'rejected';
                    } else {
                        // Default to pending when divisional is approved but no explicit ICT decision
                        $inferred = 'pending';
                    }

                    if ($inferred) {
                        UserAccess::where('id', $row->id)->update(['ict_director_status' => $inferred]);
                    }
                }
            } catch (\Throwable $normErr) {
                Log::warning('ICT Director stats: normalization skipped', [
                    'error' => $normErr->getMessage()
                ]);
            }
            
            // Use trait method to get comprehensive statistics
            $systemStats = $this->getSystemStatistics();
            
            $stats = [
                // Overall workflow statistics
                'pending' => $this->getPendingRequestsForStage('ict_director')->count(),
                'hodApproved' => (clone $baseQuery)->where('hod_status', 'approved')->count(),
                'divisionalApproved' => (clone $baseQuery)->where('hod_status', 'approved')
                                                            ->where('divisional_status', 'approved')->count(),
                'pendingDict' => $this->getPendingRequestsForStage('ict_director')->count(),
                'dictApproved' => (clone $baseQuery)->where('ict_director_status', 'approved')->count(),
                'dictRejected' => (clone $baseQuery)->where('ict_director_status', 'rejected')->count(),
                
                // Final states
                'approved' => $this->getCompletedRequests()->count(),
                'implemented' => (clone $baseQuery)->where('ict_officer_status', 'implemented')->count(),
                'completed' => $this->getCompletedRequests()->count(),
                'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
                'rejected_total' => $this->getRequestsWithRejections()->count(),
                
                // Overall counts
                'total' => (clone $baseQuery)->count(),
                'in_progress' => $this->getRequestsInProgress()->count(),
                
                // Time-based statistics
                'thisMonth' => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'lastMonth' => (clone $baseQuery)->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year)
                    ->count(),
                    
                // Additional workflow insights
                'workflow_stages' => [
                    'pending_hod' => $this->getPendingRequestsForStage('hod')->count(),
                    'pending_divisional' => $this->getPendingRequestsForStage('divisional')->count(),
                    'pending_ict_director' => $this->getPendingRequestsForStage('ict_director')->count(),
                    'pending_head_it' => $this->getPendingRequestsForStage('head_it')->count(),
                    'pending_ict_officer' => $this->getPendingRequestsForStage('ict_officer')->count(),
                ],
                
                'departments' => Department::pluck('name')->toArray()
            ];

            Log::info('ICT Director Combined Access: Statistics retrieved successfully', $stats);

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('ICT Director Combined Access: Error fetching statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'data' => [
                    'pending' => 0,
                    'hodApproved' => 0,
                    'divisionalApproved' => 0,
                    'pendingDict' => 0,
                    'dictApproved' => 0,
                    'dictRejected' => 0,
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
        $user = auth()->user();
        $isHeadOfIT = $user->hasRole('head_of_it');
        
        // Determine which status to show based on user role
        if ($isHeadOfIT) {
            $relevantStatus = $request->head_it_status ?? 'pending';
            $mappedStatus = $this->mapStatusForHeadOfIT($relevantStatus);
            $displayName = $this->getStatusDisplayNameForHeadOfIT($relevantStatus);
        } else {
            $relevantStatus = $request->ict_director_status ?? $request->status;
            $mappedStatus = $this->mapStatusForICTDirector($relevantStatus);
            $displayName = $this->getStatusDisplayName($relevantStatus);
        }
        
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
            
            'status' => $mappedStatus,
            'dict_status' => $isHeadOfIT ? $request->head_it_status : $request->ict_director_status, // Role-appropriate status
            'status_display' => $displayName,
            'raw_status' => $request->status, // Keep legacy status for debugging
            
            // New granular status columns
            'hod_status' => $request->hod_status,
            'divisional_status' => $request->divisional_status,
            'ict_director_status' => $request->ict_director_status,
            'head_it_status' => $request->head_it_status,
            'ict_officer_status' => $request->ict_officer_status,
            'signature_path' => $request->signature_path,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
            'submission_date' => $request->created_at,
            
            // Cancellation metadata for UI logic
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_by' => $request->cancelled_by,
            'cancelled_at' => $request->cancelled_at,
            'cancelled_by_name' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->name ?? 'System') : null,
            'cancelled_by_role' => $request->cancelled_by ? (\App\Models\User::find($request->cancelled_by)?->roles->first()?->name ?? null) : null,
            
            // HOD approval info (for context)
            'hod_comments' => $request->hod_comments ?? '',
            'hod_name' => $request->hod_name ?? '',
            'hod_pf_number' => $request->resolveApproverPfNumber($request->hod_name),
            'hod_approved_at' => $request->hod_approved_at,
            'hod_approval_date' => $request->hod_approved_at, // Alias for frontend
            
            // Divisional approval info (for context)
            'divisional_comments' => $request->divisional_comments ?? '',
            'divisional_name' => $request->divisional_name ?? '',
            'divisional_pf_number' => $request->resolveApproverPfNumber($request->divisional_director_name ?? $request->divisional_name),
            'divisional_approved_at' => $request->divisional_approved_at,
            
            // ICT Director approval info
            'dict_comments' => $request->dict_comments ?? '',
            'ict_director_comments' => $request->ict_director_comments ?? '',
            'ict_director_rejection_reasons' => $request->ict_director_rejection_reasons ?? '',
            'ict_director_pf_number' => $request->resolveApproverPfNumber($request->ict_director_name ?? $request->dict_name),
            'dict_name' => $request->dict_name ?? '',
            'dict_approved_at' => $request->ict_director_approved_at,
            
            // Head of IT approval info
            'head_it_comments' => $request->head_it_comments ?? '',
            'head_it_name' => $request->head_it_name ?? '',
            'head_it_pf_number' => $request->resolveApproverPfNumber($request->head_it_name),
            'head_it_approved_at' => $request->head_it_approved_at,
            'ict_officer_pf_number' => $request->resolveApproverPfNumber($request->ict_officer_name),
            
            // Approval workflow status with visual indicators
            'hod_approval_status' => $this->getApprovalStatus($request, 'hod'),
            'divisional_approval_status' => $this->getApprovalStatus($request, 'divisional'),
            'dict_approval_status' => $this->getApprovalStatus($request, 'dict'),
            'head_it_approval_status' => $this->getApprovalStatus($request, 'head_it'),
            'ict_approval_status' => $this->getApprovalStatus($request, 'ict'),
            
            // Complete approval information with signature status indicators
            'approvals' => [
                'hod' => array_merge([
                    'name' => $request->hod_name,
                    'signature' => $request->hod_signature_path ?? null,
                    'signature_url' => $request->hod_signature_path ? storage_path('app/public/' . $request->hod_signature_path) : null,
                    'date' => $request->hod_approved_at,
                    'comments' => $request->hod_comments,
                    'approved_by' => $request->hod_approved_by_name ?? null,
                    'has_signature' => !empty($request->hod_signature_path),
                    'is_approved' => !empty($request->hod_approved_at)
                ], $this->formatSignatureStatus($request->hod_signature_path, $request->hod_approved_at, $request->hod_name, $request->hod_status)),
                
                'divisionalDirector' => array_merge([
                    'name' => $request->divisional_director_name,
                    'signature' => $request->divisional_director_signature_path ?? null,
                    'signature_url' => $request->divisional_director_signature_path ? storage_path('app/public/' . $request->divisional_director_signature_path) : null,
                    'date' => $request->divisional_approved_at,
                    'comments' => $request->divisional_director_comments ?? $request->divisional_comments,
                    'has_signature' => !empty($request->divisional_director_signature_path),
                    'is_approved' => !empty($request->divisional_approved_at)
                ], $this->formatSignatureStatus($request->divisional_director_signature_path, $request->divisional_approved_at, $request->divisional_director_name, $request->divisional_status)),
                
                'directorICT' => array_merge([
                    'name' => $request->ict_director_name ?? $request->dict_name,
                    'signature' => $request->ict_director_signature_path ?? null,
                    'signature_url' => $request->ict_director_signature_path ? storage_path('app/public/' . $request->ict_director_signature_path) : null,
                    'date' => $request->ict_director_approved_at ?? $request->dict_approved_at,
                    'comments' => $request->ict_director_comments ?? $request->dict_comments,
                    'has_signature' => !empty($request->ict_director_signature_path),
                    'is_approved' => !empty($request->ict_director_approved_at ?? $request->dict_approved_at)
                ], $this->formatSignatureStatus($request->ict_director_signature_path, $request->ict_director_approved_at ?? $request->dict_approved_at, $request->ict_director_name ?? $request->dict_name, $request->ict_director_status))
            ],
            
            // Implementation data with visual status indicators
            'implementation' => [
                'headIT' => array_merge([
                    'name' => $request->head_it_name,
                    'signature' => $request->head_it_signature_path ?? null,
                    'signature_url' => $request->head_it_signature_path ? storage_path('app/public/' . $request->head_it_signature_path) : null,
                    'date' => $request->head_it_approved_at,
                    'comments' => $request->head_it_comments,
                    'has_signature' => !empty($request->head_it_signature_path),
                    'is_approved' => !empty($request->head_it_approved_at)
                ], $this->formatSignatureStatus($request->head_it_signature_path, $request->head_it_approved_at, $request->head_it_name, $request->head_it_status)),
                
                'ictOfficer' => array_merge([
                    'name' => $request->ict_officer_name,
                    'signature' => $request->ict_officer_signature_path ?? null,
                    'signature_url' => $request->ict_officer_signature_path ? storage_path('app/public/' . $request->ict_officer_signature_path) : null,
                    'date' => $request->ict_officer_implemented_at,
                    'comments' => $request->ict_officer_comments,
                    'implementation_comments' => $request->implementation_comments,
                    'has_signature' => !empty($request->ict_officer_signature_path),
                    'is_implemented' => !empty($request->ict_officer_implemented_at)
                ], $this->formatSignatureStatus($request->ict_officer_signature_path, $request->ict_officer_implemented_at, $request->ict_officer_name, $request->ict_officer_status))
            ],
            
            // SMS notification status tracking
            'sms_to_hod_status' => $request->sms_to_hod_status ?? 'pending',
            'sms_to_divisional_status' => $request->sms_to_divisional_status ?? 'pending',
            'sms_to_ict_director_status' => $request->sms_to_ict_director_status ?? 'pending',
            'sms_to_head_it_status' => $request->sms_to_head_it_status ?? 'pending',
            'sms_to_requester_status' => $request->sms_to_requester_status ?? 'pending',
        ];
    }

    /**
     * Map database status to ICT Director perspective using new granular status columns
     */
    private function mapStatusForICTDirector($ictDirectorStatus): string
    {
        // Map the granular ICT Director status to frontend-friendly values
        switch ($ictDirectorStatus) {
            case 'pending':
                return 'pending'; // Pending ICT Director approval
            case 'approved':
                return 'approved'; // Approved by ICT Director
            case 'rejected':
                return 'rejected'; // Rejected by ICT Director
            default:
                return $ictDirectorStatus ?: 'pending'; // Default to pending if null
        }
    }
    
    /**
     * Map database status to Head of IT perspective using new granular status columns
     */
    private function mapStatusForHeadOfIT($headItStatus): string
    {
        // Map the granular Head of IT status to frontend-friendly values
        switch ($headItStatus) {
            case 'pending':
                return 'pending'; // Pending Head of IT approval
            case 'approved':
                return 'approved'; // Approved by Head of IT
            case 'rejected':
                return 'rejected'; // Rejected by Head of IT
            default:
                return $headItStatus ?: 'pending'; // Default to pending if null
        }
    }
    
    /**
     * Get user-friendly status display name using new granular status columns
     */
    private function getStatusDisplayName($ictDirectorStatus): string
    {
        switch ($ictDirectorStatus) {
            case 'pending':
                return 'Pending ICT Director Approval';
            case 'approved':
                return 'Approved by ICT Director';
            case 'rejected':
                return 'Rejected by ICT Director';
            case 'implemented':
                return 'Implemented';
            case 'cancelled':
                return 'Cancelled';
            default:
                return ucwords(str_replace('_', ' ', $ictDirectorStatus ?: 'pending'));
        }
    }
    
    /**
     * Get user-friendly status display name for Head of IT using new granular status columns
     */
    private function getStatusDisplayNameForHeadOfIT($headItStatus): string
    {
        switch ($headItStatus) {
            case 'pending':
                return 'Pending Head of IT Approval';
            case 'approved':
                return 'Approved by Head of IT';
            case 'rejected':
                return 'Rejected by Head of IT';
            case 'implemented':
                return 'Implemented';
            case 'cancelled':
                return 'Cancelled';
            default:
                return ucwords(str_replace('_', ' ', $headItStatus ?: 'pending'));
        }
    }

    /**
     * Get approval status for a specific role using new granular status columns
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
                return $request->ict_officer_status ?? 'pending';
                
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
            
            // Check if user has permission (ICT Director or Head of IT)
            if (!$user->hasRole('ict_director') && !$user->hasRole('head_of_it')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: Insufficient permissions'
                ], 403);
            }

            Log::info('DictCombinedAccess: Getting access request timeline', [
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
                    'hod_pf_number' => $request->resolveApproverPfNumber($request->hod_name),
                    'hod_comments' => $request->hod_comments,
                    'hod_signature_path' => $request->hod_signature_path,
                    'hod_approved_at' => $request->hod_approved_at,
                    'hod_approved_by' => $request->hod_approved_by,
                    'hod_approved_by_name' => $request->hod_approved_by_name,
                    
                    // Divisional Director approval stage
                    'divisional_status' => $request->divisional_status,
                    'divisional_director_name' => $request->divisional_director_name,
                    'divisional_pf_number' => $request->resolveApproverPfNumber($request->divisional_director_name),
                    'divisional_director_comments' => $request->divisional_director_comments,
                    'divisional_director_signature_path' => $request->divisional_director_signature_path,
                    'divisional_approved_at' => $request->divisional_approved_at,
                    
                    // ICT Director approval stage
                    'ict_director_status' => $request->ict_director_status,
                    'ict_director_name' => $request->ict_director_name,
                    'ict_director_pf_number' => $request->resolveApproverPfNumber($request->ict_director_name),
                    'ict_director_comments' => $request->ict_director_comments,
                    'ict_director_signature_path' => $request->ict_director_signature_path,
                    'ict_director_approved_at' => $request->ict_director_approved_at,
                    'ict_director_rejection_reasons' => $request->ict_director_rejection_reasons,
                    
                    // Head of IT approval stage
                    'head_it_status' => $request->head_it_status,
                    'head_it_name' => $request->head_it_name,
                    'head_it_pf_number' => $request->resolveApproverPfNumber($request->head_it_name),
                    'head_it_comments' => $request->head_it_comments,
                    'head_it_signature_path' => $request->head_it_signature_path,
                    'head_it_approved_at' => $request->head_it_approved_at,
                    
                    // ICT Officer implementation stage
                    'ict_officer_status' => $request->ict_officer_status,
                    'ict_officer_name' => $request->ict_officer_name,
                    'ict_officer_pf_number' => $request->resolveApproverPfNumber($request->ict_officer_name),
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
            Log::error('DictCombinedAccess: Error getting access request timeline', [
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
