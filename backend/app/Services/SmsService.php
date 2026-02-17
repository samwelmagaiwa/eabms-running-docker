<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\SmsLog;
use Exception;

class SmsService
{
    protected $apiUrl;
    protected $apiKey;
    protected $secretKey;
    protected $senderId;
    protected $testMode;

    public function __construct()
    {
        $this->apiUrl = config('sms.api_url');
        $this->apiKey = config('sms.api_key');
        $this->secretKey = config('sms.secret_key');
        $this->senderId = config('sms.sender_id');
        $this->testMode = config('sms.test_mode', false);
    }

    /**
     * Send SMS to a single recipient
     *
     * @param string $phoneNumber
     * @param string $message
     * @param string $type (optional) - Type of SMS (approval, notification, etc.)
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message, string $type = 'notification'): array
    {
        try {
            // Validate phone number format
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                return $this->buildResponse(false, 'Invalid phone number format');
            }

            // Check rate limiting
            if ($this->isRateLimited($phoneNumber)) {
                return $this->buildResponse(false, 'Rate limit exceeded for this phone number');
            }

            // Prepare SMS payload
            $payload = $this->prepareSmsPayload($phoneNumber, $message);

            // Send SMS via HTTP API
            $response = $this->makeApiCall($payload);

            // Log the SMS attempt
            $this->logSms($phoneNumber, $message, $type, $response['success'], $response);

            return $response;

        } catch (Exception $e) {
            Log::error('SMS Service Error: ' . $e->getMessage(), [
                'phone' => $phoneNumber,
                'message' => substr($message, 0, 100),
                'type' => $type
            ]);

            return $this->buildResponse(false, 'SMS service error: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS to multiple recipients (bulk SMS)
     *
     * @param array $recipients Array of phone numbers or User objects
     * @param string $message
     * @param string $type
     * @return array
     */
    public function sendBulkSms(array $recipients, string $message, string $type = 'bulk'): array
    {
        $results = [
            'total' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($recipients as $recipient) {
            $phoneNumber = $this->extractPhoneNumber($recipient);
            
            if (!$phoneNumber) {
                $results['failed']++;
                $results['details'][] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'reason' => 'Invalid phone number'
                ];
                continue;
            }

            $result = $this->sendSms($phoneNumber, $message, $type);
            
            if ($result['success']) {
                $results['sent']++;
                $results['details'][] = [
                    'phone' => $phoneNumber,
                    'status' => 'sent',
                    'message_id' => $result['data']['message_id'] ?? null
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'phone' => $phoneNumber,
                    'status' => 'failed',
                    'reason' => $result['message']
                ];
            }

            // Add small delay between messages to avoid overwhelming the API
            usleep(100000); // 0.1 second delay
        }

        Log::info('Bulk SMS completed', [
            'type' => $type,
            'total' => $results['total'],
            'sent' => $results['sent'],
            'failed' => $results['failed']
        ]);

        return $results;
    }

    /**
     * Send approval notification SMS
     *
     * @param User $user
     * @param string $requestType
     * @param string $status (pending, approved, rejected)
     * @param array $additionalData
     * @return array
     */
    public function sendApprovalNotification(User $user, string $requestType, string $status, array $additionalData = []): array
    {
        if (!$user->phone) {
            return $this->buildResponse(false, 'User has no phone number');
        }

        $message = $this->buildApprovalMessage($user, $requestType, $status, $additionalData);
        
        return $this->sendSms($user->phone, $message, 'approval');
    }

    /**
     * Send notification to HOD/Approvers
     *
     * @param array $approvers Array of User objects
     * @param string $requestType
     * @param User $requester
     * @param array $additionalData
     * @return array
     */
    public function notifyApprovers(array $approvers, string $requestType, User $requester, array $additionalData = []): array
    {
        $message = $this->buildApproverNotificationMessage($requestType, $requester, $additionalData);
        
        $phoneNumbers = [];
        foreach ($approvers as $approver) {
            if ($approver->phone) {
                $phoneNumbers[] = $approver->phone;
            }
        }

        return $this->sendBulkSms($phoneNumbers, $message, 'approval_notification');
    }

    /**
     * Format phone number to international format
     * Automatically adds Tanzania country code +255 if missing
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Already has country code 255 (e.g., 255712345678)
        if (strlen($phoneNumber) === 12 && substr($phoneNumber, 0, 3) === '255') {
            return $phoneNumber;
        }

        // Add Tanzania country code if not present
        // 9 digits starting with 6, 7, 8, or 9
        if (strlen($phoneNumber) === 9 && in_array(substr($phoneNumber, 0, 1), ['6', '7', '8', '9'])) {
            return '255' . $phoneNumber;
        }
        
        // 10 digits starting with 06, 07, 08, or 09
        if (strlen($phoneNumber) === 10 && in_array(substr($phoneNumber, 0, 2), ['06', '07', '08', '09'])) {
            return '255' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }

    /**
     * Validate phone number format
     *
     * @param string $phoneNumber
     * @return bool
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Check if it's a valid Tanzanian mobile number (12 digits starting with 255)
        // Supports all Tanzanian mobile prefixes: 2556x, 2557x, 2558x, 2559x
        return preg_match('/^255[6-9][0-9]{8}$/', $phoneNumber);
    }

    /**
     * Check if phone number is rate limited
     *
     * @param string $phoneNumber
     * @return bool
     */
    protected function isRateLimited(string $phoneNumber): bool
    {
        $key = 'sms_rate_limit:' . $phoneNumber;
        $attempts = Cache::get($key, 0);

        // Configurable rate limit (defaults to 5/hour)
        $maxAttempts = config('sms.rate_limit.max_per_hour_per_number', 5);

        if ($attempts >= $maxAttempts) {
            return true;
        }

        // Increment and set expiry
        Cache::put($key, $attempts + 1, now()->addHour());

        return false;
    }

