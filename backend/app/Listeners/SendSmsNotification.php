<?php

namespace App\Listeners;

use App\Events\ApprovalStatusChanged;
use App\Events\ApprovalRequestSubmitted;
use App\Services\SmsModule;
use Illuminate\Support\Facades\Log;

/**
 * SMS Notification Listener
 * 
 * This listener handles SMS notifications for approval requests.
 * It runs synchronously to ensure SMS is sent immediately without
 * requiring a queue worker to be running.
 */
class SendSmsNotification
{
    protected $sms;

    /**
     * Create the event listener.
     *
     * @param SmsModule $sms
     */
    public function __construct(SmsModule $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Handle the event.
     *
     * @param mixed $event
     * @return void
     */
    public function handle($event)
    {
        try {
            if ($event instanceof ApprovalRequestSubmitted) {
                $this->handleApprovalRequestSubmitted($event);
            } elseif ($event instanceof ApprovalStatusChanged) {
                $this->handleApprovalStatusChanged($event);
            }
        } catch (\Exception $e) {
            Log::error('SMS Notification Listener Error: ' . $e->getMessage(), [
                'event' => get_class($event),
                'event_data' => method_exists($event, 'toArray') ? $event->toArray() : []
            ]);

            // Re-throw to ensure the job fails and can be retried
            throw $e;
        }
    }

    /**
     * Handle approval request submitted event
     *
     * @param ApprovalRequestSubmitted $event
     * @return void
     */
    protected function handleApprovalRequestSubmitted(ApprovalRequestSubmitted $event)
    {
        Log::info('Processing SMS notification for approval request submitted', [
            'request_id' => $event->request->id,
            'request_type' => $event->requestType,
            'user_id' => $event->user->id
        ]);

        $reference = $event->request->reference ?? $event->request->id;

        // Notify the requester that their request has been submitted
        if ($event->user && $event->user->phone) {
            $message = $this->buildApprovalMessage($event->user->name ?? 'User', $event->requestType, 'pending', [
                'reference' => $reference,
                'reason' => null
            ]);
            $result = $this->sms->sendSms($event->user->phone, $message, 'approval');
            
            // Update requester SMS status
            if (method_exists($event->request, 'update')) {
                $event->request->update([
                    'sms_sent_to_requester_at' => $result['success'] ? now() : null,
                    'sms_to_requester_status' => $result['success'] ? 'sent' : 'failed'
                ]);
            }
        } else {
            Log::warning('Requester has no phone number for submission SMS', [
                'user_id' => $event->user->id ?? null
            ]);
        }

        // Notify approvers about the new request
        if (!empty($event->approvers)) {
            $approverMessage = $this->buildApproverNotificationMessage(
                $event->requestType,
                $event->user->name ?? 'User',
                $event->user->department->name ?? ($event->request->department->name ?? 'N/A'),
                $reference
            );
            $results = $this->sms->sendBulkSms($event->approvers, $approverMessage, 'approval_notification');
            
            // Update HOD/Approver SMS status for the request record
            // For initial submission, we track this in the sms_to_hod_status column
            if (method_exists($event->request, 'update')) {
                $status = ($results['sent'] > 0) ? 'sent' : 'failed';
                $event->request->update([
                    'sms_sent_to_hod_at' => ($results['sent'] > 0) ? now() : null,
                    'sms_to_hod_status' => $status
                ]);
            }
        }
    }

    /**
     * Handle approval status changed event
     *
     * @param ApprovalStatusChanged $event
     * @return void
     */
    protected function handleApprovalStatusChanged(ApprovalStatusChanged $event)
    {
        Log::info('Processing SMS notification for approval status change', [
            'request_id' => $event->request->id,
            'request_type' => $event->requestType,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'user_id' => $event->user->id
        ]);

        $reference = $event->request->reference ?? $event->request->request_id ?? $event->request->id;

        // For device booking approvals, SMS is handled directly by the BookingServiceController.
        // Skip generic listener-based SMS to avoid duplicate and conflicting messages.
        if ($event->requestType === 'device_booking') {
            Log::info('Skipping generic ApprovalStatusChanged SMS for device_booking; handled by booking module.', [
                'request_id' => $event->request->id,
                'user_id' => $event->user->id,
            ]);
            return;
        }

        // Normalize status keys for SMS templates (non-device bookings)
        $statusKey = $event->newStatus;

        // Notify the requester about the status change
        if ($event->user && $event->user->phone) {
            $message = $this->buildApprovalMessage(
                $event->user->name ?? 'User',
                $event->requestType,
                $statusKey,
                [
                    'reference' => $reference,
                    'reason' => $event->reason ?? 'Not specified'
                ]
            );

            $this->sms->sendSms($event->user->phone, $message, 'approval');
        } else {
            Log::warning('Requester has no phone number for status change SMS', [
                'user_id' => $event->user->id ?? null
            ]);
        }

        // Notify next approvers when request progresses through the workflow
        // Check for ANY approval progression status, not just 'approved'
        $isApprovalProgressing = $this->isApprovalProgressionStatus($statusKey);
        
        if ($isApprovalProgressing && !empty($event->additionalNotifyUsers)) {
            $nextApproverLevel = $this->getNextApproverLevelFromStatus($statusKey);
            $message = $this->buildNextApproverNotificationMessage(
                $event->user,
                $event->requestType,
                $reference,
                $nextApproverLevel
            );

            $results = $this->sms->sendBulkSms(
                $event->additionalNotifyUsers,
                $message,
                'approval_notification'
            );
            
            Log::info('SMS sent to next approvers', [
                'request_id' => $event->request->id,
                'new_status' => $statusKey,
                'next_level' => $nextApproverLevel,
                'recipients_count' => count($event->additionalNotifyUsers),
                'sent' => $results['sent'] ?? 0,
                'failed' => $results['failed'] ?? 0
            ]);
        }
    }
    
    /**
     * Check if the status indicates approval progression (moving to next stage)
     *
     * @param string $status
     * @return bool
     */
    protected function isApprovalProgressionStatus(string $status): bool
    {
        // Statuses that indicate request is progressing to next approval stage
        $progressionStatuses = [
            'hod_approved',
            'pending_divisional',
            'divisional_approved', 
            'pending_ict_director',
            'ict_director_approved',
            'pending_head_it',
            'head_it_approved',
            'pending_ict_officer',
        ];
        
        return in_array($status, $progressionStatuses);
    }
    
    /**
     * Get the next approver level from the current status
     *
     * @param string $status
     * @return string
     */
    protected function getNextApproverLevelFromStatus(string $status): string
    {
        return match($status) {
            'hod_approved', 'pending_divisional' => 'Divisional Director',
            'divisional_approved', 'pending_ict_director' => 'ICT Director',
            'ict_director_approved', 'pending_head_it' => 'Head of IT',
            'head_it_approved', 'pending_ict_officer' => 'ICT Officer',
            default => 'Next Approver'
        };
    }

    /**
     * Build additional approval notification message (for final approval)
     *
     * @param $user
     * @param string $requestType
     * @param array $additionalData
     * @return string
     */
    protected function buildAdditionalApprovalMessage($user, string $requestType, array $additionalData): string
    {
        $template = "ACCESS APPROVED: {requester} has been granted {type} access. Reference: {ref}. Please provide necessary assistance. - MNH IT";

        return str_replace([
            '{requester}',
            '{type}',
            '{ref}'
        ], [
            $user->name,
            ucfirst(str_replace('_', ' ', $requestType)),
            $additionalData['reference']
        ], $template);
    }
    
    /**
     * Build notification message for the next approver in the workflow
     *
     * @param $user
     * @param string $requestType
     * @param string $reference
     * @param string $approverLevel
     * @return string
     */
    protected function buildNextApproverNotificationMessage($user, string $requestType, string $reference, string $approverLevel): string
    {
        $template = "PENDING APPROVAL: {type} request from {requester} requires your review as {level}. Ref: {ref}. Please login to approve. - ICT MNH-MLOGANZILA";

        return str_replace([
            '{requester}',
            '{type}',
            '{ref}',
            '{level}'
        ], [
            $user->name ?? 'Staff',
            ucfirst(str_replace('_', ' ', $requestType)),
            $reference,
            $approverLevel
        ], $template);
    }

    /**
     * Build requester approval message for all workflow statuses
     */
    protected function buildApprovalMessage(string $name, string $requestType, string $status, array $additionalData): string
    {
        $templates = [
            // Initial submission
            'pending' => "Dear {name}, your {type} request has been submitted and is pending approval. Ref: {ref}. You will be notified on progress. - ICT MNH-MLOGANZILA",
            'pending_hod' => "Dear {name}, your {type} request has been submitted and is pending HOD approval. Ref: {ref}. - ICT MNH-MLOGANZILA",
            
            // HOD Stage
            'hod_approved' => "Dear {name}, your {type} request has been APPROVED by HOD and forwarded to Divisional Director. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'hod_rejected' => "Dear {name}, your {type} request has been REJECTED by HOD. Ref: {ref}. Reason: {reason}. Contact your HOD for guidance. - ICT MNH-MLOGANZILA",
            
            // Divisional Director Stage
            'pending_divisional' => "Dear {name}, your {type} request is now with Divisional Director for approval. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'divisional_approved' => "Dear {name}, your {type} request has been APPROVED by Divisional Director and forwarded to ICT Director. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'divisional_rejected' => "Dear {name}, your {type} request has been REJECTED by Divisional Director. Ref: {ref}. Reason: {reason}. - ICT MNH-MLOGANZILA",
            
            // ICT Director Stage
            'pending_ict_director' => "Dear {name}, your {type} request is now with ICT Director for approval. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'ict_director_approved' => "Dear {name}, your {type} request has been APPROVED by ICT Director and forwarded to Head of IT. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'ict_director_rejected' => "Dear {name}, your {type} request has been REJECTED by ICT Director. Ref: {ref}. Reason: {reason}. - ICT MNH-MLOGANZILA",
            
            // Head of IT Stage
            'pending_head_it' => "Dear {name}, your {type} request is now with Head of IT for approval. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'head_it_approved' => "Dear {name}, your {type} request has been FULLY APPROVED and assigned to ICT Officer for implementation. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'head_it_rejected' => "Dear {name}, your {type} request has been REJECTED by Head of IT. Ref: {ref}. Reason: {reason}. - ICT MNH-MLOGANZILA",
            
            // ICT Officer Stage
            'pending_ict_officer' => "Dear {name}, your {type} request is assigned to ICT Officer for implementation. Ref: {ref}. You will be notified once complete. - ICT MNH-MLOGANZILA",
            'implemented' => "Dear {name}, CONGRATULATIONS! Your {type} access is now ACTIVE and ready to use. Ref: {ref}. Contact ICT for support. - ICT MNH-MLOGANZILA",
            'ict_officer_rejected' => "Dear {name}, your {type} request could not be implemented. Ref: {ref}. Reason: {reason}. Contact ICT for assistance. - ICT MNH-MLOGANZILA",
            
            // Generic statuses
            'approved' => "Dear {name}, your {type} request has been APPROVED. Ref: {ref}. - ICT MNH-MLOGANZILA",
            'rejected' => "Dear {name}, your {type} request has been REJECTED. Ref: {ref}. Reason: {reason}. Contact ICT for assistance. - ICT MNH-MLOGANZILA",
            'cancelled' => "Dear {name}, your {type} request has been CANCELLED. Ref: {ref}. Reason: {reason}. - ICT MNH-MLOGANZILA"
        ];

        $template = $templates[$status] ?? $templates['pending'];

        return str_replace([
            '{name}', '{type}', '{ref}', '{reason}'
        ], [
            $name,
            ucfirst(str_replace('_', ' ', $requestType)),
            $additionalData['reference'] ?? 'N/A',
            $additionalData['reason'] ?? 'Not specified'
        ], $template);
    }

    /**
     * Build approver notification message
     */
    protected function buildApproverNotificationMessage(string $requestType, string $requesterName, string $department, string $reference): string
    {
        $template = "New {type} request from {requester} ({department}) requires your approval. Reference: {ref}. Please review in the system. - MNH IT";

        return str_replace([
            '{type}', '{requester}', '{department}', '{ref}'
        ], [
            ucfirst(str_replace('_', ' ', $requestType)),
            $requesterName,
            $department,
            $reference
        ], $template);
    }

    /**
     * The job failed to process.
     *
     * @param mixed $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed($event, $exception)
    {
        Log::error('SMS Notification job failed permanently', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}