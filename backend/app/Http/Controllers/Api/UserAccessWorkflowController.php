<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAccess;
use App\Services\UserAccessWorkflowService;
use App\Services\StatusMigrationService;
use App\Traits\HandlesStatusQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserAccessWorkflowController extends Controller
{
    use HandlesStatusQueries;
    
    protected UserAccessWorkflowService $workflowService;
    protected StatusMigrationService $statusMigrationService;

    public function __construct(UserAccessWorkflowService $workflowService, StatusMigrationService $statusMigrationService)
    {
        $this->workflowService = $workflowService;
        $this->statusMigrationService = $statusMigrationService;
    }

    /**
     * Display a listing of requests for approval
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'status', 'request_type', 'per_page']);
            $requests = $this->workflowService->getRequestsForApproval(auth()->user(), $filters);
            
            $transformedRequests = $requests->getCollection()->map(function ($request) {
                return $this->workflowService->transformRequestData($request);
            });
            
            $requests->setCollection($transformedRequests);
            
            return response()->json([
                'success' => true,
                'message' => 'Requests retrieved successfully',
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $userAccess = $this->workflowService->createRequest($request);
            
            return response()->json([
                'success' => true,
                'message' => 'Access request created successfully',
                'data' => $this->workflowService->transformRequestData($userAccess)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified request
     */
    public function show(UserAccess $userAccess): JsonResponse
    {
        try {
            $userAccess->load(['user', 'department']);
            
            return response()->json([
                'success' => true,
                'message' => 'Request retrieved successfully',
                'data' => $this->workflowService->transformRequestData($userAccess)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified request
     */
    public function update(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Only allow updates if request is still at initial pending stage using new status columns
            if ($userAccess->hod_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update request that has already entered the approval workflow',
                    'error' => 'Request is no longer editable',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 403);
            }

            $updatedRequest = $this->workflowService->updateRequest($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Request updated successfully',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process HOD approval/rejection
     */
    public function processHodApproval(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Verify user has HOD role
            if (!auth()->user()->hasRole() || auth()->user()->role->name !== 'HEAD_OF_DEPARTMENT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only HOD can perform this action'
                ], 403);
            }

            // Validate using new granular status columns - HOD can only approve if HOD status is pending
            if ($userAccess->hod_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not available for HOD approval',
                    'error' => 'Invalid workflow stage for HOD approval',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 400);
            }

            $updatedRequest = $this->workflowService->processHodApproval($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => $request->action === 'approve' ? 'Request approved by HOD' : 'Request rejected by HOD',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process HOD approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Divisional Director approval/rejection
     */
    public function processDivisionalApproval(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Verify user has Divisional Director role
            if (!auth()->user()->hasRole() || auth()->user()->role->name !== 'DIVISIONAL_DIRECTOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Divisional Director can perform this action'
                ], 403);
            }

            // Validate using new granular status columns - Divisional can only approve if HOD approved and Divisional is pending
            if ($userAccess->hod_status !== 'approved' || $userAccess->divisional_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not available for Divisional Director approval',
                    'error' => 'Request must have HOD approval and be pending divisional approval',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 400);
            }

            $updatedRequest = $this->workflowService->processDivisionalApproval($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => $request->action === 'approve' ? 'Request approved by Divisional Director' : 'Request rejected by Divisional Director',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Divisional approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process ICT Director approval/rejection
     */
    public function processIctDirectorApproval(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Verify user has ICT Director role
            if (!auth()->user()->hasRole() || auth()->user()->role->name !== 'ICT_DIRECTOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only ICT Director can perform this action'
                ], 403);
            }

            // Validate using new granular status columns - ICT Director can only approve if HOD approved (or skipped for ICT Officer flow)
            // and (Divisional approved OR skipped), and ICT Director is pending
            $divisionalOk = in_array($userAccess->divisional_status, ['approved', 'skipped'], true);
            $hodOk = in_array($userAccess->hod_status, ['approved', 'skipped'], true);
            if (!$hodOk || 
                !$divisionalOk || 
                $userAccess->ict_director_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not available for ICT Director approval',
                    'error' => 'Request must have HOD and Divisional approval and be pending ICT Director approval',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 400);
            }

            $updatedRequest = $this->workflowService->processIctDirectorApproval($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => $request->action === 'approve' ? 'Request approved by ICT Director' : 'Request rejected by ICT Director',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process ICT Director approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Head IT approval/rejection
     */
    public function processHeadItApproval(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Verify user has Head IT role
            if (!auth()->user()->hasRole() || auth()->user()->role->name !== 'HEAD_IT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Head IT can perform this action'
                ], 403);
            }

            // Validate using new granular status columns - Head IT can only approve if previous stages approved
            // (HOD may be skipped for ICT Officer flow, Divisional may be skipped) and Head IT is pending
            $divisionalOk = in_array($userAccess->divisional_status, ['approved', 'skipped'], true);
            $hodOk = in_array($userAccess->hod_status, ['approved', 'skipped'], true);
            if (!$hodOk || 
                !$divisionalOk || 
                $userAccess->ict_director_status !== 'approved' || 
                $userAccess->head_it_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not available for Head IT approval',
                    'error' => 'Request must have HOD, Divisional, and ICT Director approval and be pending Head IT approval',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 400);
            }

            $updatedRequest = $this->workflowService->processHeadItApproval($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => $request->action === 'approve' ? 'Request approved by Head IT' : 'Request rejected by Head IT',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Head IT approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process ICT Officer implementation
     */
    public function processIctOfficerImplementation(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            // Verify user has ICT Officer role
            if (!auth()->user()->hasRole() || auth()->user()->role->name !== 'ICT_OFFICER') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only ICT Officer can perform this action'
                ], 403);
            }

            // Validate using new granular status columns - ICT Officer can only implement if previous stages approved
            // (HOD may be skipped for ICT Officer flow, Divisional may be skipped) and ICT Officer is pending
            $divisionalOk = in_array($userAccess->divisional_status, ['approved', 'skipped'], true);
            $hodOk = in_array($userAccess->hod_status, ['approved', 'skipped'], true);
            if (!$hodOk || 
                !$divisionalOk || 
                $userAccess->ict_director_status !== 'approved' || 
                $userAccess->head_it_status !== 'approved' || 
                $userAccess->ict_officer_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not available for ICT Officer implementation',
                    'error' => 'Request must have all previous approvals and be pending ICT Officer implementation',
                    'current_workflow_stage' => [
                        'hod_status' => $userAccess->hod_status,
                        'divisional_status' => $userAccess->divisional_status,
                        'ict_director_status' => $userAccess->ict_director_status,
                        'head_it_status' => $userAccess->head_it_status,
                        'ict_officer_status' => $userAccess->ict_officer_status
                    ]
                ], 400);
            }

            $updatedRequest = $this->workflowService->processIctOfficerImplementation($userAccess, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Request implemented by ICT Officer',
                'data' => $this->workflowService->transformRequestData($updatedRequest)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process ICT Officer implementation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            // Get enhanced statistics using both legacy service and new granular status methods
            $legacyStatistics = $this->workflowService->getStatistics(auth()->user());
            
            // Get enhanced statistics using new granular status system
            $enhancedStatistics = $this->getEnhancedStatistics(auth()->user());
            
            // Merge both statistics for complete view
            $combinedStatistics = array_merge($legacyStatistics, $enhancedStatistics);
            
            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $combinedStatistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get enhanced statistics using new granular status system
     */
    private function getEnhancedStatistics($user): array
    {
        // Use trait methods for comprehensive statistics
        $systemStats = $this->getSystemStatistics();
        
        // Role-specific statistics
        $roleBasedStats = [];
        if ($user->hasRole()) {
            $userRole = $user->role->name;
            switch ($userRole) {
                case 'HEAD_OF_DEPARTMENT':
                    $roleBasedStats = $this->getStageStatistics('hod');
                    break;
                case 'DIVISIONAL_DIRECTOR':
                    $roleBasedStats = $this->getStageStatistics('divisional');
                    break;
                case 'ICT_DIRECTOR':
                    $roleBasedStats = $this->getStageStatistics('ict_director');
                    break;
                case 'HEAD_IT':
                    $roleBasedStats = $this->getStageStatistics('head_it');
                    break;
                case 'ICT_OFFICER':
                    $roleBasedStats = $this->getStageStatistics('ict_officer');
                    break;
            }
        }
        
        return [
            'enhanced_workflow_statistics' => [
                'system_overview' => $systemStats,
                'role_specific' => $roleBasedStats,
                'workflow_breakdown' => [
                    'pending_hod' => $this->getPendingRequestsForStage('hod')->count(),
                    'pending_divisional' => $this->getPendingRequestsForStage('divisional')->count(),
                    'pending_ict_director' => $this->getPendingRequestsForStage('ict_director')->count(),
                    'pending_head_it' => $this->getPendingRequestsForStage('head_it')->count(),
                    'pending_ict_officer' => $this->getPendingRequestsForStage('ict_officer')->count(),
                ],
                'completion_statistics' => [
                    'fully_completed' => $this->getCompletedRequests()->count(),
                    'in_progress' => $this->getRequestsInProgress()->count(),
                    'rejected_somewhere' => $this->getRequestsWithRejections()->count(),
                ]
            ]
        ];
    }

    /**
     * Get available request types and modules
     */
    public function getFormOptions(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Form options retrieved successfully',
                'data' => [
                    'request_types' => UserAccess::REQUEST_TYPES,
                    'access_types' => UserAccess::ACCESS_TYPES,
                    'statuses' => UserAccess::STATUSES,
                    'granular_status_options' => [
                        'hod_statuses' => ['pending', 'approved', 'rejected'],
                        'divisional_statuses' => ['pending', 'approved', 'rejected'],
                        'ict_director_statuses' => ['pending', 'approved', 'rejected'],
                        'head_it_statuses' => ['pending', 'approved', 'rejected'],
                        'ict_officer_statuses' => ['pending', 'implemented', 'rejected']
                    ],
                    'workflow_stages' => [
                        'hod' => 'Head of Department',
                        'divisional' => 'Divisional Director',
                        'ict_director' => 'ICT Director',
                        'head_it' => 'Head IT',
                        'ict_officer' => 'ICT Officer'
                    ],
                    'wellsoft_modules' => [
                        'Registrar',
                        'Specialist',
                        'Cashier',
                        'Resident Nurse',
                        'Intern Doctor',
                        'Intern Nurse',
                        'Medical Recorder',
                        'Social Worker',
                        'Quality Officer',
                        'Administrator',
                        'Health Attendant'
                    ],
                    'jeeva_modules' => [
                        'FINANCIAL ACCOUNTING',
                        'DOCTOR CONSULTATION',
                        'MEDICAL RECORDS',
                        'OUTPATIENT',
                        'NURSING STATION',
                        'INPATIENT',
                        'IP CASHIER',
                        'HIV',
                        'LINEN & LAUNDRY',
                        'FIXED ASSETS',
                        'PMTCT',
                        'PHARMACY',
                        'BILL NOTE',
                        'BLOOD BANK',
                        'ORDER MANAGEMENT',
                        'PRIVATE CREDIT',
                        'LABORATORY',
                        'GENERAL STORE',
                        'IP BILLING',
                        'RADIOLOGY',
                        'PURCHASE',
                        'SCROLLING',
                        'OPERATION THEATRE',
                        'CSSD',
                        'WEB INDENT',
                        'MORTUARY',
                        'GENERAL MAINTENANCE',
                        'PERSONNEL',
                        'MAINTENANCE',
                        'PAYROLL',
                        'CMS',
                        'MIS STATISTICS'
                    ],
                    'internet_purposes' => [
                        'Purpose 1',
                        'Purpose 2',
                        'Purpose 3',
                        'Purpose 4'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a request
     */
    public function cancel(Request $request, UserAccess $userAccess): JsonResponse
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string|max:1000'
            ]);

            // Only allow cancellation by request owner or admin
            if (auth()->id() !== $userAccess->user_id && !auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to cancel this request'
                ], 403);
            }

            $userAccess->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now()
            ]);

            // Send SMS notification to requester about cancellation
            try {
                $sms = app(\App\Services\SmsModule::class);
                $sms->notifyRequestCancelled(
                    $userAccess,
                    auth()->user(),
                    $request->cancellation_reason
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Workflow cancellation SMS notification failed', [
                    'request_id' => $userAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully',
                'data' => $this->workflowService->transformRequestData($userAccess->fresh())
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export requests to Excel/CSV
     */
    public function export(Request $request): JsonResponse
    {
        try {
            // This would typically generate and return a file
            // For now, return success message
            return response()->json([
                'success' => true,
                'message' => 'Export functionality to be implemented',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
