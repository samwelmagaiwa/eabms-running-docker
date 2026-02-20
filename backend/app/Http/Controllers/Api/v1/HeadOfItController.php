<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCombinedAccessRequest;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TaskAssignedNotification;
use Carbon\Carbon;

class HeadOfItController extends Controller
{
    /**
     * Get all requests that have reached Head of IT stage (pending, approved, rejected)
     */
    public function getAllRequests(Request $request)
    {
        try {
            Log::debug('HeadOfItController: Getting all requests for Head of IT');
            
            // Get requests that are approved by ICT Director (all that have reached Head of IT stage)
            // Include pending, approved, and rejected requests
            $query = UserCombinedAccessRequest::with(['user', 'department'])
                ->where('ict_director_status', 'approved')
                ->orderBy('ict_director_approved_at', 'asc'); // FIFO ordering

            $requests = $query->get()->map(function ($request) {
                // Determine correct status for Head of IT view
                $headItStatus = $request->head_it_status ?? 'pending';
                $displayStatus = 'ict_director_approved'; // Default for pending Head of IT approval
                
                if ($headItStatus === 'approved') {
                    $displayStatus = 'head_of_it_approved';
                } elseif ($headItStatus === 'rejected') {
                    $displayStatus = 'head_of_it_rejected';
                } elseif ($request->status === 'assigned_to_ict' || $request->status === 'implementation_in_progress' || $request->status === 'completed') {
                    $displayStatus = $request->status;
                }
                
                return [
                    'id' => $request->id,
                    'request_id' => $request->request_id,
                    'staff_name' => $request->staff_name,
                    'pf_number' => $request->pf_number,
                    'phone_number' => $request->phone_number,
                    'department' => $request->department_name ?? $request->department->name ?? 'Unknown',
                    'request_types' => is_string($request->request_types) 
                        ? explode(',', $request->request_types) 
                        : $request->request_types,
                    'internet_purposes' => $request->internet_purposes,
                    'status' => $displayStatus,
                    'head_it_status' => $headItStatus,
                    'created_at' => $request->created_at,
                    'hod_approved_at' => $request->hod_approved_at,
                    'divisional_approved_at' => $request->divisional_approved_at,
                    'ict_director_approved_at' => $request->ict_director_approved_at,
                    'head_of_it_approved_at' => $request->head_of_it_approved_at,
                    'updated_at' => $request->updated_at,
                    // SMS to requester on Head of IT approval
                    'sms_to_requester_status' => $request->sms_to_requester_status ?? 'pending',
                    'sms_sent_to_requester_at' => $request->sms_sent_to_requester_at,
                    // SMS status tracking for ICT Officer assignment
                    'sms_to_ict_officer_status' => $request->sms_to_ict_officer_status ?? 'pending',
                    'sms_sent_to_ict_officer_at' => $request->sms_sent_to_ict_officer_at,
                    // Task assignment tracking
                    'assigned_ict_officer_id' => $request->assigned_ict_officer_id,
                    'task_assigned_at' => $request->task_assigned_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Requests retrieved successfully',
                'data' => [
                    'requests' => $requests,
                    'total' => $requests->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting pending requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get requests pending Head of IT approval (backward compatibility)
     * This method now redirects to getAllRequests for compatibility
     */
    public function getPendingRequests(Request $request)
    {
        return $this->getAllRequests($request);
    }

    /**
     * Get a specific request by ID
     */
    public function getRequestById($id)
    {
        try {
            Log::debug('HeadOfItController: Getting request by ID', ['request_id' => $id]);
            
            $request = UserCombinedAccessRequest::with(['user', 'department'])
                ->findOrFail($id);

            $requestData = [
                'id' => $request->id,
                'request_id' => $request->request_id,
                'staff_name' => $request->staff_name,
                'pf_number' => $request->pf_number,
                'phone_number' => $request->phone_number,
                'department' => $request->department_name ?? $request->department->name ?? 'Unknown',
                'department_name' => $request->department_name ?? $request->department->name ?? 'Unknown',
                'position' => $request->position ?? 'Staff',
                'email' => $request->user->email ?? null,
                'request_types' => is_string($request->request_types) 
                    ? explode(',', $request->request_types) 
                    : $request->request_types,
                'internet_purposes' => $request->internet_purposes,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'hod_approved_at' => $request->hod_approved_at,
                'divisional_approved_at' => $request->divisional_approved_at,
                'ict_director_approved_at' => $request->ict_director_approved_at,
                'head_of_it_approved_at' => $request->head_of_it_approved_at,
                'updated_at' => $request->updated_at,
                // Assignment info for UI logic
                'assigned_ict_officer_id' => $request->assigned_ict_officer_id,
                'ict_officer_status' => $request->ict_officer_status,
                'task_assigned_at' => $request->task_assigned_at
            ];

            // If combined record doesn't have assignment info, infer from ICT task assignments (NEW system)
            if (empty($requestData['assigned_ict_officer_id'])) {
                $activeAssignment = \App\Models\IctTaskAssignment::where('user_access_id', $id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->latest('assigned_at')
                    ->first();
                if ($activeAssignment) {
                    $requestData['assigned_ict_officer_id'] = $activeAssignment->ict_officer_user_id;
                    $requestData['task_assigned_at'] = $activeAssignment->assigned_at;
                    // Prefer explicit ICT officer status from UserAccess if available, else set pending
                    $requestData['ict_officer_status'] = $request->ict_officer_status ?? 'pending';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Request retrieved successfully',
                'data' => $requestData
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting request by ID', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Approve a request
     */
    public function approveRequest(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:5120' // Max 5MB
        ]);

        DB::beginTransaction();
        
        try {
            Log::debug('HeadOfItController: Approving request', ['request_id' => $id]);
            
            $accessRequest = UserCombinedAccessRequest::findOrFail($id);
            
            // Check if request is in correct status for Head of IT approval
            // Use granular status columns for validation
            if ($accessRequest->ict_director_status !== 'approved' || 
                ($accessRequest->head_it_status !== null && $accessRequest->head_it_status !== 'pending')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not in the correct status for Head of IT approval',
                    'debug' => [
                        'ict_director_status' => $accessRequest->ict_director_status,
                        'head_it_status' => $accessRequest->head_it_status,
                        'overall_status' => $accessRequest->status
                    ]
                ], 400);
            }

            // Handle signature upload
            $signaturePath = null;
            if ($request->hasFile('signature')) {
                $signatureFile = $request->file('signature');
                $fileName = 'head_of_it_signature_' . $id . '_' . time() . '.' . $signatureFile->extension();
                $signaturePath = $signatureFile->storeAs('signatures/head_of_it', $fileName, 'public');
            }

            // Update request status and approval information
            $accessRequest->update([
                'status' => 'head_of_it_approved',
                'head_it_status' => 'approved',
                'head_it_approved_at' => now(),
                'head_it_approved_by' => Auth::id(),
                'head_it_signature_path' => $signaturePath,
                'head_it_comments' => 'Approved by Head of IT'
            ]);

            DB::commit();

            Log::info('HeadOfItController: Request approved successfully', ['request_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Request approved successfully',
                'data' => [
                    'request_id' => $accessRequest->id,
                    'status' => $accessRequest->status,
                    'approved_at' => $accessRequest->head_of_it_approved_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error approving request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reject a request
     */
    public function rejectRequest(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:5120', // Max 5MB
            'reason' => 'required|string|min:10|max:1000'
        ]);

        DB::beginTransaction();
        
        try {
            Log::info('HeadOfItController: Rejecting request', ['request_id' => $id]);
            
            $accessRequest = UserCombinedAccessRequest::findOrFail($id);
            
            // Check if request is in correct status for Head of IT rejection
            // Use granular status columns for validation
            if ($accessRequest->ict_director_status !== 'approved' || 
                ($accessRequest->head_it_status !== null && $accessRequest->head_it_status !== 'pending')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is not in the correct status for Head of IT rejection',
                    'debug' => [
                        'ict_director_status' => $accessRequest->ict_director_status,
                        'head_it_status' => $accessRequest->head_it_status,
                        'overall_status' => $accessRequest->status
                    ]
                ], 400);
            }

            // Handle signature upload
            $signaturePath = null;
            if ($request->hasFile('signature')) {
                $signatureFile = $request->file('signature');
                $fileName = 'head_of_it_rejection_signature_' . $id . '_' . time() . '.' . $signatureFile->extension();
                $signaturePath = $signatureFile->storeAs('signatures/head_of_it', $fileName, 'public');
            }

            // Update request status and rejection information
            $accessRequest->update([
                'status' => 'head_of_it_rejected',
                'head_it_status' => 'rejected',
                'head_it_rejected_at' => now(),
                'head_it_rejected_by' => Auth::id(),
                'head_it_signature_path' => $signaturePath,
                'head_it_rejection_reason' => $request->reason,
                'head_it_comments' => 'Rejected by Head of IT: ' . $request->reason
            ]);

            DB::commit();

            Log::info('HeadOfItController: Request rejected successfully', ['request_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Request rejected successfully',
                'data' => [
                    'request_id' => $accessRequest->id,
                    'status' => $accessRequest->status,
                    'rejected_at' => $accessRequest->head_of_it_rejected_at,
                    'reason' => $request->reason
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error rejecting request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get list of available ICT Officers with SMS status for specific request
     */
    public function getIctOfficers(Request $request)
    {
        try {
            $requestId = $request->query('request_id');
            Log::info('HeadOfItController: Getting ICT officers list', ['request_id' => $requestId]);
            
            // Get users with ICT Officer role
            $ictOfficers = User::whereHas('roles', function($query) {
                    $query->where('name', 'ict_officer');
                })
                ->with(['department'])
                ->where('is_active', true)
                ->get()
                ->map(function ($officer) use ($requestId) {
                    // For now, set all ICT officers as available
                    // TODO: Implement proper task assignment tracking
                    $activeAssignments = 0; // Placeholder
                    $status = 'Available'; // Default status
                    $smsStatus = null; // Default no SMS status

                    // If request_id provided, check if this officer is assigned to that request
                    if ($requestId) {
                        $assignment = \App\Models\IctTaskAssignment::where('user_access_id', $requestId)
                            ->where('ict_officer_user_id', $officer->id)
                            ->whereIn('status', ['assigned', 'in_progress'])
                            ->latest('assigned_at')
                            ->first();
                        
                        if ($assignment) {
                            // This officer is assigned to this request
                            $userAccess = \App\Models\UserAccess::find($requestId);
                            $smsStatus = $userAccess->sms_to_ict_officer_status ?? 'pending';
                            $status = 'Assigned';
                        }
                    }

                    return [
                        'id' => $officer->id,
                        'name' => $officer->name,
                        'pf_number' => $officer->pf_number,
                        'phone_number' => $officer->phone,
                        'email' => $officer->email,
                        'department' => $officer->department->name ?? 'ICT Department',
                        'position' => 'ICT Officer',
                        'status' => $status,
                        'sms_status' => $smsStatus,
                        'active_assignments' => $activeAssignments,
                        'is_active' => $officer->is_active,
                        'created_at' => $officer->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'ICT officers retrieved successfully',
                'data' => $ictOfficers
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting ICT officers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ICT officers',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign a task to an ICT Officer (Updated to use UserAccess)
     */
    public function assignTaskToIctOfficer(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:user_access,id',
            'ict_officer_id' => 'required|exists:users,id',
            'force_reassign' => 'sometimes|boolean'
        ]);

        DB::beginTransaction();
        
        try {
            Log::info('HeadOfItController: Assigning task to ICT officer', [
                'request_id' => $request->request_id,
                'ict_officer_id' => $request->ict_officer_id
            ]);
            
            $accessRequest = \App\Models\UserAccess::findOrFail($request->request_id);
            $ictOfficer = User::findOrFail($request->ict_officer_id);
            
            // Verify ICT Officer role
            if (!$ictOfficer->isIctOfficer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not an ICT Officer'
                ], 400);
            }

            // Check if request is approved by Head of IT
            if ($accessRequest->head_it_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be approved by Head of IT before assignment',
                    'debug' => [
                        'head_it_status' => $accessRequest->head_it_status,
                        'calculated_status' => $accessRequest->getCalculatedStatus()
                    ]
                ], 400);
            }

            // Disallow assignment if already implemented
            if ($accessRequest->ict_officer_status === 'implemented') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task already implemented. Cannot reassign'
                ], 400);
            }

            // Check if task is already assigned and handle reassignment rules
            $existingAssignment = \App\Models\IctTaskAssignment::where('user_access_id', $request->request_id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->latest('assigned_at')
                ->first();

            if ($existingAssignment) {
                // If existing assignment exists and no force_reassign requested, block with clear message
                if (!$request->boolean('force_reassign')) {
                    $currentName = optional($existingAssignment->ictOfficer)->name ?? 'another ICT Officer';
                    return response()->json([
                        'success' => false,
                        'message' => 'Request is already assigned to ' . $currentName . '. You cannot assign twice. Use Reassign to move it.'
                    ], 400);
                }

                // If reassignment explicitly requested, cancel the previous assignment
                $existingAssignment->update([
                    'status' => \App\Models\IctTaskAssignment::STATUS_CANCELLED,
                    'cancelled_at' => now()
                ]);
            }

            // Create ICT task assignment
            $ictTaskAssignment = \App\Models\IctTaskAssignment::create([
                'user_access_id' => $request->request_id,
                'ict_officer_user_id' => $request->ict_officer_id,
                'assigned_by_user_id' => Auth::id(),
                'status' => \App\Models\IctTaskAssignment::STATUS_ASSIGNED,
                'assignment_notes' => 'ICT task assigned by Head of IT for ' . $accessRequest->staff_name,
                'assigned_at' => now()
            ]);

            // Update user access ICT Officer status to pending and store assigned officer ID
            $accessRequest->update([
                'ict_officer_status' => 'pending',
                'assigned_ict_officer_id' => $request->ict_officer_id,
                'task_assigned_at' => now()
            ]);

            // Send SMS notification to ICT Officer using SmsModule (same pattern as other approvals)
            try {
                $smsModule = app(\App\Services\SmsModule::class);
                
                // Notify ICT Officer about new task assignment
                $results = $smsModule->notifyIctOfficerAssignment($accessRequest, $ictOfficer, Auth::user());
                
                Log::info('ICT Officer task assignment SMS notifications sent', [
                    'request_id' => $request->request_id,
                    'ict_officer_id' => $request->ict_officer_id,
                    'results' => $results
                ]);
            } catch (\Exception $smsError) {
                Log::warning('Failed to send ICT Officer assignment SMS notification', [
                    'request_id' => $request->request_id,
                    'ict_officer_id' => $request->ict_officer_id,
                    'error' => $smsError->getMessage()
                ]);
            }
            
            // Also send notification to ICT Officer (for email/database)
            try {
                $ictOfficer->notify(new \App\Notifications\IctTaskAssignedNotification($accessRequest, $ictTaskAssignment));
            } catch (\Exception $notificationError) {
                Log::warning('Failed to send ICT task notification', [
                    'error' => $notificationError->getMessage()
                ]);
            }

            DB::commit();

            Log::info('HeadOfItController: ICT task assigned successfully', [
                'request_id' => $request->request_id,
                'ict_officer_id' => $request->ict_officer_id,
                'assignment_id' => $ictTaskAssignment->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully to ' . $ictOfficer->name,
                'data' => [
                    'assignment_id' => $ictTaskAssignment->id,
                    'request_id' => $accessRequest->id,
                    'ict_officer' => [
                        'id' => $ictOfficer->id,
                        'name' => $ictOfficer->name,
                        'email' => $ictOfficer->email
                    ],
                    'status' => $accessRequest->getCalculatedStatus(),
                    'assigned_at' => $ictTaskAssignment->assigned_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error assigning task', [
                'request_id' => $request->request_id,
                'ict_officer_id' => $request->ict_officer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get task assignment history for a request
     */
    public function getTaskHistory($requestId)
    {
        try {
            Log::info('HeadOfItController: Getting task history', ['request_id' => $requestId]);
            
            $assignments = TaskAssignment::with(['assignedTo', 'assignedBy'])
                ->where('request_id', $requestId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'status' => $assignment->status,
                        'assigned_to' => [
                            'id' => $assignment->assignedTo->id,
                            'name' => $assignment->assignedTo->name,
                            'email' => $assignment->assignedTo->email
                        ],
                        'assigned_by' => [
                            'id' => $assignment->assignedBy->id,
                            'name' => $assignment->assignedBy->name,
                            'email' => $assignment->assignedBy->email
                        ],
                        'assigned_at' => $assignment->assigned_at,
                        'completed_at' => $assignment->completed_at,
                        'description' => $assignment->description,
                        'priority' => $assignment->priority,
                        'progress_notes' => $assignment->progress_notes,
                        'estimated_completion' => $assignment->estimated_completion
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Task history retrieved successfully',
                'data' => $assignments
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting task history', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task history',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cancel task assignment
     */
    public function cancelTaskAssignment($requestId)
    {
        DB::beginTransaction();
        
        try {
            Log::info('HeadOfItController: Canceling task assignment', ['request_id' => $requestId]);
            
            $accessRequest = UserCombinedAccessRequest::findOrFail($requestId);
            
            // Find active assignment
            $assignment = TaskAssignment::where('request_id', $requestId)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active assignment found for this request'
                ], 404);
            }

            // Update assignment status
            $assignment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => 'Cancelled by Head of IT'
            ]);

            // Update request status back to head_of_it_approved
            $accessRequest->update([
                'status' => 'head_of_it_approved',
                'assigned_ict_officer_id' => null,
                'task_assigned_at' => null
            ]);

            DB::commit();

            Log::info('HeadOfItController: Task assignment cancelled successfully', ['request_id' => $requestId]);

            return response()->json([
                'success' => true,
                'message' => 'Task assignment cancelled successfully',
                'data' => [
                    'request_id' => $accessRequest->id,
                    'status' => $accessRequest->status,
                    'cancelled_at' => $assignment->cancelled_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error canceling task assignment', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel task assignment',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // ========================================
    // NEW ICT TASK ASSIGNMENT METHODS
    // ========================================

    /**
     * Get requests pending Head of IT approval (NEW UserAccess system)
     */
    public function getPendingIctRequests(Request $request)
    {
        try {
            Log::info('HeadOfItController: Getting pending ICT requests for Head of IT');
            
            // Get requests that are approved by ICT Director and waiting for Head of IT approval
            $query = \App\Models\UserAccess::with(['user', 'department'])
                ->where('ict_director_status', 'approved')
                ->where(function($q) {
                    $q->whereNull('head_it_status')
                      ->orWhere('head_it_status', 'pending');
                })
                ->orderBy('ict_director_approved_at', 'asc'); // FIFO ordering

            $requests = $query->get()->map(function ($accessRequest) {
                return [
                    'id' => $accessRequest->id,
                    'staff_name' => $accessRequest->staff_name,
                    'pf_number' => $accessRequest->pf_number,
                    'phone_number' => $accessRequest->phone_number,
                    'department' => $accessRequest->department->name ?? 'Unknown',
                    'request_types' => is_string($accessRequest->request_type) 
                        ? explode(',', $accessRequest->request_type) 
                        : $accessRequest->request_type,
                    'internet_purposes' => $accessRequest->internet_purposes,
                    'status' => $accessRequest->getCalculatedOverallStatus(),
                    'created_at' => $accessRequest->created_at,
                    'hod_approved_at' => $accessRequest->hod_approved_at,
                    'divisional_approved_at' => $accessRequest->divisional_approved_at,
                    'ict_director_approved_at' => $accessRequest->ict_director_approved_at,
                    'head_it_approved_at' => $accessRequest->head_it_approved_at,
                    'updated_at' => $accessRequest->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'ICT requests retrieved successfully',
                'data' => [
                    'requests' => $requests,
                    'total' => $requests->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting pending ICT requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ICT requests',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available ICT Officers for task assignment (NEW system)
     */
    public function getAvailableIctOfficers()
    {
        try {
            Log::info('HeadOfItController: Getting available ICT officers list');
            
            // Get users with ICT Officer role
            $ictOfficers = User::whereHas('roles', function($q) {
                    $q->where('name', 'ict_officer');
                })
                ->with(['department'])
                ->where('is_active', true)
                ->get()
                ->map(function ($officer) {
                    // Get current ICT task workload
                    $activeAssignments = $officer->getCurrentIctWorkload();
                    $availabilityStatus = $officer->getIctTaskAvailabilityStatus();
                    
                    return [
                        'id' => $officer->id,
                        'name' => $officer->name,
                        'pf_number' => $officer->pf_number,
                        'phone_number' => $officer->phone,
                        'email' => $officer->email,
                        'department' => $officer->department->name ?? 'ICT Department',
                        'position' => 'ICT Officer',
                        'availability_status' => $availabilityStatus,
                        'active_assignments' => $activeAssignments,
                        'is_active' => $officer->is_active,
                        'is_available_for_tasks' => $officer->isAvailableForIctTasks(),
                        'created_at' => $officer->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'ICT officers retrieved successfully',
                'data' => $ictOfficers
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting available ICT officers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ICT officers',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign ICT task to officer (NEW system)
     */
    public function assignIctTask(Request $request)
    {
        $request->validate([
            'user_access_id' => 'required|exists:user_access,id',
            'ict_officer_user_id' => 'required|exists:users,id',
            'assignment_notes' => 'nullable|string|max:1000',
            'force_reassign' => 'sometimes|boolean'
        ]);

        DB::beginTransaction();
        
        try {
            Log::info('HeadOfItController: Assigning ICT task to officer', [
                'user_access_id' => $request->user_access_id,
                'ict_officer_user_id' => $request->ict_officer_user_id
            ]);
            
            $userAccess = \App\Models\UserAccess::findOrFail($request->user_access_id);
            $ictOfficer = User::findOrFail($request->ict_officer_user_id);
            
            // Verify ICT Officer role
            if (!$ictOfficer->isIctOfficer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not an ICT Officer'
                ], 400);
            }

            // Check if request is approved by Head of IT
            if ($userAccess->head_it_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must be approved by Head of IT before assignment',
                    'debug' => [
                        'head_it_status' => $userAccess->head_it_status,
                        'calculated_status' => $userAccess->getCalculatedOverallStatus()
                    ]
                ], 400);
            }

            // Check if task is already assigned and handle reassignment rules
            $existingAssignment = \App\Models\IctTaskAssignment::where('user_access_id', $request->user_access_id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->latest('assigned_at')
                ->first();

            if ($existingAssignment) {
                // If existing assignment exists and force_reassign not set, block with helpful message
                if (!$request->boolean('force_reassign')) {
                    $currentName = optional($existingAssignment->ictOfficer)->name ?? 'another ICT Officer';
                    return response()->json([
                        'success' => false,
                        'message' => 'Request is already assigned to ' . $currentName . '. You cannot assign twice. Use Reassign to move it.'
                    ], 400);
                }

                // Proceed with reassignment: cancel previous assignment
                $existingAssignment->update([
                    'status' => \App\Models\IctTaskAssignment::STATUS_CANCELLED,
                    'cancelled_at' => now()
                ]);
            }

            // Create ICT task assignment
            $ictTaskAssignment = \App\Models\IctTaskAssignment::create([
                'user_access_id' => $request->user_access_id,
                'ict_officer_user_id' => $request->ict_officer_user_id,
                'assigned_by_user_id' => Auth::id(),
                'status' => \App\Models\IctTaskAssignment::STATUS_ASSIGNED,
                'assignment_notes' => $request->assignment_notes ?? 'ICT task assigned by Head of IT',
                'assigned_at' => now()
            ]);

            // Update user access with assignee and assignment timestamp, and set ICT Officer status to pending
            $userAccess->update([
                'assigned_ict_officer_id' => $request->ict_officer_user_id,
                'task_assigned_at' => now(),
                'ict_officer_status' => 'pending',
                'ict_officer_user_id' => $request->ict_officer_user_id,
                'ict_officer_assigned_at' => now()
            ]);

            // Send SMS notification to ICT Officer using SmsModule (same pattern as other approvals)
            try {
                $smsModule = app(\App\Services\SmsModule::class);
                
                // Notify ICT Officer about new task assignment
                $results = $smsModule->notifyIctOfficerAssignment($userAccess, $ictOfficer, Auth::user());
                
                Log::info('ICT Officer task assignment SMS notifications sent', [
                    'user_access_id' => $userAccess->id,
                    'ict_officer_id' => $ictOfficer->id,
                    'results' => $results
                ]);
            } catch (\Exception $smsError) {
                Log::warning('Failed to send ICT Officer assignment SMS notification', [
                    'user_access_id' => $userAccess->id,
                    'ict_officer_id' => $ictOfficer->id,
                    'error' => $smsError->getMessage()
                ]);
            }

            // Also send notification to ICT Officer (for email/database)
            try {
                $ictOfficer->notify(new \App\Notifications\IctTaskAssignedNotification($userAccess, $ictTaskAssignment));
            } catch (\Exception $notificationError) {
                Log::warning('Failed to send ICT task notification', [
                    'error' => $notificationError->getMessage()
                ]);
            }

            DB::commit();

            Log::info('HeadOfItController: ICT task assigned successfully', [
                'user_access_id' => $request->user_access_id,
                'ict_officer_user_id' => $request->ict_officer_user_id,
                'assignment_id' => $ictTaskAssignment->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ICT task assigned successfully to ' . $ictOfficer->name,
                'data' => [
                    'assignment_id' => $ictTaskAssignment->id,
                    'user_access_id' => $userAccess->id,
                    'ict_officer' => [
                        'id' => $ictOfficer->id,
                        'name' => $ictOfficer->name,
                        'email' => $ictOfficer->email
                    ],
                    'status' => $userAccess->getCalculatedOverallStatus(),
                    'assigned_at' => $ictTaskAssignment->assigned_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error assigning ICT task', [
                'user_access_id' => $request->user_access_id,
                'ict_officer_user_id' => $request->ict_officer_user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign ICT task',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get ICT task assignment history for a request (NEW system)
     */
    public function getIctTaskHistory($userAccessId)
    {
        try {
            Log::info('HeadOfItController: Getting ICT task history', ['user_access_id' => $userAccessId]);
            
            $assignments = \App\Models\IctTaskAssignment::with(['ictOfficer', 'assignedBy'])
                ->where('user_access_id', $userAccessId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'status' => $assignment->status,
                        'status_label' => $assignment->status_label,
                        'ict_officer' => [
                            'id' => $assignment->ictOfficer->id,
                            'name' => $assignment->ictOfficer->name,
                            'email' => $assignment->ictOfficer->email
                        ],
                        'assigned_by' => [
                            'id' => $assignment->assignedBy->id,
                            'name' => $assignment->assignedBy->name,
                            'email' => $assignment->assignedBy->email
                        ],
                        'assigned_at' => $assignment->assigned_at,
                        'started_at' => $assignment->started_at,
                        'completed_at' => $assignment->completed_at,
                        'cancelled_at' => $assignment->cancelled_at,
                        'assignment_notes' => $assignment->assignment_notes,
                        'progress_notes' => $assignment->progress_notes,
                        'completion_notes' => $assignment->completion_notes,
                        'created_at' => $assignment->created_at,
                        'updated_at' => $assignment->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'ICT task history retrieved successfully',
                'data' => $assignments
            ]);

        } catch (\Exception $e) {
            Log::error('HeadOfItController: Error getting ICT task history', [
                'user_access_id' => $userAccessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ICT task history',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cancel ICT task assignment (NEW system)
     */
    public function cancelIctTaskAssignment($userAccessId)
    {
        DB::beginTransaction();
        
        try {
            Log::info('HeadOfItController: Canceling ICT task assignment', ['user_access_id' => $userAccessId]);
            
            $userAccess = \App\Models\UserAccess::findOrFail($userAccessId);
            
            // Find active ICT assignment
            $assignment = \App\Models\IctTaskAssignment::where('user_access_id', $userAccessId)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active ICT assignment found for this request'
                ], 404);
            }

            // Update assignment status
            $assignment->update([
                'status' => \App\Models\IctTaskAssignment::STATUS_CANCELLED,
                'cancelled_at' => now()
            ]);

            // Update user access ICT Officer status back to pending and clear assignee so it is open for reassignment
            $userAccess->update([
                'ict_officer_status' => 'pending',
                'assigned_ict_officer_id' => null,
                'task_assigned_at' => null
            ]);

            DB::commit();

            Log::info('HeadOfItController: ICT task assignment cancelled successfully', ['user_access_id' => $userAccessId]);

            return response()->json([
                'success' => true,
                'message' => 'ICT task assignment cancelled successfully',
                'data' => [
                    'user_access_id' => $userAccess->id,
                    'status' => $userAccess->getCalculatedOverallStatus(),
                    'cancelled_at' => $assignment->cancelled_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('HeadOfItController: Error canceling ICT task assignment', [
                'user_access_id' => $userAccessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel ICT task assignment',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get detailed timeline for a specific access request
     */
    public function getAccessRequestTimeline($requestId)
    {
        try {
            $user = Auth::user();
            
            // Check if user has permission (Head of IT)
            if (!$user->hasRole('head_of_it')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied: User is not a Head of IT'
                ], 403);
            }

            Log::info('HeadOfItController: Getting access request timeline', [
                'user_id' => $user->id,
                'request_id' => $requestId
            ]);

            // Find the access request with all related data
            $request = \App\Models\UserAccess::with([
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
                    'ict_officer_user_id' => $request->ict_officer_user_id,
                    'ict_officer_assigned_at' => $request->ict_officer_assigned_at,
                    'ict_officer_started_at' => $request->ict_officer_started_at,
                    'ict_officer_implemented_at' => $request->ict_officer_implemented_at,
                    'ict_officer_comments' => $request->ict_officer_comments,
                    'ict_officer_pf_number' => $request->resolveApproverPfNumber($request->ict_officer_name),
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
            Log::error('HeadOfItController: Error getting access request timeline', [
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
