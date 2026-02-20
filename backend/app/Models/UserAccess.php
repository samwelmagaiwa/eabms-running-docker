<?php

namespace App\Models;

use App\Models\Signature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class UserAccess extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_access';
    
    /**
     * Cached StatusMigrationService instance to avoid creating multiple instances per request
     */
    private static $statusMigrationService = null;
    
    /**
     * Cached calculated status to avoid recalculating multiple times per request
     */
    private $cachedCalculatedStatus = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'request_id',
        'pf_number',
        'staff_name',
        'phone_number',
        'department_id',
        'signature_path',
        'request_type',
        'internet_purposes',
        'module_requested_for',
        'wellsoft_modules_selected',
        'jeeva_modules_selected',
        'access_type',
        'temporary_until',
        'divisional_status',
        'hod_status',
        'ict_director_status',
        'head_it_status',
        'ict_officer_status',
        'ict_officer_user_id',
        'ict_officer_assigned_at',
        'ict_officer_started_at',
        'hod_comments',
        'hod_resubmission_notes',
        'resubmitted_at',
        'resubmitted_by',
        'hod_name',
        'hod_signature_path',
        'hod_approved_at',
        'hod_approved_by',
        'hod_approved_by_name',
        'divisional_director_name',
        'divisional_director_signature_path',
        'divisional_director_comments',
        'divisional_approved_at',
        'ict_director_name',
        'ict_director_signature_path',
        'ict_director_comments',
        'ict_director_rejection_reasons',
        'ict_director_approved_at',
        'head_it_name',
        'head_it_signature_path',
        'head_it_comments',
        'head_it_approved_at',
        'ict_officer_name',
        'ict_officer_signature_path',
        'ict_officer_comments',
        'ict_officer_implemented_at',
        'implementation_comments',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        // SMS notification tracking
        'sms_notifications',
        'sms_sent_to_hod_at',
        'sms_to_hod_status',
        'sms_sent_to_divisional_at',
        'sms_to_divisional_status',
        'sms_sent_to_ict_director_at',
        'sms_to_ict_director_status',
        'sms_sent_to_head_it_at',
        'sms_to_head_it_status',
        'sms_sent_to_requester_at',
        'sms_to_requester_status',
        // ICT Officer SMS tracking
        'sms_sent_to_ict_officer_at',
        'sms_to_ict_officer_status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'request_type' intentionally NOT cast here to avoid JSON decode errors on legacy values
        'wellsoft_modules_selected' => 'array',
        'jeeva_modules_selected' => 'array',
        'internet_purposes' => 'array',
        'temporary_until' => 'date',
        'hod_approved_at' => 'datetime',
        'resubmitted_at' => 'datetime',
        'divisional_approved_at' => 'datetime',
        'ict_director_approved_at' => 'datetime',
        'head_it_approved_at' => 'datetime',
        'ict_officer_assigned_at' => 'datetime',
        'ict_officer_started_at' => 'datetime',
        'ict_officer_implemented_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'status',
        'request_type_name',
        'status_name',
        'access_type_name',
        'all_modules'
    ];

    // Removed custom accessors and mutators since we're using Laravel's built-in array casting

    /**
     * Request type constants
     */
    const REQUEST_TYPES = [
        'jeeva_access' => 'Jeeva Access',
        'wellsoft' => 'Wellsoft',
        'internet_access_request' => 'Internet Access Request',
    ];

    /**
     * Status constants
     */
    const STATUSES = [
        'pending' => 'Pending',
        'pending_hod' => 'Pending HOD Approval',
        'hod_approved' => 'HOD Approved',
        'hod_rejected' => 'HOD Rejected',
        'pending_divisional' => 'Pending Divisional Director',
        'divisional_approved' => 'Divisional Director Approved',
        'divisional_rejected' => 'Divisional Director Rejected',
        'pending_ict_director' => 'Pending ICT Director',
        'ict_director_approved' => 'ICT Director Approved',
        'ict_director_rejected' => 'ICT Director Rejected',
        'pending_head_it' => 'Pending Head IT',
        'head_it_approved' => 'Head IT Approved',
        'head_it_rejected' => 'Head IT Rejected',
        'pending_ict_officer' => 'Pending ICT Officer',
        'implemented' => 'Implemented',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'in_review' => 'In Review',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Get the user that owns the access request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department that the request belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the selected Wellsoft modules for this request.
     */
    public function selectedWellsoftModules(): BelongsToMany
    {
        return $this->belongsToMany(WellsoftModule::class, 'wellsoft_modules_selected', 'user_access_id', 'module_id')
                    ->withTimestamps();
    }

    /**
     * Get the selected Jeeva modules for this request.
     */
    public function selectedJeevaModules(): BelongsToMany
    {
        return $this->belongsToMany(JeevaModule::class, 'jeeva_modules_selected', 'user_access_id', 'module_id')
                    ->withTimestamps();
    }

    /**
     * Digital signatures linked to this request (document_id = user_access.id)
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id', 'id');
    }

    /**
     * Check if digital signatures contain an entry by user name (case-insensitive)
     */
    public function historyHasSignerByName(?string $name): bool
    {
        if (!$name) {
            return false;
        }
        $n = mb_strtolower(trim($name));
        return $this->signatures()
            ->whereHas('user', function ($q) use ($n) {
                $q->whereRaw('LOWER(name) = ?', [$n]);
            })
            ->exists();
    }

    /**
     * Check if digital signatures contain an entry by user id
     */
    public function historyHasSignerByUserId(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->signatures()->where('user_id', $userId)->exists();
    }

    /**
     * Get the ICT task assignments for this request.
     */
    public function ictTaskAssignments(): HasMany
    {
        return $this->hasMany(IctTaskAssignment::class);
    }

    /**
     * Get the current active ICT task assignment.
     */
    public function currentIctTaskAssignment(): HasOne
    {
        return $this->hasOne(IctTaskAssignment::class)->latest();
    }

    /**
     * Check if the access is permanent.
     */
    public function isPermanent(): bool
    {
        return $this->access_type === 'permanent';
    }

    /**
     * Check if the access is temporary.
     */
    public function isTemporary(): bool
    {
        return $this->access_type === 'temporary';
    }

    /**
     * Check if the temporary access has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->isTemporary() || !$this->temporary_until) {
            return false;
        }
        
        return now()->isAfter($this->temporary_until);
    }

    /**
     * Check if HOD has approved this request.
     */
    public function isHodApproved(): bool
    {
        return !empty($this->hod_name) && !empty($this->hod_approved_at);
    }

    /**
     * Resolve PF number for an approver name by checking signatures or profile data
     */
    public function resolveApproverPfNumber(?string $stageApproverName): ?string
    {
        if (!$stageApproverName || $stageApproverName === 'N/A' || empty($stageApproverName)) {
            return null;
        }

        return \Cache::remember("user_pf.{$stageApproverName}", 3600, function() use ($stageApproverName) {
            try {
                // 1. Try to find via signatures relationship
                $signature = $this->signatures()
                    ->whereHas('user', function ($q) use ($stageApproverName) {
                        $q->where('name', $stageApproverName);
                    })->with('user')->first();

                if ($signature && $signature->user) {
                    return $signature->user->pf_number;
                }

                // 2. Fallback: Search user by name directly
                $user = \App\Models\User::where('name', $stageApproverName)->first();
                return $user ? $user->pf_number : null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Check if Divisional Director has approved this request.
     */
    public function isDivisionalDirectorApproved(): bool
    {
        return !empty($this->divisional_director_name) && !empty($this->divisional_approved_at);
    }

    /**
     * Check if ICT Director has approved this request.
     */
    public function isIctDirectorApproved(): bool
    {
        return !empty($this->ict_director_name) && !empty($this->ict_director_approved_at);
    }

    // ========================================
    // STATUS CONSTANTS FOR NEW COLUMNS
    // ========================================
    
    const HOD_STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];
    const DIVISIONAL_STATUSES = ['pending', 'approved', 'rejected'];
    const ICT_DIRECTOR_STATUSES = ['pending', 'approved', 'rejected'];
    const HEAD_IT_STATUSES = ['pending', 'approved', 'rejected'];
    const ICT_OFFICER_STATUSES = ['pending', 'implemented', 'rejected'];

    /**
     * Get the approval progress percentage (using new status columns).
     */
    public function getApprovalProgress(): int
    {
        $totalSteps = 3; // HOD, Divisional Director, ICT Director
        $completedSteps = 0;
        
        // Use new status columns if available, fallback to old logic
        if ($this->hod_status === 'approved' || $this->isHodApproved()) $completedSteps++;
        if ($this->divisional_status === 'approved' || $this->isDivisionalDirectorApproved()) $completedSteps++;
        if ($this->ict_director_status === 'approved' || $this->isIctDirectorApproved()) $completedSteps++;
        
        return (int) (($completedSteps / $totalSteps) * 100);
    }

    /**
     * Get the next approval step.
     */
    public function getNextApprovalStep(): ?string
    {
        if (!$this->isHodApproved()) {
            return 'HOD/BM Approval';
        }
        
        if (!$this->isDivisionalDirectorApproved()) {
            return 'Divisional Director Approval';
        }
        
        if (!$this->isIctDirectorApproved()) {
            return 'ICT Director Approval';
        }
        
        if (!$this->isHeadItApproved()) {
            return 'Head of IT Approval';
        }
        
        if (!$this->isIctOfficerImplemented()) {
            return 'ICT Officer Implementation';
        }
        
        return null; // All steps completed
    }

    /**
     * Check if Head of IT has approved this request.
     */
    public function isHeadItApproved(): bool
    {
        return !empty($this->head_it_name) && !empty($this->head_it_approved_at);
    }

    /**
     * Check if ICT Officer has implemented this request.
     */
    public function isIctOfficerImplemented(): bool
    {
        return !empty($this->ict_officer_name) && !empty($this->ict_officer_implemented_at);
    }

    // ========================================
    // NEW APPROVAL STATUS COLUMN HELPERS
    // ========================================

    /**
     * Get the approval status for HOD
     */
    public function getHodApprovalStatus(): string
    {
        return $this->hod_status ?? 'pending';
    }

    /**
     * Check if HOD has approved using new status column
     */
    public function isHodApprovedByStatus(): bool
    {
        return $this->hod_status === 'approved';
    }

    /**
     * Get the approval status for Divisional Director
     */
    public function getDivisionalApprovalStatus(): string
    {
        return $this->divisional_status ?? 'pending';
    }

    /**
     * Check if Divisional Director has approved using new status column
     */
    public function isDivisionalApprovedByStatus(): bool
    {
        return $this->divisional_status === 'approved';
    }

    /**
     * Get the approval status for ICT Director
     */
    public function getIctDirectorApprovalStatus(): string
    {
        return $this->ict_director_status ?? 'pending';
    }

    /**
     * Check if ICT Director has approved using new status column
     */
    public function isIctDirectorApprovedByStatus(): bool
    {
        return $this->ict_director_status === 'approved';
    }

    /**
     * Get the approval status for Head IT
     */
    public function getHeadItApprovalStatus(): string
    {
        return $this->head_it_status ?? 'pending';
    }

    /**
     * Check if Head IT has approved using new status column
     */
    public function isHeadItApprovedByStatus(): bool
    {
        return $this->head_it_status === 'approved';
    }

    /**
     * Get the implementation status for ICT Officer
     */
    public function getIctOfficerImplementationStatus(): string
    {
        return $this->ict_officer_status ?? 'pending';
    }

    /**
     * Check if ICT Officer has implemented using new status column
     */
    public function isIctOfficerImplementedByStatus(): bool
    {
        return $this->ict_officer_status === 'implemented';
    }

    /**
     * Get the calculated overall status from new status columns (with caching)
     */
    public function getCalculatedOverallStatus(): string
    {
        // Return cached value if available
        if ($this->cachedCalculatedStatus !== null) {
            return $this->cachedCalculatedStatus;
        }
        
        $migrationService = $this->getStatusMigrationService();
        
        $statusColumns = [
            'hod_status' => $this->hod_status,
            'divisional_status' => $this->divisional_status,
            'ict_director_status' => $this->ict_director_status,
            'head_it_status' => $this->head_it_status,
            'ict_officer_status' => $this->ict_officer_status
        ];
        
        // Cache the result
        $this->cachedCalculatedStatus = $migrationService->calculateOverallStatus($statusColumns);
        return $this->cachedCalculatedStatus;
    }
    
    /**
     * Get or create a shared StatusMigrationService instance
     */
    private function getStatusMigrationService(): \App\Services\StatusMigrationService
    {
        if (self::$statusMigrationService === null) {
            self::$statusMigrationService = new \App\Services\StatusMigrationService();
        }
        return self::$statusMigrationService;
    }

    /**
     * Get the next pending approval stage using new status columns
     */
    public function getNextPendingStageFromColumns(): ?string
    {
        $migrationService = $this->getStatusMigrationService();
        
        $statusColumns = [
            'hod_status' => $this->hod_status,
            'divisional_status' => $this->divisional_status,
            'ict_director_status' => $this->ict_director_status,
            'head_it_status' => $this->head_it_status,
            'ict_officer_status' => $this->ict_officer_status
        ];
        
        return $migrationService->getNextPendingStage($statusColumns);
    }

    /**
     * Check if workflow is complete using new status columns
     */
    public function isWorkflowCompleteByColumns(): bool
    {
        $migrationService = $this->getStatusMigrationService();
        
        $statusColumns = [
            'hod_status' => $this->hod_status,
            'divisional_status' => $this->divisional_status,
            'ict_director_status' => $this->ict_director_status,
            'head_it_status' => $this->head_it_status,
            'ict_officer_status' => $this->ict_officer_status
        ];
        
        return $migrationService->isWorkflowComplete($statusColumns);
    }

    /**
     * Check if workflow has rejections using new status columns
     */
    public function hasRejectionsInColumns(): bool
    {
        $migrationService = $this->getStatusMigrationService();
        
        $statusColumns = [
            'hod_status' => $this->hod_status,
            'divisional_status' => $this->divisional_status,
            'ict_director_status' => $this->ict_director_status,
            'head_it_status' => $this->head_it_status,
            'ict_officer_status' => $this->ict_officer_status
        ];
        
        return $migrationService->hasRejections($statusColumns);
    }

    /**
     * Get workflow progress percentage using new status columns
     */
    public function getWorkflowProgressFromColumns(): int
    {
        $migrationService = $this->getStatusMigrationService();
        
        $statusColumns = [
            'hod_status' => $this->hod_status,
            'divisional_status' => $this->divisional_status,
            'ict_director_status' => $this->ict_director_status,
            'head_it_status' => $this->head_it_status,
            'ict_officer_status' => $this->ict_officer_status
        ];
        
        return $migrationService->getWorkflowProgress($statusColumns);
    }

    /**
     * Check if the implementation workflow is complete.
     */
    public function isImplementationComplete(): bool
    {
        return $this->isHeadItApproved() && $this->isIctOfficerImplemented();
    }

    /**
     * Get the implementation progress percentage.
     */
    public function getImplementationProgress(): int
    {
        $totalSteps = 2; // Head of IT, ICT Officer
        $completedSteps = 0;
        
        if ($this->isHeadItApproved()) $completedSteps++;
        if ($this->isIctOfficerImplemented()) $completedSteps++;
        
        return (int) (($completedSteps / $totalSteps) * 100);
    }

    /**
     * Get the complete workflow progress percentage (approval + implementation).
     */
    public function getCompleteWorkflowProgress(): int
    {
        $totalSteps = 5; // HOD, Divisional Director, ICT Director, Head of IT, ICT Officer
        $completedSteps = 0;
        
        if ($this->isHodApproved()) $completedSteps++;
        if ($this->isDivisionalDirectorApproved()) $completedSteps++;
        if ($this->isIctDirectorApproved()) $completedSteps++;
        if ($this->isHeadItApproved()) $completedSteps++;
        if ($this->isIctOfficerImplemented()) $completedSteps++;
        
        return (int) (($completedSteps / $totalSteps) * 100);
    }

    /**
     * Get the current workflow stage.
     */
    public function getCurrentWorkflowStage(): string
    {
        if (!$this->isHodApproved()) {
            return 'Pending HOD Approval';
        }
        
        if (!$this->isDivisionalDirectorApproved()) {
            return 'Pending Divisional Director Approval';
        }
        
        if (!$this->isIctDirectorApproved()) {
            return 'Pending ICT Director Approval';
        }
        
        if (!$this->isHeadItApproved()) {
            return 'Pending Head of IT Approval';
        }
        
        if (!$this->isIctOfficerImplemented()) {
            return 'Pending ICT Officer Implementation';
        }
        
        return 'Implementation Complete';
    }

    /**
     * Scope a query to only include requests of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where(function($q) use ($type) {
            $q->whereJsonContains('request_type', $type)
              ->orWhere('request_type', 'LIKE', '%' . $type . '%');
        });
    }

    /**
     * Scope a query to only include requests with a given status.
     * NOTE: This method is deprecated since status column no longer exists.
     * Use specific status column queries instead.
     * @deprecated
     */
    public function scopeWithStatus($query, string $status)
    {
        // Since status column no longer exists, we need to calculate based on role-specific status columns
        return $query->where(function($q) use ($status) {
            switch ($status) {
                case 'pending':
                    $q->where(function($subQ) {
                        $subQ->where('hod_status', 'pending')
                             ->orWhereNull('hod_status');
                    });
                    break;
                case 'hod_approved':
                    $q->where('hod_status', 'approved')
                      ->where(function($subQ) {
                          $subQ->where('divisional_status', 'pending')
                               ->orWhereNull('divisional_status');
                      });
                    break;
                case 'divisional_approved':
                    $q->where('divisional_status', 'approved')
                      ->where(function($subQ) {
                          $subQ->where('ict_director_status', 'pending')
                               ->orWhereNull('ict_director_status');
                      });
                    break;
                case 'ict_director_approved':
                    $q->where('ict_director_status', 'approved')
                      ->where(function($subQ) {
                          $subQ->where('head_it_status', 'pending')
                               ->orWhereNull('head_it_status');
                      });
                    break;
                case 'head_it_approved':
                    $q->where('head_it_status', 'approved')
                      ->where(function($subQ) {
                          $subQ->where('ict_officer_status', 'pending')
                               ->orWhereNull('ict_officer_status');
                      });
                    break;
                case 'implemented':
                    $q->where('ict_officer_status', 'implemented');
                    break;
                case 'hod_rejected':
                    $q->where('hod_status', 'rejected');
                    break;
                case 'divisional_rejected':
                    $q->where('divisional_status', 'rejected');
                    break;
                case 'ict_director_rejected':
                    $q->where('ict_director_status', 'rejected');
                    break;
                case 'head_it_rejected':
                    $q->where('head_it_status', 'rejected');
                    break;
                case 'rejected':
                    $q->where(function($subQ) {
                        $subQ->where('hod_status', 'rejected')
                             ->orWhere('divisional_status', 'rejected')
                             ->orWhere('ict_director_status', 'rejected')
                             ->orWhere('head_it_status', 'rejected')
                             ->orWhere('ict_officer_status', 'rejected');
                    });
                    break;
            }
        });
    }

    /**
     * Scope a query to only include pending requests.
     * @deprecated Use scopeWithStatus('pending') instead
     */
    public function scopePending($query)
    {
        return $this->scopeWithStatus($query, 'pending');
    }

    /**
     * Get the formatted request type name.
     */
    public function getRequestTypeNameAttribute(): string
    {
        $requestTypes = $this->getRequestTypesArray();
        if (empty($requestTypes)) {
            return 'N/A';
        }
        return implode(', ', array_map(fn($type) => self::REQUEST_TYPES[$type] ?? $type, $requestTypes));
    }

    /**
     * Get the formatted status name.
     */
    public function getStatusNameAttribute(): string
    {
        $calculatedStatus = $this->getCalculatedStatus();
        return self::STATUSES[$calculatedStatus] ?? $calculatedStatus;
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->getCalculatedStatus() === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        $status = $this->getCalculatedStatus();
        return $status === 'approved' || $status === 'implemented';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        $status = $this->getCalculatedStatus();
        return in_array($status, [
            'rejected', 'hod_rejected', 'divisional_rejected', 
            'ict_director_rejected', 'head_it_rejected', 'ict_officer_rejected'
        ]);
    }

    /**
     * Get the next approval needed in the workflow
     */
    public function getNextApprovalNeeded(): string
    {
        // If no HOD approval yet
        if (empty($this->hod_approved_at)) {
            return 'HOD/BM Approval';
        }
        
        // If no divisional director approval yet
        if (empty($this->divisional_approved_at)) {
            return 'Divisional Director Approval';
        }
        
        // If no ICT director approval yet
        if (empty($this->ict_director_approved_at)) {
            return 'ICT Director Approval';
        }
        
        // If no Head IT approval yet
        if (empty($this->head_it_approved_at)) {
            return 'Head IT Approval';
        }
        
        // If no ICT Officer implementation yet
        if (empty($this->ict_officer_implemented_at)) {
            return 'ICT Officer Implementation';
        }
        
        return 'Complete';
    }

    /**
     * Get the current approval stage
     */
    public function getCurrentApprovalStage(): array
    {
        $stages = [
            'hod' => [
                'name' => 'HOD/BM',
                'completed' => !empty($this->hod_approved_at),
                'has_signature' => (!empty($this->hod_signature_path)) || $this->historyHasSignerByName($this->hod_name),
                'approved_by' => $this->hod_name,
                'approved_at' => $this->hod_approved_at
            ],
            'divisional' => [
                'name' => 'Divisional Director',
                'completed' => !empty($this->divisional_approved_at),
                'has_signature' => (!empty($this->divisional_director_signature_path)) || $this->historyHasSignerByName($this->divisional_director_name),
                'approved_by' => $this->divisional_director_name,
                'approved_at' => $this->divisional_approved_at
            ],
            'ict_director' => [
                'name' => 'ICT Director',
                'completed' => !empty($this->ict_director_approved_at),
                'has_signature' => (!empty($this->ict_director_signature_path)) || $this->historyHasSignerByName($this->ict_director_name),
                'approved_by' => $this->ict_director_name,
                'approved_at' => $this->ict_director_approved_at
            ],
            'head_it' => [
                'name' => 'Head IT',
                'completed' => !empty($this->head_it_approved_at),
                'has_signature' => (!empty($this->head_it_signature_path)) || $this->historyHasSignerByName($this->head_it_name),
                'approved_by' => $this->head_it_name,
                'approved_at' => $this->head_it_approved_at
            ],
            'ict_officer' => [
                'name' => 'ICT Officer',
                'completed' => !empty($this->ict_officer_implemented_at),
                'has_signature' => (!empty($this->ict_officer_signature_path)) || $this->historyHasSignerByName($this->ict_officer_name) || $this->historyHasSignerByUserId($this->ict_officer_user_id),
                'implemented_by' => $this->ict_officer_name,
                'implemented_at' => $this->ict_officer_implemented_at
            ]
        ];
        
        return $stages;
    }

    /**
     * Check if the request is in review.
     */
    public function isInReview(): bool
    {
        return $this->getCalculatedStatus() === 'in_review';
    }

    /**
     * Check if the request contains a specific request type.
     */
    public function hasRequestType(string $type): bool
    {
        $requestTypes = $this->getRequestTypesArray();
        return in_array($type, $requestTypes);
    }

    /**
     * Get all request types as an array (robust against legacy formats).
     */
    public function getRequestTypesArray(): array
    {
        $val = $this->request_type; // will use accessor below
        return is_array($val) ? $val : [];
    }

    /**
     * Access type constants
     */
    const ACCESS_TYPES = [
        'permanent' => 'Permanent (until retirement)',
        'temporary' => 'Temporary'
    ];

    /**
     * Get next approval stage based on current status
     */
    public function getNextApprovalStage(): ?string
    {
        $calculatedStatus = $this->getCalculatedStatus();
        return match($calculatedStatus) {
            'pending', 'pending_hod' => 'hod',
            'hod_approved', 'pending_divisional' => 'divisional_director',
            'divisional_approved', 'pending_ict_director' => 'ict_director',
            'ict_director_approved', 'pending_head_it' => 'head_it',
            'head_it_approved', 'pending_ict_officer' => 'ict_officer',
            default => null
        };
    }

    /**
     * Check if request is at a specific approval stage
     */
    public function isAtStage(string $stage): bool
    {
        $calculatedStatus = $this->getCalculatedStatus();
        return match($stage) {
            'hod' => in_array($calculatedStatus, ['pending', 'pending_hod']),
            'divisional_director' => in_array($calculatedStatus, ['hod_approved', 'pending_divisional']),
            'ict_director' => in_array($calculatedStatus, ['divisional_approved', 'pending_ict_director']),
            'head_it' => in_array($calculatedStatus, ['ict_director_approved', 'pending_head_it']),
            'ict_officer' => in_array($calculatedStatus, ['head_it_approved', 'pending_ict_officer']),
            default => false
        };
    }

    /**
     * Check if request has specific modules
     */
    public function hasWellsoftModules(): bool
    {
        return !empty($this->wellsoft_modules_selected);
    }

    public function hasJeevaModules(): bool
    {
        return !empty($this->jeeva_modules_selected);
    }

    public function hasInternetPurposes(): bool
    {
        return !empty($this->internet_purposes);
    }

    /**
     * Get the stage status (pending/completed) for each approval stage
     */
    public function getDetailedApprovalProgress(): array
    {
        return [
            'hod' => [
                'stage_name' => 'HOD/BM',
                'status' => !empty($this->hod_approved_at) ? 'completed' : 'pending',
                'approved_at' => $this->hod_approved_at,
                'approved_by' => $this->hod_name,
                'has_signature' => (!empty($this->hod_signature_path)) || $this->historyHasSignerByName($this->hod_name),
                'order' => 1
            ],
            'divisional_director' => [
                'stage_name' => 'Divisional Director',
                'status' => !empty($this->divisional_approved_at) ? 'completed' : 
                           (!empty($this->hod_approved_at) ? 'pending' : 'locked'),
                'approved_at' => $this->divisional_approved_at,
                'approved_by' => $this->divisional_director_name,
                'has_signature' => (!empty($this->divisional_director_signature_path)) || $this->historyHasSignerByName($this->divisional_director_name),
                'order' => 2
            ],
            'ict_director' => [
                'stage_name' => 'Director of ICT',
                'status' => !empty($this->ict_director_approved_at) ? 'completed' : 
                           (!empty($this->divisional_approved_at) ? 'pending' : 'locked'),
                'approved_at' => $this->ict_director_approved_at,
                'approved_by' => $this->ict_director_name,
                'has_signature' => (!empty($this->ict_director_signature_path)) || $this->historyHasSignerByName($this->ict_director_name),
                'order' => 3
            ],
            'head_it' => [
                'stage_name' => 'Head of IT',
                'status' => !empty($this->head_it_approved_at) ? 'completed' : 
                           (!empty($this->ict_director_approved_at) ? 'pending' : 'locked'),
                'approved_at' => $this->head_it_approved_at,
                'approved_by' => $this->head_it_name,
                'has_signature' => (!empty($this->head_it_signature_path)) || $this->historyHasSignerByName($this->head_it_name),
                'order' => 4,
                'section' => 'implementation'
            ],
            'ict_officer' => [
                'stage_name' => 'ICT Officer',
                'status' => !empty($this->ict_officer_implemented_at) ? 'completed' : 
                           (!empty($this->head_it_approved_at) ? 'pending' : 'locked'),
                'implemented_at' => $this->ict_officer_implemented_at,
                'implemented_by' => $this->ict_officer_name,
                'has_signature' => (!empty($this->ict_officer_signature_path)) || $this->historyHasSignerByName($this->ict_officer_name) || $this->historyHasSignerByUserId($this->ict_officer_user_id),
                'order' => 5,
                'section' => 'implementation'
            ]
        ];
    }
    
    /**
     * Update individual stage status based on approval action
     */
    public function updateStageStatus(string $stage, string $status): void
    {
        $statusColumn = $stage . '_status';
        
        if (in_array($statusColumn, ['hod_status', 'divisional_status', 'ict_director_status', 'head_it_status', 'ict_officer_status'])) {
            $this->{$statusColumn} = $status;
            
            // Update the main status based on current workflow stage
            $this->updateMainStatus();
        }
    }
    
    /**
     * Calculate the overall status based on role-specific status columns
     * This replaces the need for the general 'status' column
     */
    public function getCalculatedStatus(): string
    {
        // Check for cancelled status first (highest priority)
        if ($this->hod_status === 'cancelled') {
            return 'cancelled';
        }
        
        // Check for rejections (high priority)
        if ($this->hod_status === 'rejected') {
            return 'hod_rejected';
        }
        if ($this->divisional_status === 'rejected') {
            return 'divisional_rejected';
        }
        if ($this->ict_director_status === 'rejected') {
            return 'ict_director_rejected';
        }
        if ($this->head_it_status === 'rejected') {
            return 'head_it_rejected';
        }
        if ($this->ict_officer_status === 'rejected') {
            return 'ict_officer_rejected';
        }
        
        // Check completion status
        if ($this->ict_officer_status === 'implemented') {
            return 'implemented';
        }
        
        // Check progress through approval stages
        if ($this->head_it_status === 'approved') {
            return 'head_it_approved'; // Pending ICT Officer
        }
        if ($this->ict_director_status === 'approved') {
            return 'ict_director_approved'; // Pending Head IT
        }
        if ($this->divisional_status === 'approved') {
            return 'divisional_approved'; // Pending ICT Director
        }
        if ($this->hod_status === 'approved') {
            return 'hod_approved'; // Pending Divisional Director
        }
        
        // Default to pending if no approvals yet
        return 'pending';
    }
    
    /**
     * Get the status for display purposes (backwards compatibility)
     */
    public function getStatusAttribute(): string
    {
        // Status column no longer exists, always calculate dynamically
        return $this->getCalculatedStatus();
    }
    
    /**
     * Check if request is in a specific calculated status
     */
    public function isInStatus(string $status): bool
    {
        return $this->getCalculatedStatus() === $status;
    }
    
    /**
     * Update the main status field based on the current workflow stage
     * @deprecated Status column no longer exists - this method is a no-op
     */
    public function updateMainStatus(): void
    {
        // This method is deprecated - status column no longer exists
        // Status is now calculated dynamically via getCalculatedStatus()
        // This method is kept for backwards compatibility but does nothing
    }
    
    /**
     * Check if a specific stage can be acted upon (not locked)
     */
    public function canStageBeActedUpon(string $stage): bool
    {
        $progress = $this->getDetailedApprovalProgress();
        
        if (!isset($progress[$stage])) {
            return false;
        }
        
        return in_array($progress[$stage]['status'], ['pending', 'completed']);
    }
    
    /**
     * Get the next pending stage that needs action
     */
    public function getNextPendingStage(): ?string
    {
        $progress = $this->getDetailedApprovalProgress();
        
        foreach ($progress as $stageKey => $stage) {
            if ($stage['status'] === 'pending') {
                return $stageKey;
            }
        }
        
        return null; // All stages completed
    }
    
    /**
     * Check if all approval stages are completed
     */
    public function isFullyApproved(): bool
    {
        return $this->getCurrentWorkflowStage() === 'Implementation Complete';
    }
    
    /**
     * Get formatted access type
     */
    public function getAccessTypeNameAttribute(): string
    {
        return self::ACCESS_TYPES[$this->access_type] ?? $this->access_type;
    }

    /**
     * Check if the request can be updated/resubmitted.
     * Only pending or rejected requests can be updated.
     */
    public function canBeUpdated(): bool
    {
        $updatableStatuses = [
            'pending',
            'hod_rejected',
            'divisional_rejected',
            'ict_director_rejected',
            'head_it_rejected',
            'rejected',
            'cancelled' // Allow updating cancelled requests for resubmission
        ];
        
        return in_array($this->getCalculatedStatus(), $updatableStatuses);
    }

    /**
     * Get all modules as a combined array
     */
    public function getAllModulesAttribute(): array
    {
        $modules = [];
        
        if ($this->wellsoft_modules_selected) {
            foreach ($this->wellsoft_modules_selected as $module) {
                $modules[] = ['type' => 'Wellsoft', 'name' => $module];
            }
        }
        
        if ($this->jeeva_modules_selected) {
            foreach ($this->jeeva_modules_selected as $module) {
                $modules[] = ['type' => 'Jeeva', 'name' => $module];
            }
        }
        
        return $modules;
    }
    
    /**
     * Get selected Wellsoft modules
     */
    public function getSelectedWellsoftModules(): array
    {
        return $this->wellsoft_modules_selected ?? [];
    }
    
    /**
     * Get selected Jeeva modules
     */
    public function getSelectedJeevaModules(): array
    {
        return $this->jeeva_modules_selected ?? [];
    }
    
    /**
     * Check if request has selected Wellsoft modules
     */
    public function hasSelectedWellsoftModules(): bool
    {
        return !empty($this->wellsoft_modules_selected);
    }
    
    /**
     * Check if request has selected Jeeva modules
     */
    public function hasSelectedJeevaModules(): bool
    {
        return !empty($this->jeeva_modules_selected);
    }
    
    /**
     * Get module requested for (use/revoke)
     */
    public function getModuleRequestedForAttribute(): string
    {
        return $this->attributes['module_requested_for'] ?? 'use';
    }
    
    /**
     * Check if modules are requested for use
     */
    public function isModuleRequestForUse(): bool
    {
        return $this->module_requested_for === 'use';
    }
    
    /**
     * Check if modules are requested for revocation
     */
    public function isModuleRequestForRevoke(): bool
    {
        return $this->module_requested_for === 'revoke';
    }
    
    /**
     * Get the request ID attribute or generate one
     */
    public function getRequestIdAttribute($value)
    {
        return $value ?? 'MLG-REQ' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Robust accessor for request_type supporting JSON, CSV, or legacy formats.
     */
    public function getRequestTypeAttribute($value)
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }
        if (is_string($value)) {
            $trim = trim($value);
            // Try strict JSON first
            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded, fn($v) => $v !== null && $v !== ''));
            }
            // Normalize common legacy patterns: single quotes, escaped quotes, bracketed lists
            $normalized = $trim;
            // Replace single quotes with double quotes when it looks like a JSON-like array
            if (preg_match('/^\s*\[.*\]\s*$/', $normalized)) {
                $normalized = preg_replace("/'/", '"', $normalized);
                $decoded2 = json_decode($normalized, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded2)) {
                    return array_values(array_filter($decoded2, fn($v) => $v !== null && $v !== ''));
                }
            }
            // Fallback: comma-separated list
            $parts = array_filter(array_map(function ($p) {
                $p = trim($p);
                return trim($p, "\"' ");
            }, explode(',', $trim)));
            if (!empty($parts)) {
                return array_values($parts);
            }
            // Final fallback: single token as array
            return $trim !== '' ? [$trim] : [];
        }
        // Unknown type
        return [];
    }

    /**
     * Ensure request_type is stored as JSON array consistently.
     */
    public function setRequestTypeAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['request_type'] = json_encode(array_values(array_filter($value, fn($v) => $v !== null && $v !== '')));
            return;
        }
        if (is_string($value)) {
            $trim = trim($value);
            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['request_type'] = json_encode(array_values(array_filter($decoded, fn($v) => $v !== null && $v !== '')));
                return;
            }
            // Normalize legacy bracket list with single quotes
            if (preg_match('/^\s*\[.*\]\s*$/', $trim)) {
                $normalized = preg_replace("/'/", '"', $trim);
                $decoded2 = json_decode($normalized, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded2)) {
                    $this->attributes['request_type'] = json_encode(array_values(array_filter($decoded2, fn($v) => $v !== null && $v !== '')));
                    return;
                }
            }
            // CSV fallback
            $parts = array_filter(array_map(function ($p) {
                $p = trim($p);
                return trim($p, "\"' ");
            }, explode(',', $trim)));
            $this->attributes['request_type'] = json_encode(array_values($parts));
            return;
        }
        // Unknown type, store empty array
        $this->attributes['request_type'] = json_encode([]);
    }
    
    /**
     * Generate and set request_id after model is created
     */
    protected static function booted()
    {
        // Set request_id after creation when ID is available
        static::created(function ($userAccess) {
            // Check if request_id is missing in raw attributes
            $rawRequestId = $userAccess->getAttributes()['request_id'] ?? null;
            if (empty($rawRequestId)) {
                $generatedId = 'MLG-REQ' . str_pad($userAccess->id, 6, '0', STR_PAD_LEFT);
                $userAccess->request_id = $generatedId;
                $userAccess->saveQuietly(); // Save without triggering events
            }
        });
    }
}