    /**
     * Prepare SMS payload for API call
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    protected function prepareSmsPayload(string $phoneNumber, string $message): array
    {
        // KODA TECH SMS provider payload format
        return [
            'api_key' => $this->apiKey,
            'secret_key' => $this->secretKey,
            'recipient' => $phoneNumber,
            'message' => $message,
            'sender_id' => $this->senderId,
            'test_mode' => $this->testMode
        ];
    }

    /**
     * Make API call to SMS provider using cURL
     *
     * @param array $payload
     * @return array
     */
    protected function makeApiCall(array $payload): array
    {
        try {
            // If in test mode, return success without making actual API call
            if ($this->testMode) {
                Log::info('SMS sent in test mode', $payload);
                return $this->buildResponse(true, 'SMS sent successfully (test mode)', [
                    'message_id' => 'TEST_' . time(),
                    'status' => 'sent',
                    'test_mode' => true
                ]);
            }

            // Encode credentials for Basic Auth
            $auth = base64_encode($this->apiKey . ':' . $this->secretKey);

            // Prepare JSON body according to KODA TECH API specs
            $apiPayload = [
                "from" => $this->senderId,
                "to" => $payload['recipient'],
                "text" => $payload['message'],
                "reference" => uniqid("ref_")
            ];

            // Initialize CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, config('sms.timeout', 30));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . $auth,
                "Content-Type: application/json",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiPayload));

            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Handle cURL errors
            if ($curlError) {
                Log::error('SMS cURL Error', [
                    'error' => $curlError,
                    'payload' => $apiPayload
                ]);
                return $this->buildResponse(false, 'Connection error: ' . $curlError);
            }

            // Log response for debugging (debug level to avoid high-volume logs)
            Log::debug('SMS API Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'payload' => $apiPayload
            ]);

            // Handle non-200 HTTP responses
            if ($httpCode !== 200) {
                Log::error('SMS HTTP Error', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return $this->buildResponse(false, 'HTTP Error: ' . $httpCode);
            }

            // Decode JSON response
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('SMS JSON Decode Error', [
                    'error' => json_last_error_msg(),
                    'response' => $response
                ]);
                return $this->buildResponse(false, 'Invalid JSON response');
            }

            // Check if the response contains messages (successful submission)
            if (isset($data['messages']) && is_array($data['messages'])) {
                Log::info('SMS successfully sent', [
                    'message_count' => count($data['messages']),
                    'response' => $data
                ]);
                return $this->buildResponse(true, 'SMS sent successfully', $data);
            }

            // Check for error in response
            if (isset($data['error'])) {
                return $this->buildResponse(false, 'API Error: ' . ($data['error']['message'] ?? 'Unknown error'), $data);
            }

            return $this->buildResponse(true, 'SMS processed', $data);

        } catch (Exception $e) {
            Log::error('SMS API Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->buildResponse(false, 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * Extract phone number from recipient (string or User object)
     *
     * @param mixed $recipient
     * @return string|null
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
     * Build approval message for different statuses
     *
     * @param User $user
     * @param string $requestType
     * @param string $status
     * @param array $additionalData
     * @return string
     */
    protected function buildApprovalMessage(User $user, string $requestType, string $status, array $additionalData): string
    {
        $templates = [
            'pending' => "Dear {name}, your {type} request has been submitted and is pending approval. Reference: {ref}. You will be notified once reviewed. - MNH IT",
            'approved' => "Dear {name}, your {type} request has been APPROVED. Reference: {ref}. You can now proceed with access. - MNH IT",
            'rejected' => "Dear {name}, your {type} request has been REJECTED. Reference: {ref}. Reason: {reason}. Contact IT for assistance. - MNH IT"
        ];

        $template = $templates[$status] ?? $templates['pending'];
        
        return str_replace([
            '{name}',
            '{type}',
            '{ref}',
            '{reason}'
        ], [
            $user->name,
            ucfirst(str_replace('_', ' ', $requestType)),
            $additionalData['reference'] ?? 'N/A',
            $additionalData['reason'] ?? 'Not specified'
        ], $template);
    }

    /**
     * Build notification message for approvers
     *
     * @param string $requestType
     * @param User $requester
     * @param array $additionalData
     * @return string
     */
    protected function buildApproverNotificationMessage(string $requestType, User $requester, array $additionalData): string
    {
        $template = "New {type} request from {requester} ({department}) requires your approval. Reference: {ref}. Please review in the system. - MNH IT";
        
        return str_replace([
            '{type}',
            '{requester}',
            '{department}',
            '{ref}'
        ], [
            ucfirst(str_replace('_', ' ', $requestType)),
            $requester->name,
            $requester->department->name ?? 'N/A',
            $additionalData['reference'] ?? 'N/A'
        ], $template);
    }

    /**
     * Log SMS attempt
     *
     * @param string $phoneNumber
     * @param string $message
     * @param string $type
     * @param bool $success
     * @param array $response
     * @return void
     */
    protected function logSms(string $phoneNumber, string $message, string $type, bool $success, array $response): void
    {
        try {
            // Store provider response as structured JSON (not JSON-encoded string)
            SmsLog::create([
                'phone_number' => $phoneNumber,
                'message' => $message,
                'type' => $type,
                'status' => $success ? 'sent' : 'failed',
                'provider_response' => $response,
                'sent_at' => $success ? now() : null
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log SMS: ' . $e->getMessage());
        }
    }

    /**
     * Build standardized response array
     *
     * @param bool $success
     * @param string $message
     * @param mixed $data
     * @return array
     */
    protected function buildResponse(bool $success, string $message, $data = null): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Get SMS sending statistics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
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
}