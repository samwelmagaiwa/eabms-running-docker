<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\SmsLog;
use Exception;

/**
 * Consolidated SMS Module
 * 
 * All SMS functionality in one place.
 * Handles sending SMS via KODA TECH API, logging, and approval notifications.
 */
class SmsModule
{
    protected $apiUrl;
    protected $apiKey;
    protected $secretKey;
    protected $senderId;
    protected $testMode;
    protected $enabled;
    protected $timeout;

    public function __construct()
    {
        $this->testMode = config('sms.test_mode', true);
        // Use configured Kilakona endpoint; no hardcoding of provider URLs
        $this->apiUrl = config('sms.api_url');
        $this->apiKey = config('sms.api_key');
        // Prefer api_secret but keep secret_key for backward compatibility
        $this->secretKey = config('sms.api_secret', config('sms.secret_key'));
        $this->senderId = config('sms.sender_id');
        $this->enabled = config('sms.enabled', false);
        $this->timeout = config('sms.timeout', 30);
        $this->verifySsl = config('sms.verify_ssl', false);
        $this->messageType = config('sms.message_type', 'text');
        $this->deliveryReportUrl = config('sms.delivery_report_url');
    }

    // ==================== CORE SENDING FUNCTIONS ====================

    /**
     * Send SMS to a single recipient
     * 
     * @param string $phoneNumber
     * @param string $message
     * @param string $type
     * @param int|null $referenceId - ID of the related model (e.g., UserAccess, BookingService)
     * @param string|null $referenceType - Class name of the related model
     */
    public function sendSms(string $phoneNumber, string $message, string $type = 'notification', ?int $referenceId = null, ?string $referenceType = null): array
    {
        try {
            // In test mode, simulate success but indicate it wasn't actually sent
            if ($this->testMode) {
                Log::info('SMS TEST MODE: would send SMS', [
                    'phone' => $phoneNumber,
                    'message_preview' => substr($message, 0, 50),
                    'type' => $type,
                    'reference_id' => $referenceId,
                    'reference_type' => $referenceType
                ]);
                // Still log a synthetic SMS entry for traceability
                $this->logSms($this->formatPhoneNumber($phoneNumber), $message, $type, true, [
                    'success' => true,
                    'message' => 'SMS sent (test mode)',
                    'test_mode' => true
                ], $referenceId, $referenceType);
                // Return success but with 'actually_sent' = false so caller knows to use appropriate status
                return $this->buildResponse(true, 'SMS sent successfully (test mode)', [
                    'test_mode' => true,
                    'actually_sent' => false,
                    'status_to_use' => 'test_mode'
                ]);
            }

            // If service is disabled, indicate it wasn't sent
            if (!$this->enabled) {
                Log::info('SMS disabled - skipping send', [
                    'phone' => $phoneNumber,
                    'message_preview' => substr($message, 0, 50),
                    'type' => $type,
                    'reference_id' => $referenceId,
                    'reference_type' => $referenceType
                ]);
                // Log as informational without error state
                $this->logSms($this->formatPhoneNumber($phoneNumber), $message, $type, true, [
                    'success' => true,
                    'message' => 'SMS skipped (service disabled)',
                    'disabled' => true
                ], $referenceId, $referenceType);
                // Return success but with 'actually_sent' = false so caller knows to use appropriate status
                return $this->buildResponse(true, 'SMS skipped (service disabled)', [
                    'disabled' => true,
                    'actually_sent' => false,
                    'status_to_use' => 'disabled'
                ]);
            }

            // Format and validate phone number
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                return $this->buildResponse(false, 'Invalid phone number format');
            }

            // Check rate limiting
            if ($this->isRateLimited($phoneNumber)) {
                return $this->buildResponse(false, 'Rate limit exceeded');
            }

            // Send via API
            $response = $this->sendViaApi($phoneNumber, $message);

            // Log the attempt with reference info for delivery report tracking
            $this->logSms($phoneNumber, $message, $type, $response['success'], $response, $referenceId, $referenceType);

            return $response;

        } catch (Exception $e) {
            Log::error('SMS Module Error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return $this->buildResponse(false, 'SMS error: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS to multiple recipients
     */
    public function sendBulkSms(array $recipients, string $message, string $type = 'bulk'): array
    {
        $results = ['total' => count($recipients), 'sent' => 0, 'failed' => 0, 'details' => []];

        foreach ($recipients as $recipient) {
            $phoneNumber = $this->extractPhoneNumber($recipient);
            
            if (!$phoneNumber) {
                $results['failed']++;
                continue;
            }

            $result = $this->sendSms($phoneNumber, $message, $type);
            
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'phone' => $phoneNumber,
                'status' => $result['success'] ? 'sent' : 'failed',
                'message' => $result['message']
            ];

            // Small delay to avoid overwhelming API
            usleep(100000); // 0.1 second
        }

        return $results;
    }

    // ==================== APPROVAL NOTIFICATIONS ====================

    /**
     * Send notification when a request is approved
     * 
     * @param mixed $request - The access request object
     * @param User $approver - Who just approved it
     * @param string $approvalLevel - 'hod', 'divisional', 'ict_director', 'head_it'
     * @param User|null $nextApprover - Next person who needs to approve (if any)
     */
    public function notifyRequestApproved($request, User $approver, string $approvalLevel, ?User $nextApprover = null): array
    {
        $results = ['requester_notified' => false, 'next_approver_notified' => false];

        try {
            // 1. Notify the requester
            $results['requester_notified'] = $this->notifyRequester($request, $approvalLevel);
            
            // Update requester SMS status
            $this->updateRequesterSmsStatus($request, $results['requester_notified']);

            // 2. Notify next approver if exists
            if ($nextApprover && $nextApprover->phone) {
                $results['next_approver_notified'] = $this->notifyNextApprover($request, $nextApprover, $approvalLevel);
                
                // Update next approver SMS status based on their level
                $this->updateNextApproverSmsStatus($request, $approvalLevel, $results['next_approver_notified']);
            }

            Log::info('Approval SMS notifications sent', [
                'request_id' => $request->id,
                'level' => $approvalLevel,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send approval notifications', [
                'request_id' => $request->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Notify ICT Officer about task assignment
     * Also notifies the staff that their request is being processed
     */
    public function notifyIctOfficerAssignment($request, User $ictOfficer, User $assignedBy): array
    {
        $results = ['ict_officer_notified' => false, 'requester_notified' => false];

        try {
            // 1. Notify ICT Officer about assignment
            if ($ictOfficer->phone) {
                $requesterName = $request->staff_name ?? $request->full_name ?? 'Staff';
                $department = 'N/A';
                if ($request->department && is_object($request->department)) {
                    $department = $request->department->name;
                }
                
                $types = $this->getRequestTypes($request);
                $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);
                
                // Handle grammar: if types is 'Access', don't add 'access' again
                $accessText = ($types === 'Access') ? $types : "{$types} access";
                $message = "NEW TASK ASSIGNMENT: You have been assigned to implement {$accessText} for {$requesterName} ({$department}). Ref: {$ref}. Please login to system to start implementation. - EABMS";
                
                // Send SMS with reference for delivery tracking
                $result = $this->sendSms($ictOfficer->phone, $message, 'ict_officer_assignment', $request->id, get_class($request));
                $results['ict_officer_notified'] = $result['success'];
                
                // Update SMS status tracking for ICT Officer
                try {
                    $request->update([
                        'sms_sent_to_ict_officer_at' => $results['ict_officer_notified'] ? now() : null,
                        'sms_to_ict_officer_status' => $results['ict_officer_notified'] ? 'sent' : 'failed'
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to update ICT Officer SMS status', [
                        'request_id' => $request->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::warning('No phone number for ICT Officer', [
                    'officer_id' => $ictOfficer->id,
                    'officer_name' => $ictOfficer->name
                ]);
            }

            // 2. Notify the staff (requester) that their request is being processed
            $results['requester_notified'] = $this->notifyRequesterAssigned($request, $ictOfficer);

            Log::info('ICT Officer assignment SMS notifications sent', [
                'request_id' => $request->id,
                'ict_officer_id' => $ictOfficer->id,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send ICT Officer assignment notification', [
                'request_id' => $request->id ?? null,
                'ict_officer_id' => $ictOfficer->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Notify requester that their request has been assigned to an ICT Officer for implementation
     */
    protected function notifyRequesterAssigned($request, User $ictOfficer): bool
    {
        // Get phone number
        $phone = null;
        
        if (!empty($request->phone)) {
            $phone = $request->phone;
        } elseif (!empty($request->phone_number)) {
            $phone = $request->phone_number;
        } elseif (isset($request->user) && !empty($request->user->phone)) {
            $phone = $request->user->phone;
        }
        
        if (!$phone) {
            Log::warning('No phone number for requester (assignment notification)', [
                'request_id' => $request->id,
                'user_id' => $request->user_id ?? null
            ]);
            return false;
        }

        // Get requester name
        $name = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'User';

        // Get request types
        $types = $this->getRequestTypes($request);

        // Get reference
        $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

        // Build message
        $message = "Dear {$name}, your {$types} request (Ref: {$ref}) has been FULLY APPROVED and assigned to ICT for implementation. You will be notified once completed. - EABMS";

        // Send SMS with reference for delivery tracking
        $result = $this->sendSms($phone, $message, 'assignment_notification', $request->id, get_class($request));
        return $result['success'];
    }

    /**
     * Notify requester when their request is rejected
     * 
     * @param mixed $request - The access request object
     * @param User $rejectedBy - Who rejected the request
     * @param string $approvalLevel - 'hod', 'divisional', 'ict_director', 'head_it'
     * @param string|null $reason - Reason for rejection
     */
    public function notifyRequestRejected($request, User $rejectedBy, string $approvalLevel, ?string $reason = null): array
    {
        $results = ['requester_notified' => false];

        try {
            // Get requester phone number
            $phone = null;
            
            if (!empty($request->phone)) {
                $phone = $request->phone;
            } elseif (!empty($request->phone_number)) {
                $phone = $request->phone_number;
            } elseif (isset($request->user) && !empty($request->user->phone)) {
                $phone = $request->user->phone;
            }
            
            if (!$phone) {
                Log::warning('No phone number for requester (rejection notification)', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id ?? null
                ]);
                return $results;
            }

            // Get requester name
            $name = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'User';

            // Get request types
            $types = $this->getRequestTypes($request);

            // Get reference
            $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

            // Get approver level name
            $levelName = $this->getApprovalLevelName($approvalLevel);

            // Build message
            $message = "Dear {$name}, your {$types} request (Ref: {$ref}) has been REJECTED by {$levelName}.";
            
            if ($reason && trim($reason) !== '') {
                $shortReason = strlen($reason) > 50 ? substr($reason, 0, 47) . '...' : $reason;
                $message .= " Reason: {$shortReason}";
            }
            
            $message .= " Please contact your HOD for guidance. - EABMS";

            // Send SMS with reference for delivery tracking
            $result = $this->sendSms($phone, $message, 'rejection', $request->id, get_class($request));
            $results['requester_notified'] = (bool) ($result['success'] ?? false);

            // Update SMS status tracking
            $this->updateRequesterSmsStatus($request, $results['requester_notified']);

            Log::info('Request rejection SMS notification sent', [
                'request_id' => $request->id,
                'rejected_by' => $rejectedBy->id,
                'level' => $approvalLevel,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send rejection notification', [
                'request_id' => $request->id ?? null,
                'rejected_by' => $rejectedBy->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Notify requester when their request is cancelled
     * 
     * @param mixed $request - The access request object
     * @param User $cancelledBy - Who cancelled the request
     * @param string $reason - Reason for cancellation
     */
    public function notifyRequestCancelled($request, User $cancelledBy, string $reason): array
    {
        $results = ['requester_notified' => false];

        try {
            // Get requester phone number
            $phone = null;
            
            if (!empty($request->phone)) {
                $phone = $request->phone;
            } elseif (!empty($request->phone_number)) {
                $phone = $request->phone_number;
            } elseif (isset($request->user) && !empty($request->user->phone)) {
                $phone = $request->user->phone;
            }
            
            if (!$phone) {
                Log::warning('No phone number for requester (cancellation notification)', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id ?? null
                ]);
                return $results;
            }

            // Get requester name
            $name = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'User';

            // Get request types
            $types = $this->getRequestTypes($request);

            // Get reference
            $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

            // Get canceller's role for display
            $cancellerRole = $this->getUserRoleDisplay($cancelledBy);

            // Build message - keep it concise for SMS
            $shortReason = strlen($reason) > 50 ? substr($reason, 0, 47) . '...' : $reason;
            $message = "Dear {$name}, your {$types} request (Ref: {$ref}) has been CANCELLED by {$cancellerRole}. Reason: {$shortReason} - EABMS";

            // Send SMS with reference for delivery tracking
            $result = $this->sendSms($phone, $message, 'cancellation', $request->id, get_class($request));
            $results['requester_notified'] = (bool) ($result['success'] ?? false);

            // Update SMS status tracking
            $this->updateRequesterSmsStatus($request, $results['requester_notified']);

            Log::info('Request cancellation SMS notification sent', [
                'request_id' => $request->id,
                'cancelled_by' => $cancelledBy->id,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send cancellation notification', [
                'request_id' => $request->id ?? null,
                'cancelled_by' => $cancelledBy->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Get user's role for display in SMS
     */
    protected function getUserRoleDisplay(User $user): string
    {
        // Try to get from roles relationship
        if ($user->relationLoaded('roles') && $user->roles->isNotEmpty()) {
            $roleName = $user->roles->first()->name;
            return $this->formatRoleName($roleName);
        }
        
        // Try to load roles
        try {
            $user->load('roles');
            if ($user->roles->isNotEmpty()) {
                $roleName = $user->roles->first()->name;
                return $this->formatRoleName($roleName);
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return $user->name;
    }

    /**
     * Format role name for display
     */
    protected function formatRoleName(string $role): string
    {
        $roleMap = [
            'admin' => 'Admin',
            'head_of_department' => 'HOD',
            'divisional_director' => 'Divisional Director',
            'ict_director' => 'ICT Director',
            'head_of_it' => 'Head of IT',
            'ict_officer' => 'ICT Officer',
            'staff' => 'Staff',
            'secretary_ict' => 'ICT Secretary'
        ];
        
        return $roleMap[$role] ?? ucwords(str_replace('_', ' ', $role));
    }

    /**
     * Notify requester when access is granted (implementation completed)
     * 
     * @param mixed $request - The access request object
     * @param User $ictOfficer - ICT Officer who granted access
     * @param string|null $comments - Implementation notes from ICT Officer
     */
    public function notifyAccessGranted($request, User $ictOfficer, ?string $comments = null): array
    {
        $results = ['requester_notified' => false];

        try {
            // Get requester phone number
            $phone = null;
            $phoneSource = 'unknown';
            
            // Try to get from request fields first
            if (!empty($request->phone)) {
                $phone = $request->phone;
                $phoneSource = 'request.phone';
            } elseif (!empty($request->phone_number)) {
                $phone = $request->phone_number;
                $phoneSource = 'request.phone_number';
            } elseif (isset($request->user) && !empty($request->user->phone)) {
                $phone = $request->user->phone;
                $phoneSource = 'users.phone (via relationship)';
            }
            
            if (!$phone) {
                Log::warning('No phone number for requester (access granted notification)', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id ?? null
                ]);
                return $results;
            }
            
            Log::info('Access granted SMS - phone number resolved', [
                'request_id' => $request->id,
                'phone_source' => $phoneSource,
                'phone_masked' => substr($phone, 0, 6) . '***'
            ]);

            // Get requester name
            $name = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'User';

            // Get request types
            $types = $this->getRequestTypes($request);

            // Get reference
            $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

            // Build message (previous template - no forced 160 char cap)
            // Avoid "Access access" when getRequestTypes() falls back to "Access".
            $accessText = (strtolower(trim((string) $types)) === 'access') ? 'access request' : (trim((string) $types) . ' access');
            $message = "Dear {$name}, your {$accessText} has been GRANTED and is now ACTIVE. Ref: {$ref}.";

            if ($comments && trim($comments) !== '') {
                $message .= " Note: " . trim($comments);
            }

            $message .= " - EABMS";

            // Send SMS with reference for delivery tracking
            $result = $this->sendSms($phone, $message, 'access_granted', $request->id, get_class($request));
            $results['requester_notified'] = (bool) ($result['success'] ?? false);

            // IMPORTANT: log the concrete failure reason (otherwise we only see requester_notified=false)
            if (!$results['requester_notified']) {
                Log::warning('Access granted SMS failed', [
                    'request_id' => $request->id,
                    'ict_officer_id' => $ictOfficer->id,
                    'phone_source' => $phoneSource,
                    'phone_masked' => substr((string) $phone, 0, 6) . '***',
                    'reason' => $result['message'] ?? 'Unknown failure',
                    // Provider payload may be helpful for debugging but can be large; keep it as-is
                    'provider_response' => $result['data'] ?? null,
                ]);
            }

            // Update SMS status tracking
            try {
                $request->update([
                    'sms_sent_to_requester_at' => $results['requester_notified'] ? now() : null,
                    'sms_to_requester_status' => $results['requester_notified'] ? 'sent' : 'failed'
                ]);
            } catch (Exception $e) {
                Log::error('Failed to update requester SMS status (access granted)', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Access granted SMS notification processed', [
                'request_id' => $request->id,
                'ict_officer_id' => $ictOfficer->id,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send access granted notification', [
                'request_id' => $request->id ?? null,
                'ict_officer_id' => $ictOfficer->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Notify requester about approval
     */
    protected function notifyRequester($request, string $approvalLevel): bool
    {
        // Get phone number - prioritize from user relationship (users table)
        $phone = null;
        $phoneSource = 'unknown';
        
        // Try to get from request fields first
        if (!empty($request->phone)) {
            $phone = $request->phone;
            $phoneSource = 'request.phone';
        } elseif (!empty($request->phone_number)) {
            $phone = $request->phone_number;
            $phoneSource = 'request.phone_number';
        } elseif (isset($request->user) && !empty($request->user->phone)) {
            // Get from users table via relationship
            $phone = $request->user->phone;
            $phoneSource = 'users.phone (via relationship)';
        }
        
        if (!$phone) {
            Log::warning('No phone number for requester', [
                'request_id' => $request->id,
                'user_id' => $request->user_id ?? null,
                'checked_sources' => ['request.phone', 'request.phone_number', 'user.phone']
            ]);
            return false;
        }
        
        Log::info('SMS phone number resolved', [
            'request_id' => $request->id,
            'phone_source' => $phoneSource,
            'phone_masked' => substr($phone, 0, 6) . '***'
        ]);

        // Get requester name
        $name = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'User';

        // Get request types
        $types = $this->getRequestTypes($request);

        // Get reference
        $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

        // Build message
        $levelName = $this->getApprovalLevelName($approvalLevel);
        $message = "Dear {$name}, your {$types} request has been APPROVED by {$levelName}. Reference: {$ref}. You will be notified on next steps. - EABMS";

        // Send SMS with reference for delivery tracking
        $result = $this->sendSms($phone, $message, 'approval', $request->id, get_class($request));
        return $result['success'];
    }

    /**
     * Notify next approver about pending request
     */
    protected function notifyNextApprover($request, User $nextApprover, string $currentLevel): bool
    {
        // Get phone from users table
        if (!$nextApprover->phone) {
            Log::warning('No phone number for next approver', [
                'approver_id' => $nextApprover->id,
                'approver_name' => $nextApprover->name,
                'approver_role' => $nextApprover->roles->pluck('name')->toArray() ?? []
            ]);
            return false;
        }
        
        Log::info('Next approver phone number found', [
            'approver_id' => $nextApprover->id,
            'approver_name' => $nextApprover->name,
            'phone_source' => 'users.phone',
            'phone_masked' => substr($nextApprover->phone, 0, 6) . '***'
        ]);

        // Get requester info
        $requesterName = $request->staff_name ?? $request->full_name ?? $request->user->name ?? 'Staff';
        
        // Get department name (not the whole object)
        $department = 'N/A';
        if ($request->department && is_object($request->department)) {
            $department = $request->department->name;
        } elseif (isset($request->user->department->name)) {
            $department = $request->user->department->name;
        }

        // Get request types
        $types = $this->getRequestTypes($request);

        // Get reference
        $ref = $request->request_id ?? 'MLG-REQ' . str_pad($request->id, 6, '0', STR_PAD_LEFT);

        // Build message
        $message = "PENDING APPROVAL: {$types} request from {$requesterName} ({$department}) requires your review. Ref: {$ref}. Please check the system. - EABMS";

        // Send SMS with reference for delivery tracking
        $result = $this->sendSms($nextApprover->phone, $message, 'approval_notification', $request->id, get_class($request));
        return $result['success'];
    }

    // ==================== API COMMUNICATION ====================

    /**
     * Send SMS via KODA TECH API using cURL
     */
    protected function sendViaApi(string $phoneNumber, string $message): array
    {
        try {
            // Test mode bypass
            if ($this->testMode && !$this->enabled) {
                Log::info('SMS TEST MODE (disabled)', ['phone' => $phoneNumber]);
                return $this->buildResponse(true, 'SMS sent (test mode - disabled)', ['test_mode' => true]);
            }

            // Prepare Kilakona payload
            // Note: Kilakona API requires contacts as a STRING (not array)
            $payload = [
                'senderId' => $this->senderId,
                'messageType' => $this->messageType,
                'message' => $message,
                'contacts' => $phoneNumber, // String format required by Kilakona
            ];
            if (!empty($this->deliveryReportUrl)) {
                $payload['deliveryReportUrl'] = $this->deliveryReportUrl;
            }

            // Log the full request for debugging
            Log::info('SMS API Request', [
                'api_url' => $this->apiUrl,
                'phone' => substr($phoneNumber, 0, 6) . '***' . substr($phoneNumber, -2),
                'message_length' => strlen($message),
                'sender_id' => $this->senderId,
                'has_delivery_url' => !empty($this->deliveryReportUrl),
            ]);

            // Initialize cURL
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api_key: ' . $this->apiKey,
                'api_secret: ' . $this->secretKey,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            // SSL verification per config
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl ? 1 : 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);

            // Execute
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // Handle cURL errors
            if ($curlError) {
                Log::error('SMS cURL Error', [
                    'error' => $curlError,
                    'curl_errno' => $curlErrno,
                    'api_url' => $this->apiUrl,
                    'phone' => substr($phoneNumber, 0, 6) . '***',
                    'timeout' => $this->timeout,
                    'verify_ssl' => $this->verifySsl,
                    'sender_id' => $this->senderId
                ]);
                return $this->buildResponse(false, 'Connection error: ' . $curlError);
            }

            // Handle HTTP errors (accept any 2xx)
            if ($httpCode < 200 || $httpCode >= 300) {
                Log::error('SMS HTTP Error', ['code' => $httpCode, 'response' => $response]);
                return $this->buildResponse(false, 'HTTP Error: ' . $httpCode);
            }

            // Log full response for debugging
            Log::info('SMS API Response', [
                'http_code' => $httpCode,
                'response_raw' => substr($response, 0, 500), // First 500 chars
                'phone' => substr($phoneNumber, 0, 6) . '***',
            ]);

            // Decode JSON
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('SMS JSON Error', ['error' => json_last_error_msg(), 'response' => $response]);
                return $this->buildResponse(false, 'Invalid JSON response');
            }

            // Kilakona success detection
            // We must NOT treat "any JSON" as success because the provider may return
            // a 2xx response with success=false or with 0 valid recipients.
            if (!is_array($data)) {
                return $this->buildResponse(false, 'Unexpected API response', $data);
            }

            // Provider-reported success flag (observed: { code, success, message, data:{validContacts,...} })
            $providerSuccess = (isset($data['success']) && $data['success'] === true);

            // If provider returns explicit error fields
            if (isset($data['error']) || isset($data['errors'])) {
                $msg = isset($data['error']) ? (is_array($data['error']) ? json_encode($data['error']) : (string) $data['error']) : json_encode($data['errors']);
                return $this->buildResponse(false, 'API Error: ' . $msg, $data);
            }

            // If provider has a nested data block with valid/invalid contacts, ensure at least one valid recipient
            $validCount = null;
            if (isset($data['data']) && is_array($data['data']) && array_key_exists('validContacts', $data['data'])) {
                $valid = $data['data']['validContacts'];
                $validCount = is_array($valid) ? count($valid) : (is_numeric($valid) ? (int) $valid : null);
            }

            if ($providerSuccess && ($validCount === null || $validCount > 0)) {
                Log::debug('SMS submitted successfully via Kilakona', [
                    'code' => $data['code'] ?? null,
                    'message' => $data['message'] ?? null,
                    'valid_contacts' => $validCount,
                ]);
                // Include 'actually_sent' => true to indicate SMS was really sent to provider
                $data['actually_sent'] = true;
                $data['status_to_use'] = 'sent';
                return $this->buildResponse(true, 'SMS sent successfully', $data);
            }

            // Provider responded but indicates failure or no valid recipients
            $failureReason = $data['message'] ?? 'SMS submission failed';
            if ($validCount === 0) {
                $failureReason = 'No valid recipient contacts';
            }

            Log::warning('SMS provider responded with failure', [
                'code' => $data['code'] ?? null,
                'success' => $data['success'] ?? null,
                'message' => $data['message'] ?? null,
                'valid_contacts' => $validCount,
            ]);

            return $this->buildResponse(false, $failureReason, $data);

        } catch (Exception $e) {
            Log::error('SMS API Exception', ['error' => $e->getMessage()]);
            return $this->buildResponse(false, 'Exception: ' . $e->getMessage());
        }
    }

    // ==================== HELPER FUNCTIONS ====================

    /**
     * Format phone number to international format
     * Automatically adds Tanzania country code +255 if missing
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters (spaces, +, -, etc.)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle different formats and auto-add country code
        
        // Already has country code 255 (e.g., 255712345678)
        if (strlen($phoneNumber) === 12 && substr($phoneNumber, 0, 3) === '255') {
            return $phoneNumber;
        }
        
        // 9 digits starting with 7 (e.g., 712345678)
        if (strlen($phoneNumber) === 9 && substr($phoneNumber, 0, 1) === '7') {
            return '255' . $phoneNumber;
        }
        
        // 10 digits starting with 07 (e.g., 0712345678)
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 2) === '07') {
            return '255' . substr($phoneNumber, 1);
        }
        
        // 9 digits starting with 6 (e.g., 612345678) - Vodacom
        if (strlen($phoneNumber) === 9 && substr($phoneNumber, 0, 1) === '6') {
            return '255' . $phoneNumber;
        }
        
        // 10 digits starting with 06 (e.g., 0612345678) - Vodacom
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 2) === '06') {
            return '255' . substr($phoneNumber, 1);
        }
        
        // 9 digits starting with 8 (e.g., 812345678) - TTCL
        if (strlen($phoneNumber) === 9 && substr($phoneNumber, 0, 1) === '8') {
            return '255' . $phoneNumber;
        }
        
        // 10 digits starting with 08 (e.g., 0812345678) - TTCL
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 2) === '08') {
            return '255' . substr($phoneNumber, 1);
        }
        
        // 9 digits starting with 9 (e.g., 912345678) - Smile, other
        if (strlen($phoneNumber) === 9 && substr($phoneNumber, 0, 1) === '9') {
            return '255' . $phoneNumber;
        }
        
        // 10 digits starting with 09 (e.g., 0912345678) - Smile, other
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 2) === '09') {
            return '255' . substr($phoneNumber, 1);
        }
        
        // If doesn't match any pattern, return as-is
        return $phoneNumber;
    }

    /**
     * Validate Tanzanian phone number
     * Supports all Tanzanian mobile prefixes:
     * - 2556x (Vodacom, Airtel)
     * - 2557x (Tigo, Halotel, TTCL)
     * - 2558x (TTCL additional)
     * - 2559x (Smile, other operators)
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Accept Tanzanian numbers: 255 + (6|7|8|9) + 8 digits = 12 total digits
        return preg_match('/^255[6-9][0-9]{8}$/', $phoneNumber);
    }

    /**
     * Check rate limiting (5 SMS per hour per number)
     */
    protected function isRateLimited(string $phoneNumber): bool
    {
        $key = 'sms_rate_limit:' . $phoneNumber;
        $attempts = Cache::get($key, 0);
        
        $maxAttempts = config('sms.rate_limit.max_per_hour_per_number', 5);
        
        if ($attempts >= $maxAttempts) {
            return true;
        }

        Cache::put($key, $attempts + 1, now()->addHour());
        return false;
    }

    /**
     * Extract phone number from various formats
     */
    protected function extractPhoneNumber($recipient): ?string
    {
        if (is_string($recipient)) {
            return $recipient;
        }
        
        if ($recipient instanceof User) {
            return $recipient->phone;
        }
        
        if (is_array($recipient) && isset($recipient['phone'])) {
            return $recipient['phone'];
        }
        
        return null;
    }

    /**
     * Get request types as string
     */
    protected function getRequestTypes($request): string
    {
        $types = [];
        
        // Check various field names for Jeeva access
        if (!empty($request->jeeva_modules_selected) || 
            ($request->jeeva_access ?? false)) {
            $types[] = 'Jeeva';
        }
        
        // Check various field names for Wellsoft access
        if (!empty($request->wellsoft_modules_selected) || 
            ($request->wellsoft_access ?? false)) {
            $types[] = 'Wellsoft';
        }
        
        // Check various field names for Internet access
        if (!empty($request->internet_purposes) || 
            ($request->internet_access ?? false)) {
            $types[] = 'Internet';
        }
        
        return implode(' & ', $types) ?: 'Access';
    }

    /**
     * Get human-readable approval level name
     */
    protected function getApprovalLevelName(string $level): string
    {
        $levels = [
            'hod' => 'HOD',
            'divisional' => 'Divisional Director',
            'ict_director' => 'ICT Director',
            'head_it' => 'Head of IT'
        ];
        return $levels[$level] ?? ucfirst($level);
    }

    /**
     * Log SMS attempt to database
     * 
     * @param string $phoneNumber
     * @param string $message
     * @param string $type
     * @param bool $success
     * @param array $response
     * @param int|null $referenceId - ID of the related model for delivery report tracking
     * @param string|null $referenceType - Class name of the related model
     */
    protected function logSms(string $phoneNumber, string $message, string $type, bool $success, array $response, ?int $referenceId = null, ?string $referenceType = null): void
    {
        try {
            // Store provider response as structured JSON (not JSON-encoded string)
            SmsLog::create([
                'phone_number' => $phoneNumber,
                'message' => $message,
                'type' => $type,
                'status' => $success ? 'sent' : 'failed',
                'provider_response' => $response,
                'sent_at' => $success ? now() : null,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
            
            Log::debug('SMS logged with reference', [
                'phone' => substr($phoneNumber, 0, 6) . '***',
                'type' => $type,
                'success' => $success,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log SMS', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Build standardized response
     */
    protected function buildResponse(bool $success, string $message, $data = null): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }

    // ==================== PUBLIC UTILITY FUNCTIONS ====================

    /**
     * Get SMS statistics
     */
    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = SmsLog::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $total = $query->count();
        $sent = $query->where('status', 'sent')->count();
        $failed = $query->where('status', 'failed')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0
        ];
    }

    /**
     * Test SMS configuration
     */
    public function testConfiguration(string $testPhone): array
    {
        $message = "SMS Service Test - " . now()->format('Y-m-d H:i:s') . " - MNH IT System";
        return $this->sendSms($testPhone, $message, 'test');
    }

    /**
     * Check if SMS service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if in test mode
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    // ==================== SMS STATUS TRACKING ====================

    /**
     * Update requester SMS notification status
     */
    protected function updateRequesterSmsStatus($request, bool $success): void
    {
        try {
            $request->update([
                'sms_sent_to_requester_at' => $success ? now() : null,
                'sms_to_requester_status' => $success ? 'sent' : 'failed'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update requester SMS status', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update next approver SMS notification status based on approval level
     */
    protected function updateNextApproverSmsStatus($request, string $approvalLevel, bool $success): void
    {
        try {
            $statusField = null;
            $timestampField = null;

            // Determine which fields to update based on current approval level
            // The SMS is being sent TO the next level
            switch ($approvalLevel) {
                case 'hod':
                    // HOD just approved, so SMS is sent to Divisional
                    $timestampField = 'sms_sent_to_divisional_at';
                    $statusField = 'sms_to_divisional_status';
                    break;
                case 'divisional':
                    // Divisional just approved, so SMS is sent to ICT Director
                    $timestampField = 'sms_sent_to_ict_director_at';
                    $statusField = 'sms_to_ict_director_status';
                    break;
                case 'ict_director':
                    // ICT Director just approved, so SMS is sent to Head of IT
                    $timestampField = 'sms_sent_to_head_it_at';
                    $statusField = 'sms_to_head_it_status';
                    break;
                case 'head_it':
                    // Head of IT just approved, so SMS is sent to ICT Officer
                    $timestampField = 'sms_sent_to_ict_officer_at';
                    $statusField = 'sms_to_ict_officer_status';
                    break;
                case 'ict_officer':
                    // ICT Officer is final - no next approver
                    return;
            }

            if ($statusField && $timestampField) {
                $request->update([
                    $timestampField => $success ? now() : null,
                    $statusField => $success ? 'sent' : 'failed'
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update next approver SMS status', [
                'request_id' => $request->id,
                'approval_level' => $approvalLevel,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get SMS notification status for a request
     */
    public function getSmsNotificationStatus($request): array
    {
        return [
            'hod' => [
                'status' => $request->sms_to_hod_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_hod_at,
                'sent' => $request->sms_to_hod_status === 'sent'
            ],
            'divisional' => [
                'status' => $request->sms_to_divisional_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_divisional_at,
                'sent' => $request->sms_to_divisional_status === 'sent'
            ],
            'ict_director' => [
                'status' => $request->sms_to_ict_director_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_ict_director_at,
                'sent' => $request->sms_to_ict_director_status === 'sent'
            ],
            'head_it' => [
                'status' => $request->sms_to_head_it_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_head_it_at,
                'sent' => $request->sms_to_head_it_status === 'sent'
            ],
            'ict_officer' => [
                'status' => $request->sms_to_ict_officer_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_ict_officer_at,
                'sent' => $request->sms_to_ict_officer_status === 'sent'
            ],
            'requester' => [
                'status' => $request->sms_to_requester_status ?? 'pending',
                'sent_at' => $request->sms_sent_to_requester_at,
                'sent' => $request->sms_to_requester_status === 'sent'
            ]
        ];
    }
}
