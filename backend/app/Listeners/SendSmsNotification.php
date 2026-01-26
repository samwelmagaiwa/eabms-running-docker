<?php

namespace App\Listeners;

use App\Events\ApprovalStatusChanged;
use App\Events\ApprovalRequestSubmitted;
use App\Services\SmsModule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use InteractsWithQueue;

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

        $reference = $event->request->reference ?? $event->request->id;

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

        // If approved and there are additional stakeholders to notify
        if ($statusKey === 'approved' && !empty($event->additionalNotifyUsers)) {
            $message = $this->buildAdditionalApprovalMessage(
                $event->user,
                $event->requestType,
                [ 'reference' => $reference ]
            );

            $this->sms->sendBulkSms(
                $event->additionalNotifyUsers,
                $message,
                'approval_notification'
            );
        }
    }

    /**
     * Build additional approval notification message
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
     * Build requester approval message (pending/approved/rejected)
     */
    protected function buildApprovalMessage(string $name, string $requestType, string $status, array $additionalData): string
    {
        $templates = [
            'pending' => "Dear {name}, your {type} request has been submitted and is pending approval. Reference: {ref}. You will be notified once reviewed. - ICT MNH-MLOGANZILA",
            'approved' => "Dear {name}, your {type} request has been APPROVED. Reference: {ref}. You can now proceed with access. - ICT MNH-MLOGANZILA",
            'rejected' => "Dear {name}, your {type} request has been REJECTED. Reference: {ref}. Reason: {reason}. Contact IT for assistance. - ICT MNH-MLOGANZILA"
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