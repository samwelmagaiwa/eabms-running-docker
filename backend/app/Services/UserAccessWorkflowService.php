<?php

namespace App\Services;

use App\Models\UserAccess;
use App\Models\User;
use App\Events\ApprovalRequestSubmitted;
use App\Events\ApprovalStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class UserAccessWorkflowService
{
    /**
     * Create a new user access request with complete form data
     */
    public function createRequest(Request $request): UserAccess
    {
        return DB::transaction(function () use ($request) {
            $data = $this->validateAndPrepareRequestData($request);
            
            // Handle signature upload if provided
            if ($request->hasFile('signature')) {
                $data['signature_path'] = $this->handleSignatureUpload($request->file('signature'), $data['pf_number']);
            }
            
            $userAccess = UserAccess::create($data);
            
            // Fire approval request submitted event for SMS notification
            try {
                $user = User::find($userAccess->user_id) ?? auth()->user();
                $approvers = $this->getNextApprovers($userAccess);
                
                ApprovalRequestSubmitted::dispatch(
                    $user,
                    $userAccess,
                    'user_access',
                    $approvers,
                    [
                        'department' => $userAccess->department->name ?? 'N/A',
                        'access_type' => $this->getAccessTypeSummary($userAccess)
                    ]
                );
                
                Log::info('SMS notification event fired for new user access request', [
                    'request_id' => $userAccess->id,
                    'user_id' => $user->id,
                    'approvers_count' => count($approvers)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for new request', [
                    'request_id' => $userAccess->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the request creation if SMS notification fails
            }
            
            return $userAccess;
        });
    }

    /**
     * Update existing request with new form data
     */
    public function updateRequest(UserAccess $userAccess, Request $request): UserAccess
    {
        return DB::transaction(function () use ($userAccess, $request) {
            $data = $this->validateAndPrepareRequestData($request);
            
            // Handle signature upload if provided
            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($userAccess->signature_path) {
                    Storage::disk('public')->delete($userAccess->signature_path);
                }
                $data['signature_path'] = $this->handleSignatureUpload($request->file('signature'), $data['pf_number']);
            }
            
            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                $oldStatus = 'pending';
                $newStatus = $request->action === 'approve' ? 'hod_approved' : 'hod_rejected';
                
                // Get additional users to notify (next approvers if approved)
                $additionalNotifyUsers = [];
                if ($request->action === 'approve') {
                    $additionalNotifyUsers = $this->getNextApprovers($freshUserAccess);
                }
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments,
                    $additionalNotifyUsers
                );
                
                Log::info('SMS notification event fired for HOD approval', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for HOD approval', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }

    /**
     * Process HOD approval
     */
    public function processHodApproval(UserAccess $userAccess, Request $request): UserAccess
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'required|string|max:1000',
            'hod_name' => 'required|string|max:255',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        return DB::transaction(function () use ($userAccess, $request) {
            $data = [
                'hod_name' => $request->hod_name,
                'hod_comments' => $request->comments,
                'hod_approved_at' => now(),
            ];

            if ($request->action === 'approve') {
                // Update specific HOD status column
                $data['hod_status'] = 'approved';

                // Decide next stage: Divisional Director if department has one, otherwise skip to ICT Director
                $dept = $userAccess->department; // may be null
                $hasDivisional = false;
                if ($dept) {
                    $hasDivisional = (bool) ($dept->has_divisional_director ?? false) || !empty($dept->divisional_director_id);
                }

                if ($hasDivisional) {
                    // Normal path → Divisional Director
                    $data['status'] = 'pending_divisional';
                    // Ensure divisional stage is marked pending if not already set
                    $data['divisional_status'] = $userAccess->divisional_status ?: 'pending';
                } else {
                    // Skip divisional stage → go straight to ICT Director
                    $data['divisional_status'] = 'skipped';
                    $data['status'] = 'pending_ict_director';
                }
            } else {
                // Update specific HOD status column
                $data['hod_status'] = 'rejected';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'hod_rejected';
            }

            // Handle HOD signature if provided
            if ($request->hasFile('signature')) {
                $data['hod_signature_path'] = $this->handleSignatureUpload(
                    $request->file('signature'), 
                    $userAccess->pf_number . '_hod'
                );
            }

            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                // oldStatus: was pending_hod (waiting for HOD approval)
                // newStatus: hod_approved (for staff) or the workflow status for next approver routing
                $oldStatus = 'pending_hod';

                // Derive the status for SMS template based on approval outcome
                if ($request->action === 'approve') {
                    // For staff SMS, use hod_approved to show HOD approved their request
                    // The workflow status (pending_divisional/pending_ict_director) is used internally
                    $newStatus = 'hod_approved';
                } else {
                    $newStatus = 'hod_rejected';
                }
                
                // Get additional users to notify (next approvers if approved)
                $additionalNotifyUsers = [];
                if ($request->action === 'approve') {
                    $additionalNotifyUsers = $this->getNextApprovers($freshUserAccess);
                }
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments,
                    $additionalNotifyUsers
                );
                
                Log::info('SMS notification event fired for HOD approval', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id,
                    'next_approvers_count' => count($additionalNotifyUsers)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for HOD approval', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }

    /**
     * Process Divisional Director approval
     */
    public function processDivisionalApproval(UserAccess $userAccess, Request $request): UserAccess
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000',
            'divisional_director_name' => 'required|string|max:255',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        return DB::transaction(function () use ($userAccess, $request) {
            $data = [
                'divisional_director_name' => $request->divisional_director_name,
                'divisional_director_comments' => $request->comments,
                'divisional_approved_at' => now(),
            ];

            if ($request->action === 'approve') {
                // Update specific divisional status column
                $data['divisional_status'] = 'approved';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'pending_ict_director';
            } else {
                // Update specific divisional status column
                $data['divisional_status'] = 'rejected';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'divisional_rejected';
            }

            if ($request->hasFile('signature')) {
                $data['divisional_director_signature_path'] = $this->handleSignatureUpload(
                    $request->file('signature'), 
                    $userAccess->pf_number . '_divisional'
                );
            }

            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                // oldStatus: was pending_divisional (waiting for Divisional Director)
                // newStatus: divisional_approved or divisional_rejected
                $oldStatus = 'pending_divisional';
                $newStatus = $request->action === 'approve' ? 'divisional_approved' : 'divisional_rejected';
                
                // Get additional users to notify (ICT Directors if approved)
                $additionalNotifyUsers = [];
                if ($request->action === 'approve') {
                    $additionalNotifyUsers = $this->getNextApprovers($freshUserAccess);
                }
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments,
                    $additionalNotifyUsers
                );
                
                Log::info('SMS notification event fired for Divisional Director approval', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id,
                    'next_approvers_count' => count($additionalNotifyUsers)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for Divisional Director approval', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }

    /**
     * Process ICT Director approval
     */
    public function processIctDirectorApproval(UserAccess $userAccess, Request $request): UserAccess
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000',
            'ict_director_name' => 'required|string|max:255',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        return DB::transaction(function () use ($userAccess, $request) {
            $data = [
                'ict_director_name' => $request->ict_director_name,
                'ict_director_comments' => $request->comments,
                'ict_director_approved_at' => now(),
            ];

            if ($request->action === 'approve') {
                // Update specific ICT Director status column
                $data['ict_director_status'] = 'approved';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'pending_head_it';
            } else {
                // Update specific ICT Director status column
                $data['ict_director_status'] = 'rejected';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'ict_director_rejected';
            }

            if ($request->hasFile('signature')) {
                $data['ict_director_signature_path'] = $this->handleSignatureUpload(
                    $request->file('signature'), 
                    $userAccess->pf_number . '_ict_director'
                );
            }

            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                // oldStatus: was pending_ict_director (waiting for ICT Director)
                // newStatus: ict_director_approved or ict_director_rejected
                $oldStatus = 'pending_ict_director';
                $newStatus = $request->action === 'approve' ? 'ict_director_approved' : 'ict_director_rejected';
                
                // Get additional users to notify (Head of IT if approved)
                $additionalNotifyUsers = [];
                if ($request->action === 'approve') {
                    $additionalNotifyUsers = $this->getNextApprovers($freshUserAccess);
                }
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments,
                    $additionalNotifyUsers
                );
                
                Log::info('SMS notification event fired for ICT Director approval', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id,
                    'next_approvers_count' => count($additionalNotifyUsers)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for ICT Director approval', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }

    /**
     * Process Head IT approval
     */
    public function processHeadItApproval(UserAccess $userAccess, Request $request): UserAccess
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000',
            'head_it_name' => 'required|string|max:255',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        return DB::transaction(function () use ($userAccess, $request) {
            $data = [
                'head_it_name' => $request->head_it_name,
                'head_it_comments' => $request->comments,
                'head_it_approved_at' => now(),
            ];

            if ($request->action === 'approve') {
                // Update specific Head IT status column
                $data['head_it_status'] = 'approved';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'pending_ict_officer';
            } else {
                // Update specific Head IT status column
                $data['head_it_status'] = 'rejected';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'head_it_rejected';
            }

            if ($request->hasFile('signature')) {
                $data['head_it_signature_path'] = $this->handleSignatureUpload(
                    $request->file('signature'), 
                    $userAccess->pf_number . '_head_it'
                );
            }

            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                // oldStatus: was pending_head_it (waiting for Head IT)
                // newStatus: head_it_approved or head_it_rejected
                $oldStatus = 'pending_head_it';
                $newStatus = $request->action === 'approve' ? 'head_it_approved' : 'head_it_rejected';
                
                // Get additional users to notify (ICT Officers if approved)
                $additionalNotifyUsers = [];
                if ($request->action === 'approve') {
                    $additionalNotifyUsers = $this->getNextApprovers($freshUserAccess);
                }
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments,
                    $additionalNotifyUsers
                );
                
                Log::info('SMS notification event fired for Head IT approval', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id,
                    'next_approvers_count' => count($additionalNotifyUsers)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for Head IT approval', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }

    /**
     * Process ICT Officer implementation
     */
    public function processIctOfficerImplementation(UserAccess $userAccess, Request $request): UserAccess
    {
        $request->validate([
            'action' => 'required|in:implement,reject',
            'comments' => 'nullable|string|max:1000',
            'ict_officer_name' => 'required|string|max:255',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        return DB::transaction(function () use ($userAccess, $request) {
            $data = [
                'ict_officer_name' => $request->ict_officer_name,
                'ict_officer_comments' => $request->comments,
                'ict_officer_implemented_at' => now(),
            ];

            if ($request->action === 'implement') {
                // Update specific ICT Officer status column
                $data['ict_officer_status'] = 'implemented';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'implemented';
            } else {
                // Update specific ICT Officer status column
                $data['ict_officer_status'] = 'rejected';
                // Keep legacy status for backward compatibility during transition
                $data['status'] = 'rejected';
            }

            if ($request->hasFile('signature')) {
                $data['ict_officer_signature_path'] = $this->handleSignatureUpload(
                    $request->file('signature'), 
                    $userAccess->pf_number . '_ict_officer'
                );
            }

            $userAccess->update($data);
            $freshUserAccess = $userAccess->fresh();
            
            // Fire approval status changed event for SMS notification - CRITICAL for staff to know access is granted
            try {
                $user = $freshUserAccess->user;
                $approver = auth()->user();
                // oldStatus: was pending_ict_officer (waiting for implementation)
                // newStatus: implemented or ict_officer_rejected
                $oldStatus = 'pending_ict_officer';
                $newStatus = $request->action === 'implement' ? 'implemented' : 'ict_officer_rejected';
                
                ApprovalStatusChanged::dispatch(
                    $user,
                    $freshUserAccess,
                    'user_access',
                    $oldStatus,
                    $newStatus,
                    $approver,
                    $request->comments
                );
                
                Log::info('SMS notification event fired for ICT Officer implementation', [
                    'request_id' => $freshUserAccess->id,
                    'action' => $request->action,
                    'approver_id' => $approver->id,
                    'new_status' => $newStatus
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to fire SMS notification for ICT Officer implementation', [
                    'request_id' => $freshUserAccess->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $freshUserAccess;
        });
    }


    /**
     * Get requests based on user role and approval stage
     */
    public function getRequestsForApproval(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = UserAccess::with(['user', 'department']);

        // Filter by status based on user role
        $status = $this->getStatusForUserRole($user);
        if ($status) {
            $query->whereIn('status', $status);
        }
        
        // DEPARTMENT FILTERING: HODs only see requests from their department(s)
        if ($user->hasRole('head_of_department')) {
            $hodDepartmentIds = $user->departmentsAsHOD()->pluck('id')->toArray();
            if (!empty($hodDepartmentIds)) {
                $query->whereIn('department_id', $hodDepartmentIds);
            } else {
                // If user has HOD role but no departments assigned, show no requests
                $query->whereRaw('1 = 0');
            }
        }

        // Apply additional filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function($q) use ($filters) {
                $q->where('pf_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('staff_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('phone_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['request_type']) && $filters['request_type']) {
            $query->whereJsonContains('request_type', $filters['request_type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(User $user): array
    {
        $baseQuery = UserAccess::query();
        
        // Filter by user role permissions
        $allowedStatuses = $this->getStatusForUserRole($user);
        if ($allowedStatuses) {
            $baseQuery->whereIn('status', $allowedStatuses);
        }
        
        // DEPARTMENT FILTERING: HODs only see statistics from their department(s)
        if ($user->hasRole('head_of_department')) {
            $hodDepartmentIds = $user->departmentsAsHOD()->pluck('id')->toArray();
            if (!empty($hodDepartmentIds)) {
                $baseQuery->whereIn('department_id', $hodDepartmentIds);
            } else {
                // If user has HOD role but no departments assigned, show zero stats
                $baseQuery->whereRaw('1 = 0');
            }
        }

        return [
            'total' => $baseQuery->count(),
            'pending' => (clone $baseQuery)->where('status', 'like', 'pending%')->count(),
            'approved' => (clone $baseQuery)->whereIn('status', ['approved', 'implemented'])->count(),
            'rejected' => (clone $baseQuery)->where('status', 'like', '%rejected')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Validate and prepare request data
     */
    private function validateAndPrepareRequestData(Request $request): array
    {
        $request->validate([
            'pf_number' => 'required|string|max:50',
            'staff_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'request_type' => 'required|array',
            'request_type.*' => 'in:jeeva_access,wellsoft,internet_access_request',
            'wellsoft_modules_selected' => 'nullable|array',
            'jeeva_modules_selected' => 'nullable|array',
            'internet_purposes' => 'nullable|array',
            'access_type' => 'required|in:permanent,temporary',
            'temporary_until' => 'required_if:access_type,temporary|nullable|date|after:today',
        ]);

        $base = [
            'user_id' => auth()->id(),
            'pf_number' => $request->pf_number,
            'staff_name' => $request->staff_name,
            'phone_number' => $request->phone_number,
            'department_id' => $request->department_id,
            'request_type' => $request->request_type,
            'wellsoft_modules_selected' => $request->wellsoft_modules_selected,
            'jeeva_modules_selected' => $request->jeeva_modules_selected,
            'internet_purposes' => $request->internet_purposes,
            'access_type' => $request->access_type,
            'temporary_until' => $request->temporary_until,
            // Defaults: normal staff flow starts at HOD
            'status' => 'pending_hod',
            'hod_status' => 'pending',
            'divisional_status' => 'pending',
            'ict_director_status' => 'pending',
            'head_it_status' => 'pending',
            'ict_officer_status' => 'pending',
        ];

        // Special case: if submitter is ICT Officer, skip HOD and Divisional and start at ICT Director
        try {
            $user = auth()->user();
            $isIctOfficer = false;
            if ($user) {
                // Check multiple shapes of role storage
                if (method_exists($user, 'roles')) {
                    $isIctOfficer = $user->roles()->where('name', 'ict_officer')->exists();
                }
                if (!$isIctOfficer && isset($user->role) && isset($user->role->name)) {
                    $isIctOfficer = strtolower($user->role->name) === 'ict_officer';
                }
            }

            if ($isIctOfficer) {
                $base['status'] = 'pending_ict_director';
                $base['hod_status'] = 'skipped';
                $base['divisional_status'] = 'skipped';
                $base['ict_director_status'] = 'pending';
            } else {
                // Normal user flow: dynamically check for department roles
                $deptId = $request->department_id;
                $dept = \App\Models\Department::find($deptId);
                
                // Skip HOD if not assigned
                if (!$dept?->hod_user_id) {
                    $base['hod_status'] = 'skipped';
                    Log::info("Workflow Service: HOD stage skipped for Request - Department {$deptId} has no HOD user.");
                } else {
                    $base['hod_status'] = 'pending';
                }
                
                // Skip Divisional if not configured AND no user assigned
                $hasDivisional = $dept && ($dept->has_divisional_director || !empty($dept->divisional_director_id));
                if (!$hasDivisional) {
                    $base['divisional_status'] = 'skipped';
                    Log::info("Workflow Service: Divisional stage skipped for Request - Department {$deptId} not configured for divisional.");
                } else {
                    $base['divisional_status'] = 'pending';
                }
                
                // Adjust initial overall status based on skips
                if ($base['hod_status'] === 'skipped') {
                    if ($base['divisional_status'] === 'skipped') {
                        $base['status'] = 'pending_ict_director';
                    } else {
                        $base['status'] = 'pending_divisional';
                    }
                } else {
                    $base['status'] = 'pending_hod';
                }
            }
        } catch (\Throwable $e) {
            Log::error('Workflow Service: Error in dynamic skip detection: ' . $e->getMessage());
            // Fallback to default flow on any error determining role
        }

        return $base;
    }

    /**
     * Handle signature file upload
     */
    private function handleSignatureUpload($file, string $prefix): string
    {
        $timestamp = time();
        $extension = $file->getClientOriginalExtension();
        $filename = "signature_{$timestamp}.{$extension}";
        $path = "signatures/{$prefix}";
        
        return $file->storeAs($path, $filename, 'public');
    }

    /**
     * Get allowed statuses based on user role
     */
    private function getStatusForUserRole(User $user): ?array
    {
        if (!$user->hasRole()) {
            return null;
        }

        // Show ALL statuses for complete visibility and history tracking
        // Users should see the entire request lifecycle, not just their specific approval stage
        return UserAccess::STATUSES; // Return all statuses for all roles
    }

    /**
     * Transform request for API response
     */
    public function transformRequestData(UserAccess $request): array
    {
        return [
            'id' => $request->id,
            'pf_number' => $request->pf_number,
            'staff_name' => $request->staff_name,
            'phone_number' => $request->phone_number,
            'department' => $request->department->name ?? 'Unknown',
            'request_type' => $request->request_type,
            'request_type_name' => $request->request_type_name,
            'wellsoft_modules_selected' => $request->wellsoft_modules_selected,
            'jeeva_modules_selected' => $request->jeeva_modules_selected,
            'internet_purposes' => $request->internet_purposes,
            'access_type' => $request->access_type,
            'access_type_name' => $request->access_type_name,
            'temporary_until' => $request->temporary_until?->format('Y-m-d'),
            'status' => $request->status,
            'status_name' => $request->status_name,
            'signature_path' => $request->signature_path,
            'all_modules' => $request->all_modules,
            'next_approval_stage' => $request->getNextApprovalStage(),
            
            // HOD Approval
            'hod_name' => $request->hod_name,
            'hod_pf_number' => $request->resolveApproverPfNumber($request->hod_name),
            'hod_comments' => $request->hod_comments,
            'hod_approved_at' => $request->hod_approved_at?->format('Y-m-d H:i:s'),
            
            // Divisional Director
            'divisional_director_name' => $request->divisional_director_name,
            'divisional_pf_number' => $request->resolveApproverPfNumber($request->divisional_director_name),
            'divisional_director_signature_path' => $request->divisional_director_signature_path,
            'divisional_director_comments' => $request->divisional_director_comments,
            'divisional_approved_at' => $request->divisional_approved_at?->format('Y-m-d H:i:s'),
            
            // ICT Director
            'ict_director_name' => $request->ict_director_name,
            'ict_director_pf_number' => $request->resolveApproverPfNumber($request->ict_director_name),
            'ict_director_signature_path' => $request->ict_director_signature_path,
            'ict_director_comments' => $request->ict_director_comments,
            'ict_director_approved_at' => $request->ict_director_approved_at?->format('Y-m-d H:i:s'),
            
            // Head IT
            'head_it_name' => $request->head_it_name,
            'head_it_pf_number' => $request->resolveApproverPfNumber($request->head_it_name),
            'head_it_signature_path' => $request->head_it_signature_path,
            'head_it_comments' => $request->head_it_comments,
            'head_it_approved_at' => $request->head_it_approved_at?->format('Y-m-d H:i:s'),
            
            // ICT Officer
            'ict_officer_name' => $request->ict_officer_name,
            'ict_officer_pf_number' => $request->resolveApproverPfNumber($request->ict_officer_name),
            'ict_officer_signature_path' => $request->ict_officer_signature_path,
            'ict_officer_comments' => $request->ict_officer_comments,
            'ict_officer_implemented_at' => $request->ict_officer_implemented_at?->format('Y-m-d H:i:s'),
            'implementation_comments' => $request->implementation_comments,
            
            'created_at' => $request->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $request->updated_at->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Get next approvers based on current status
     * 
     * @param UserAccess $userAccess
     * @return array
     */
    private function getNextApprovers(UserAccess $userAccess): array
    {
        $approvers = [];
        
        // Based on the current status, get the next approvers
        switch ($userAccess->status) {
            case 'pending':
            case 'pending_hod':
                // Get HODs for the request's department
                $hods = User::whereHas('roles', function ($query) {
                    $query->where('name', 'head_of_department');
                })
                ->whereHas('departmentsAsHOD', function ($query) use ($userAccess) {
                    $query->where('departments.id', $userAccess->department_id);
                })
                ->whereNotNull('phone')
                ->get()
                ->toArray();
                
                $approvers = array_merge($approvers, $hods);
                break;
                
            case 'hod_approved':
            case 'pending_divisional':
                // Get Divisional Directors for the request's department
                $divisionalDirectors = User::whereHas('roles', function ($query) {
                    $query->where('name', 'divisional_director');
                })
                ->whereHas('departmentsAsDivisionalDirector', function ($query) use ($userAccess) {
                    $query->where('departments.id', $userAccess->department_id);
                })
                ->whereNotNull('phone')
                ->get()
                ->toArray();
                
                $approvers = array_merge($approvers, $divisionalDirectors);
                break;
                
            case 'divisional_approved':
            case 'pending_ict_director':
                // Get ICT Directors
                $ictDirectors = User::whereHas('roles', function ($query) {
                    $query->where('name', 'ict_director');
                })
                ->whereNotNull('phone')
                ->get()
                ->toArray();
                
                $approvers = array_merge($approvers, $ictDirectors);
                break;
                
            case 'ict_director_approved':
            case 'pending_head_it':
                // Get Head IT
                $headIT = User::whereHas('roles', function ($query) {
                    $query->where('name', 'head_it');
                })
                ->whereNotNull('phone')
                ->get()
                ->toArray();
                
                $approvers = array_merge($approvers, $headIT);
                break;
                
            case 'head_it_approved':
            case 'pending_ict_officer':
                // Get ICT Officers
                $ictOfficers = User::whereHas('roles', function ($query) {
                    $query->where('name', 'ict_officer');
                })
                ->whereNotNull('phone')
                ->get()
                ->toArray();
                
                $approvers = array_merge($approvers, $ictOfficers);
                break;
        }
        
        return $approvers;
    }
    
    /**
     * Get access type summary for SMS notifications
     * 
     * @param UserAccess $userAccess
     * @return string
     */
    private function getAccessTypeSummary(UserAccess $userAccess): string
    {
        $types = [];
        
        if (is_array($userAccess->request_type)) {
            if (in_array('jeeva_access', $userAccess->request_type)) {
                $types[] = 'Jeeva';
            }
            if (in_array('wellsoft', $userAccess->request_type)) {
                $types[] = 'Wellsoft';
            }
            if (in_array('internet_access_request', $userAccess->request_type)) {
                $types[] = 'Internet';
            }
        }
        
        return implode(', ', $types) ?: 'System Access';
    }
}
