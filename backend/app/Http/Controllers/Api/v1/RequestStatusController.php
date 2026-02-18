<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\UserAccess;
use App\Models\BookingService;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestStatusController extends Controller
{
    /**
     * The signature service instance.
     */
    private SignatureService $signatureService;

    /**
     * Create a new controller instance.
     */
    public function __construct(SignatureService $signatureService = null)
    {
        $this->middleware('auth:sanctum');
        $this->signatureService = $signatureService ?? new SignatureService();
    }

    /**
     * Get all requests for the authenticated user (both access requests and bookings).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $requests = collect();

            // Get user access requests submitted by the current user
            $accessRequests = UserAccess::with(['user', 'department'])
                ->where('user_id', $user->id)
                ->get();

            // If the current user is an ICT Officer, also include requests they are implementing (assigned/in_progress/completed)
            $isIctOfficer = false;
            try {
                if (method_exists($user, 'roles')) {
                    $isIctOfficer = $user->roles()->where('name', 'ict_officer')->exists();
                }
                if (!$isIctOfficer && method_exists($user, 'getPrimaryRoleName')) {
                    $isIctOfficer = strtolower($user->getPrimaryRoleName()) === 'ict_officer';
                }
            } catch (\Throwable $e) {
                $isIctOfficer = false;
            }

            if ($isIctOfficer) {
                $officerRelated = UserAccess::with(['user', 'department'])
                    ->where('ict_officer_user_id', $user->id)
                    ->whereIn('ict_officer_status', ['assigned', 'in_progress', 'implemented'])
                    ->get();
                // Merge and de-duplicate by id
                $accessRequests = $accessRequests->concat($officerRelated)->unique('id')->values();
            }

            $accessRequests = $accessRequests->map(function ($accessRequest) {
                return $this->transformAccessRequest($accessRequest);
            });

            // Get booking service requests
            $bookingRequests = BookingService::with(['user', 'departmentInfo', 'approvedBy'])
                ->where('user_id', $user->id)
                ->get()
                ->map(function ($booking) {
                    return $this->transformBookingRequest($booking);
                });

            // Combine both types of requests
            $requests = $accessRequests->concat($bookingRequests);

            // Apply filters
            if ($request->has('status') && $request->status !== '') {
                $requests = $requests->where('status', $request->status);
            }

            if ($request->has('type') && $request->type !== '') {
                $requests = $requests->where('type', $request->type);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = strtolower($request->search);
                $requests = $requests->filter(function ($req) use ($search) {
                    return str_contains(strtolower($req['id']), $search) ||
                           str_contains(strtolower($req['type']), $search) ||
                           collect($req['services'])->some(function ($service) use ($search) {
                               return str_contains(strtolower($service), $search);
                           });
                });
            }

            // Sort by created_at desc by default
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $requests = $requests->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc');

            // Convert to array and add pagination-like structure
            $requestsArray = $requests->values()->all();
            $total = count($requestsArray);

            // Manual pagination
            $perPage = min($request->get('per_page', 15), 100);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedRequests = array_slice($requestsArray, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $paginatedRequests,
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ],
                'message' => 'Requests retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user requests: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


    /**
     * Get a specific request by ID and type.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $requestId = $request->query('id');
            $requestType = $request->query('type');
            $user = Auth::user();

            // Add debugging
            Log::info('RequestStatusController::show called', [
                'request_id' => $requestId,
                'request_type' => $requestType,
                'user_id' => $user->id
            ]);

            if (!$requestId || !$requestType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request ID and type are required.'
                ], 400);
            }

            $requestData = null;

            if ($requestType === 'combined_access') {
                Log::info('Looking for UserAccess record', [
                    'id' => $requestId,
                    'user_id' => $user->id
                ]);
                
                $accessRequest = UserAccess::with(['user', 'department'])
                    ->where('id', $requestId)
                    ->where('user_id', $user->id)
                    ->first();

                Log::info('UserAccess query result', [
                    'found' => !is_null($accessRequest),
                    'access_request_id' => $accessRequest ? $accessRequest->id : null
                ]);

                if (!$accessRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access request not found or unauthorized.'
                    ], 404);
                }

                $requestData = $this->transformAccessRequestForDetails($accessRequest);

            } elseif ($requestType === 'booking_service') {
                Log::info('Looking for BookingService record', [
                    'id' => $requestId,
                    'user_id' => $user->id
                ]);
                
                $booking = BookingService::with(['user', 'departmentInfo', 'approvedBy', 'ictApprovedBy'])
                    ->where('id', $requestId)
                    ->where('user_id', $user->id)
                    ->first();

                Log::info('BookingService query result', [
                    'found' => !is_null($booking),
                    'booking_id' => $booking ? $booking->id : null,
                    'booking_data' => $booking ? [
                        'id' => $booking->id,
                        'borrower_name' => $booking->borrower_name,
                        'device_type' => $booking->device_type,
                        'custom_device' => $booking->custom_device,
                        'department' => $booking->department,
                        'status' => $booking->status,
                        'signature_path' => $booking->signature_path,
                        'department_info_loaded' => !is_null($booking->departmentInfo),
                        'user_loaded' => !is_null($booking->user)
                    ] : null
                ]);

                if (!$booking) {
                    // Let's also check if the booking exists at all (maybe for a different user)
                    $anyBooking = BookingService::where('id', $requestId)->first();
                    Log::info('Checking if booking exists for any user', [
                        'booking_exists' => !is_null($anyBooking),
                        'actual_user_id' => $anyBooking ? $anyBooking->user_id : null
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking request not found or unauthorized.'
                    ], 404);
                }

                $requestData = $this->transformBookingRequestForDetails($booking);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request type.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $requestData,
                'message' => 'Request details retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving request details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request details.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get request statistics for the authenticated user.
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Access requests statistics
            $accessStats = [
                'total' => UserAccess::where('user_id', $user->id)->count(),
                'pending' => UserAccess::where('user_id', $user->id)->where('status', 'pending')->count(),
                'approved' => UserAccess::where('user_id', $user->id)->whereIn('status', ['approved', 'hod_approved'])->count(),
                'rejected' => UserAccess::where('user_id', $user->id)->whereIn('status', ['rejected', 'hod_rejected'])->count(),
                'in_review' => UserAccess::where('user_id', $user->id)->where('status', 'in_review')->count(),
            ];

            // Booking requests statistics (based on ICT approval status)
            $userBookings = BookingService::where('user_id', $user->id)->get();
            $bookingStats = [
                'total' => $userBookings->count(),
                'pending' => $userBookings->filter(function($booking) {
                    return $this->getBookingStatusForUser($booking) === 'pending';
                })->count(),
                'approved' => $userBookings->filter(function($booking) {
                    return $this->getBookingStatusForUser($booking) === 'approved';
                })->count(),
                'rejected' => $userBookings->filter(function($booking) {
                    return $this->getBookingStatusForUser($booking) === 'rejected';
                })->count(),
                'in_use' => $userBookings->where('status', 'in_use')->count(),
                'returned' => $userBookings->where('status', 'returned')->count(),
                'overdue' => $userBookings->where('status', 'overdue')->count(),
            ];

            // Combined statistics
            $totalStats = [
                'total_requests' => $accessStats['total'] + $bookingStats['total'],
                'total_pending' => $accessStats['pending'] + $bookingStats['pending'],
                'total_approved' => $accessStats['approved'] + $bookingStats['approved'],
                'total_rejected' => $accessStats['rejected'] + $bookingStats['rejected'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'access_requests' => $accessStats,
                    'booking_requests' => $bookingStats,
                    'totals' => $totalStats,
                ],
                'message' => 'Statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving request statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Transform access request for list view.
     */
    private function transformAccessRequest(UserAccess $accessRequest): array
    {
        $services = [];
        if (is_array($accessRequest->request_type)) {
            foreach ($accessRequest->request_type as $type) {
                $services[] = $this->getServiceDisplayName($type);
            }
        } else {
            $services[] = $this->getServiceDisplayName($accessRequest->request_type);
        }

        return [
            'id' => 'REQ-' . str_pad($accessRequest->id, 6, '0', STR_PAD_LEFT),
            'original_id' => $accessRequest->id,
            'type' => 'combined_access',
            'services' => $services,
            'status' => $accessRequest->status,
            'current_step' => $this->getCurrentStep($accessRequest->status, $accessRequest),
            'created_at' => $accessRequest->created_at->toISOString(),
            'updated_at' => $accessRequest->updated_at->toISOString(),
            'staff_name' => $accessRequest->staff_name,
            'department' => $accessRequest->department ? $accessRequest->department->name : 'Unknown',
            // Expose granular statuses for small visual indicators in UI
            'hod_status' => $accessRequest->hod_status,
            'divisional_status' => $accessRequest->divisional_status,
            // SMS statuses (normalize to sms_status for UI)
            // Show SMS status for the current workflow stage
            'sms_to_hod_status' => $accessRequest->sms_to_hod_status ?? 'pending',
            'sms_to_ict_director_status' => $accessRequest->sms_to_ict_director_status ?? null,
            'sms_status' => $this->getCurrentSmsStatus($accessRequest),
        ];
    }

    /**
     * Transform booking request for list view.
     */
    private function transformBookingRequest(BookingService $booking): array
    {
        // Get device display name (primary label)
        $deviceDisplayName = $booking->device_type === 'others' && $booking->custom_device 
            ? $booking->custom_device 
            : (BookingService::getDeviceTypes()[$booking->device_type] ?? $booking->device_type);

        // Build services list from all selected devices (supports multi-device bookings)
        // Prefer the model's all_device_names accessor which aggregates primary and
        // additional inventory devices, then fall back to single display name.
        $services = $booking->all_device_names ?? [];
        if (empty($services)) {
            $services = [$deviceDisplayName];
        }

        // Get department name
        $departmentName = 'Unknown Department';
        if ($booking->departmentInfo) {
            $departmentName = $booking->departmentInfo->name;
        } elseif ($booking->department) {
            // If department is stored as ID, try to get the name
            $department = \App\Models\Department::find($booking->department);
            $departmentName = $department ? $department->name : "Department ID: {$booking->department}";
        }

        // Get device availability info if device is from inventory
        $deviceAvailabilityInfo = null;
        $isDeviceOutOfStock = false;
        if ($booking->device_inventory_id) {
            $deviceAvailabilityInfo = $this->getDeviceAvailabilityInfo($booking->device_inventory_id);
            $isDeviceOutOfStock = $deviceAvailabilityInfo && !$deviceAvailabilityInfo['is_available'] && $deviceAvailabilityInfo['status'] === 'out_of_stock';
        }

        // Normalize return status for legacy records where accessories flags
        // alone downgraded the booking to "returned_but_compromised".
        $normalizedReturnStatus = $booking->return_status ?? 'not_yet_returned';
        if ($normalizedReturnStatus === 'returned_but_compromised' && !empty($booking->device_condition_receiving)) {
            $receiving = json_decode($booking->device_condition_receiving, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($receiving)) {
                $visibleDamage = $receiving['visible_damage'] ?? false;
                $functionality = $receiving['functionality'] ?? 'fully_functional';
                $physical = $receiving['physical_condition'] ?? 'excellent';

                if ($visibleDamage === false && $functionality === 'fully_functional' && $physical !== 'poor') {
                    $normalizedReturnStatus = 'returned';
                }
            }
        }

        // Determine appropriate status and current step
        $status = $this->getBookingStatusForUser($booking, $isDeviceOutOfStock);
        $currentStep = $this->getBookingCurrentStep($status, $isDeviceOutOfStock);

        return [
            'id' => 'BOOK-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
            'original_id' => $booking->id,
            'type' => 'booking_service',
            'services' => $services,
            'status' => $status,
            'current_step' => $currentStep,
            'created_at' => $booking->created_at->toISOString(),
            'updated_at' => $booking->updated_at->toISOString(),
            'staff_name' => $booking->borrower_name,
            'department' => $departmentName,
            'device_availability' => $deviceAvailabilityInfo,
            'return_status' => $normalizedReturnStatus,
            // SMS status tracking for bookings:
            // - sms_to_ict_officers_status: tracks SMS sent to ICT officers when booking is created
            // - sms_to_requester_status: tracks SMS sent to requester when booking is approved
            'sms_to_ict_officers_status' => $booking->sms_to_ict_officers_status ?? 'pending',
            'sms_to_requester_status' => $booking->sms_to_requester_status ?? 'pending',
            // Show appropriate SMS status based on workflow stage:
            // - If pending ICT approval: show ICT officers notification status
            // - If approved/rejected: show requester notification status
            'sms_status' => $this->getBookingSmsStatus($booking),
        ];
    }

    /**
     * Transform access request for detailed view.
     */
    private function transformAccessRequestForDetails(UserAccess $accessRequest): array
    {
        $services = [];
        if (is_array($accessRequest->request_type)) {
            foreach ($accessRequest->request_type as $type) {
                $services[] = $this->getServiceDisplayName($type);
            }
        } else {
            $services[] = $this->getServiceDisplayName($accessRequest->request_type);
        }

        return [
            'id' => 'REQ-' . str_pad($accessRequest->id, 6, '0', STR_PAD_LEFT),
            'type' => 'combined_access',
            'staffName' => $accessRequest->staff_name,
            'pfNumber' => $accessRequest->pf_number,
            'department' => $accessRequest->department ? $accessRequest->department->name : 'Unknown',
'digitalSignature' => $accessRequest->signatures()->exists(),
            'moduleRequestedFor' => 'Use', // Default value
            'selectedModules' => $services,
            'accessType' => 'Permanent (until retirement)', // Default value
            'temporaryUntil' => null,
            'submissionDate' => $accessRequest->created_at->format('Y-m-d'),
            'currentStatus' => $accessRequest->status,
            // Use new status columns if available, fallback to legacy status
            'hodApprovalStatus' => $accessRequest->hod_status ?? $this->getApprovalStatus($accessRequest->status, 'hod'),
            'divisionalStatus' => $accessRequest->divisional_status ?? $this->getApprovalStatus($accessRequest->status, 'divisional'),
            'dictStatus' => $accessRequest->ict_director_status ?? $this->getApprovalStatus($accessRequest->status, 'dict'),
            'headOfItStatus' => $accessRequest->head_it_status ?? $this->getApprovalStatus($accessRequest->status, 'head_of_it'),
            'ictStatus' => $accessRequest->ict_officer_status ?? $this->getApprovalStatus($accessRequest->status, 'ict'),
            'comments' => is_array($accessRequest->purpose) ? implode(', ', $accessRequest->purpose) : ($accessRequest->purpose ?? 'No comments provided'),
            'hodApprovalDate' => null,
            'divisionalApprovalDate' => null,
            'dictApprovalDate' => null,
            'headOfItApprovalDate' => null,
            'ictApprovalDate' => null,
            'signature_url' => $this->signatureService->getSignatureUrl($accessRequest->signature_path),
            'signature_info' => $this->signatureService->getSignatureInfo($accessRequest->signature_path),
            
            // HOD Approval workflow data
            'hod_approval_status' => $accessRequest->hod_approval_status,
            'hod_approved_at' => $accessRequest->hod_approved_at,
            'hod_approved_by' => $accessRequest->hod_approved_by,
            'hod_approved_by_name' => $accessRequest->hod_approved_by_name,
            'hod_approval_comment' => $accessRequest->hod_approval_comment,
            'hod_rejection_reason' => $accessRequest->hod_rejection_reason,
            'hod_comments' => $accessRequest->hod_comments,
            
            // Divisional Director approval
            'divisional_director_approval_status' => $accessRequest->divisional_director_approval_status,
            'divisional_director_approved_at' => $accessRequest->divisional_director_approved_at,
            'divisional_director_approved_by' => $accessRequest->divisional_director_approved_by,
            'divisional_director_approved_by_name' => $accessRequest->divisional_director_approved_by_name,
            'divisional_director_approval_comment' => $accessRequest->divisional_director_approval_comment,
            
            // ICT Director approval
            'ict_director_approval_status' => $accessRequest->ict_director_approval_status,
            'ict_director_approved_at' => $accessRequest->ict_director_approved_at,
            'ict_director_approved_by' => $accessRequest->ict_director_approved_by,
            'ict_director_approved_by_name' => $accessRequest->ict_director_approved_by_name,
            'ict_director_approval_comment' => $accessRequest->ict_director_approval_comment,
            
            // Head of IT approval
            'head_of_it_approval_status' => $accessRequest->head_of_it_approval_status,
            'head_of_it_approved_at' => $accessRequest->head_of_it_approved_at,
            'head_of_it_approved_by' => $accessRequest->head_of_it_approved_by,
            'head_of_it_approved_by_name' => $accessRequest->head_of_it_approved_by_name,
            'head_of_it_approval_comment' => $accessRequest->head_of_it_approval_comment,
            
            // ICT Officer approval
            'ict_officer_approval_status' => $accessRequest->ict_officer_approval_status,
            'ict_officer_approved_at' => $accessRequest->ict_officer_approved_at,
            'ict_officer_approved_by' => $accessRequest->ict_officer_approved_by,
            'ict_officer_approved_by_name' => $accessRequest->ict_officer_approved_by_name,
            'ict_officer_approval_comment' => $accessRequest->ict_officer_approval_comment,
            
            // Implementation Comments
            'implementation_comments' => $accessRequest->implementation_comments,
            'ict_implementation_comments' => $accessRequest->implementation_comments, // Alias for frontend compatibility
            'ict_officer_comments' => $accessRequest->ict_officer_comments,
            'ict_officer_name' => $accessRequest->ict_officer_name,
            'ict_officer_implemented_at' => $accessRequest->ict_officer_implemented_at,
        ];
    }

    /**
     * Transform booking request for detailed view.
     */
    private function transformBookingRequestForDetails(BookingService $booking): array
    {
        try {
            // Get device display name
            $deviceDisplayName = $booking->device_type === 'others' && $booking->custom_device 
                ? $booking->custom_device 
                : (BookingService::getDeviceTypes()[$booking->device_type] ?? $booking->device_type);

            // Get department name
            $departmentName = 'Unknown Department';
            if ($booking->departmentInfo) {
                $departmentName = $booking->departmentInfo->name;
            } elseif ($booking->department) {
                // If department is stored as ID, try to get the name
                $department = \App\Models\Department::find($booking->department);
                $departmentName = $department ? $department->name : "Department ID: {$booking->department}";
            }

            // Get signature URL
            $signatureUrl = null;
            if ($booking->signature_path) {
                $signatureUrl = asset('storage/' . $booking->signature_path);
            }

            // Check if overdue
            $isOverdue = false;
            if ($booking->status !== 'returned' && $booking->return_date && $booking->return_time) {
                try {
                    $returnDateTime = \Carbon\Carbon::parse($booking->return_date->format('Y-m-d') . ' ' . $booking->return_time);
                    $isOverdue = $returnDateTime->isPast() && in_array($booking->status, ['approved', 'in_use']);
                } catch (\Exception $e) {
                    Log::warning('Error parsing return date/time for booking', [
                        'booking_id' => $booking->id,
                        'return_date' => $booking->return_date,
                        'return_time' => $booking->return_time,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'id' => 'BOOK-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
                'type' => 'booking_service',
                'staffName' => $booking->borrower_name,
                'pfNumber' => $booking->user ? $booking->user->pf_number : 'Unknown',
                'department' => $departmentName,
'digitalSignature' => method_exists($booking, 'signatures') ? $booking->signatures()->exists() : (\App\Models\Signature::where('document_id', $booking->id)->exists()),
                'deviceType' => $deviceDisplayName,
                'bookingDate' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                'returnDate' => $booking->return_date ? $booking->return_date->format('Y-m-d') : null,
                'returnTime' => $booking->return_time,
                'reason' => $booking->reason,
                'submissionDate' => $booking->created_at->format('Y-m-d'),
                'currentStatus' => $this->getBookingStatusForUser($booking),
                'adminNotes' => $booking->admin_notes,
                'approvedBy' => $booking->approvedBy ? $booking->approvedBy->name : null,
                'approvedAt' => $booking->approved_at ? $booking->approved_at->toISOString() : null,
                'deviceCollectedAt' => $booking->device_collected_at ? $booking->device_collected_at->toISOString() : null,
                'deviceReturnedAt' => $booking->device_returned_at ? $booking->device_returned_at->toISOString() : null,
                'isOverdue' => $isOverdue,
                'signature_url' => $signatureUrl,
                // Additional fields for compatibility with the frontend
                'moduleRequestedFor' => 'Device Booking',
                'selectedModules' => [$deviceDisplayName],
                'accessType' => 'Temporary Booking',
                'temporaryUntil' => $booking->return_date ? $booking->return_date->format('Y-m-d') : null,
                'comments' => $booking->reason,
                // ICT Approval Information (determines final booking status)
                'ict_approve' => $booking->ict_approve ?? 'pending',
                'ict_notes' => $booking->ict_notes,
                'ict_approved_by' => $booking->ict_approved_by,
                'ict_approved_at' => $booking->ict_approved_at ? $booking->ict_approved_at->toISOString() : null,
                'ict_approved_by_name' => $booking->ictApprovedBy ? $booking->ictApprovedBy->name : null,
                
                // For booking service, only show ICT Officer approval (remove HOD, Divisional, DICT, Head of IT)
                'hodApprovalStatus' => null, // Hidden for booking service
                'divisionalStatus' => null, // Hidden for booking service
                'dictStatus' => null, // Hidden for booking service
                'headOfItStatus' => null, // Hidden for booking service
                'ictStatus' => $booking->ict_approve ?? 'pending',
                'hodApprovalDate' => null,
                'divisionalApprovalDate' => null,
                'dictApprovalDate' => null,
                'headOfItApprovalDate' => null,
                'ictApprovalDate' => $booking->ict_approved_at ? $booking->ict_approved_at->format('Y-m-d') : null,
                'signature_info' => $booking->signature_path ? [
                    'path' => $booking->signature_path,
                    'url' => $signatureUrl,
                    'exists' => true
                ] : null,
                // Return Status field
                'return_status' => $booking->return_status ?? 'not_yet_returned',
            ];
        } catch (\Exception $e) {
            Log::error('Error transforming booking request for details', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get service display name.
     */
    private function getServiceDisplayName(string $serviceType): string
    {
        $serviceNames = [
            'jeeva_access' => 'Jeeva',
            'wellsoft' => 'Wellsoft',
            'internet_access_request' => 'Internet',
        ];

        return $serviceNames[$serviceType] ?? ucfirst(str_replace('_', ' ', $serviceType));
    }

    /**
     * Get current step for access requests using new status columns.
     */
    private function getCurrentStep(string $status, UserAccess $accessRequest = null): int
    {
        // If we have access to the request object, use new status columns
        if ($accessRequest) {
            return $this->getCurrentStepFromColumns($accessRequest);
        }
        
        // Updated to 6-step workflow mapping
        $stepMap = [
            'pending' => 2, // HOD Review
            'pending_hod' => 2, // HOD Review
            'hod_approved' => 3, // Divisional Director Review
            'pending_divisional' => 3, // Divisional Director Review
            'hod_rejected' => 2, // Stopped at HOD Review
            'divisional_approved' => 4, // ICT Director Review
            'pending_ict_director' => 4, // ICT Director Review
            'divisional_rejected' => 3, // Stopped at Divisional Review
            'ict_director_approved' => 5, // Head IT Review
            'pending_head_it' => 5, // Head IT Review
            'ict_director_rejected' => 4, // Stopped at ICT Director Review
            'head_it_approved' => 6, // ICT Officer Implementation
            'pending_ict_officer' => 6, // ICT Officer Implementation
            'head_it_rejected' => 5, // Stopped at Head IT Review
            'implemented' => 6, // Completed at ICT Officer level
            'approved' => 6, // Completed at ICT Officer level
            'rejected' => 2, // Stopped at current step
            'in_review' => 3, // Divisional Director
        ];

        return $stepMap[$status] ?? 1;
    }
    
    /**
     * Get current step using new status columns
     */
    private function getCurrentStepFromColumns(UserAccess $accessRequest): int
    {
        // Check for rejections first
        if ($accessRequest->hod_status === 'rejected') return 2;
        if ($accessRequest->divisional_status === 'rejected') return 3;
        if ($accessRequest->ict_director_status === 'rejected') return 4;
        if ($accessRequest->head_it_status === 'rejected') return 5;
        if ($accessRequest->ict_officer_status === 'rejected') return 6;
        
        // Check workflow progression - Updated to 6-step workflow
        if ($accessRequest->ict_officer_status === 'implemented') return 6; // Complete at ICT Officer
        if ($accessRequest->head_it_status === 'approved') return 6; // ICT Officer Implementation
        if ($accessRequest->ict_director_status === 'approved') return 5; // Head IT Review
        if (in_array($accessRequest->divisional_status, ['approved','skipped'], true)) return 4; // ICT Director Review
        if (in_array($accessRequest->hod_status, ['approved','skipped'], true)) return 3; // Divisional Director Review
        
        return 2; // HOD Review (default)
    }

    /**
     * Get current step for booking requests (3-step system).
     */
    private function getBookingCurrentStep(string $status, bool $isDeviceOutOfStock = false): int
    {
        // If device is out of stock and status is pending, show waiting step
        // User rule: current step should be 'waiting from another user' instead of 'HOD review'
        if ($isDeviceOutOfStock && $status === 'pending') {
            return 0; // Special step for waiting from another user to return the device
        }
        
        // Updated 3-step mapping for booking requests:
        // Step 1: Request Submitted (user submitted booking request details)
        // Step 2: ICT Approval (ICT approve the booking request)
        // Step 3: Device Received (ICT receive the device for clearing in system)
        $stepMap = [
            'pending' => 2, // Step 2: Waiting for ICT Approval
            'approved' => 3, // Step 3: ICT Approved, ready for device receiving
            'in_use' => 3, // Step 3: Device in use (approved and received)
            'returned' => 3, // Step 3: Device returned (completed)
            'rejected' => 2, // Step 2: Rejected at ICT Approval stage
            'overdue' => 3, // Step 3: Device overdue but was approved
        ];

        return $stepMap[$status] ?? 1;
    }

    /**
     * Get approval status for specific step.
     */
    private function getApprovalStatus(string $currentStatus, string $step): string
    {
        // Handle HOD-specific statuses
        if ($currentStatus === 'hod_approved') {
            // HOD has approved, so HOD step is approved, others are pending
            return $step === 'hod' ? 'approved' : 'pending';
        } elseif ($currentStatus === 'hod_rejected') {
            // HOD has rejected, so HOD step is rejected, others remain pending
            return $step === 'hod' ? 'rejected' : 'pending';
        } elseif ($currentStatus === 'approved') {
            // Final approval - all steps are approved
            return 'approved';
        } elseif ($currentStatus === 'rejected') {
            // Final rejection - exact step depends on where it was rejected
            return 'rejected';
        } else {
            // Default pending status
            return 'pending';
        }
    }

    /**
     * Get approval status specifically for booking service (ICT Officer only).
     */
    private function getBookingApprovalStatus(string $currentStatus): string
    {
        // For booking service, only ICT Officer approval is needed
        switch ($currentStatus) {
            case 'pending':
                return 'pending';
            case 'approved':
            case 'in_use':
            case 'returned':
                return 'approved';
            case 'rejected':
                return 'rejected';
            case 'overdue':
                return 'approved'; // Device was approved but is now overdue
            default:
                return 'pending';
        }
    }

    /**
     * Get booking status for user display (prioritizes ICT approval status).
     */
    private function getBookingStatusForUser(BookingService $booking, bool $isDeviceOutOfStock = false): string
    {
        // For booking service, the ICT approval status determines the user-visible status
        // Priority order: ICT approval status > general status > out-of-stock logic
        
        // If ICT has explicitly approved or rejected, show that
        if ($booking->ict_approve && in_array($booking->ict_approve, ['approved', 'rejected'])) {
            return $booking->ict_approve;
        }
        
        // If device is out of stock and ICT hasn't made a decision yet, treat as 'pending' instead of 'out of stock'
        // User rule: when selected device is not available, status should be 'pending' not 'out of stock'
        if ($isDeviceOutOfStock && ($booking->ict_approve === 'pending' || !$booking->ict_approve)) {
            return 'pending'; // Waiting for another user to return the device
        }
        
        // If ICT approval is pending, show pending regardless of general status
        if ($booking->ict_approve === 'pending') {
            return 'pending';
        }
        
        // Fallback to general booking status interpretation
        switch ($booking->status) {
            case 'pending':
                return 'pending'; // Waiting for ICT approval
            case 'approved':
                return 'approved'; // Approved by ICT
            case 'rejected':
                return 'rejected'; // Rejected by ICT
            case 'in_use':
                return 'approved'; // Device is in use (was approved)
            case 'returned':
                return 'approved'; // Device returned (was approved)
            case 'overdue':
                return 'approved'; // Device overdue (was approved but not returned)
            default:
                return $booking->status;
        }
    }

    /**
     * Get appropriate SMS status for booking based on workflow stage.
     * 
     * SMS Flow for bookings:
     * 1. Staff submits booking → SMS sent to ICT Officers/Secretary ICT
     * 2. ICT approves → SMS sent to requester to collect device
     * 
     * This method returns the SMS status that is most relevant to the current workflow stage.
     */
    private function getBookingSmsStatus(BookingService $booking): string
    {
        // If ICT has made a decision (approved or rejected), show requester SMS status
        if (in_array($booking->ict_approve, ['approved', 'rejected'])) {
            return $booking->sms_to_requester_status ?? 'pending';
        }
        
        // If still pending ICT approval, show ICT officers notification status
        // This is the SMS sent to ICT Officers when the booking was created
        return $booking->sms_to_ict_officers_status ?? 'pending';
    }

    /**
     * Get available request types.
     */
    public function getRequestTypes(): JsonResponse
    {
        try {
            $types = [
                'combined_access' => 'Combined Access',
                'booking_service' => 'Booking Service',
                'jeeva_access' => 'Jeeva Access',
                'wellsoft_access' => 'Wellsoft Access',
                'internet_access' => 'Internet Access'
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Request types retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving request types: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request types.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available status options.
     */
    public function getStatusOptions(): JsonResponse
    {
        try {
            $statuses = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'in_review' => 'In Review',
                'in_use' => 'In Use',
                'returned' => 'Returned',
                'overdue' => 'Overdue'
            ];

            return response()->json([
                'success' => true,
                'data' => $statuses,
                'message' => 'Status options retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving status options: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve status options.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Debug endpoint to check database records.
     */
    public function debug(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Count records
            $userAccessCount = UserAccess::where('user_id', $user->id)->count();
            $bookingServiceCount = BookingService::where('user_id', $user->id)->count();
            
            // Get sample records
            $userAccessRecords = UserAccess::where('user_id', $user->id)->limit(5)->get();
            $bookingServiceRecords = BookingService::where('user_id', $user->id)->limit(5)->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'user_access_count' => $userAccessCount,
                    'booking_service_count' => $bookingServiceCount,
                    'total_requests' => $userAccessCount + $bookingServiceCount,
                    'sample_user_access' => $userAccessRecords->map(function($record) {
                        return [
                            'id' => $record->id,
                            'pf_number' => $record->pf_number,
                            'staff_name' => $record->staff_name,
                            'request_type' => $record->request_type,
                            'status' => $record->status,
                            'created_at' => $record->created_at
                        ];
                    }),
                    'sample_booking_service' => $bookingServiceRecords->map(function($record) {
                        return [
                            'id' => $record->id,
                            'borrower_name' => $record->borrower_name,
                            'device_type' => $record->device_type,
                            'status' => $record->status,
                            'created_at' => $record->created_at
                        ];
                    })
                ],
                'message' => 'Debug information retrieved successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in debug endpoint: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Debug failed.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get device availability information for a booking request.
     */
    private function getDeviceAvailabilityInfo(int $deviceInventoryId): ?array
    {
        try {
            // Get the device availability information
            $availabilityInfo = BookingService::checkDeviceAvailability($deviceInventoryId);
            
            if (!$availabilityInfo['available'] && $availabilityInfo['can_request']) {
                // Device is out of stock, get active bookings info
                $activeBookings = BookingService::getActiveBookingsForDevice($deviceInventoryId);
                
                $currentUsers = [];
                $nearestReturnInfo = null;
                
                foreach ($activeBookings as $activeBooking) {
                    $currentUsers[] = [
                        'user_name' => $activeBooking->user ? $activeBooking->user->name : $activeBooking->borrower_name,
                        'return_date' => $activeBooking->return_date ? $activeBooking->return_date->format('M d, Y') : 'Not specified',
                        'return_time' => $activeBooking->return_time ?? 'Not specified',
                        'return_date_time' => $activeBooking->return_date_time ? $activeBooking->return_date_time->format('M d, Y \\a\\t g:i A') : 'Not specified'
                    ];
                }
                
                // Get the nearest return time
                if (isset($availabilityInfo['nearest_return'])) {
                    $nearestReturnInfo = [
                        'date_time' => $availabilityInfo['nearest_return']->format('M d, Y \\a\\t g:i A'),
                        'date_only' => $availabilityInfo['nearest_return']->format('M d, Y'),
                        'time_only' => $availabilityInfo['nearest_return']->format('g:i A'),
                        'is_today' => $availabilityInfo['nearest_return']->isToday(),
                        'is_tomorrow' => $availabilityInfo['nearest_return']->isTomorrow(),
                        'relative_time' => $availabilityInfo['nearest_return']->diffForHumans()
                    ];
                }
                
                return [
                    'is_available' => false,
                    'status' => 'out_of_stock',
                    'message' => $availabilityInfo['message'],
                    'current_users' => $currentUsers,
                    'nearest_return' => $nearestReturnInfo,
                    'can_request' => true
                ];
            } elseif ($availabilityInfo['available']) {
                return [
                    'is_available' => true,
                    'status' => 'available',
                    'message' => 'Device is available for borrowing',
                    'available_quantity' => $availabilityInfo['available_quantity'] ?? 1,
                    'can_request' => true
                ];
            } else {
                return [
                    'is_available' => false,
                    'status' => 'unavailable',
                    'message' => $availabilityInfo['message'] ?? 'Device is not available',
                    'can_request' => false
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error getting device availability info', [
                'device_inventory_id' => $deviceInventoryId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_available' => false,
                'status' => 'unknown',
                'message' => 'Unable to determine device availability',
                'can_request' => true
            ];
        }
    }

    /**
     * Get SMS status for the current workflow stage.
     * Returns the SMS status relevant to where the request currently is in the workflow.
     */
    private function getCurrentSmsStatus(UserAccess $accessRequest): string
    {
        // Determine current stage and return appropriate SMS status
        // Priority: show the SMS status for the stage we're currently at or just passed
        
        // If at HOD stage (pending or just submitted)
        if ($accessRequest->hod_status === 'pending' || $accessRequest->hod_status === null) {
            return $accessRequest->sms_to_hod_status ?? 'pending';
        }
        
        // If HOD rejected, show HOD SMS status
        if ($accessRequest->hod_status === 'rejected') {
            return $accessRequest->sms_to_hod_status ?? 'pending';
        }
        
        // If at Divisional stage
        if ($accessRequest->hod_status === 'approved' && 
            ($accessRequest->divisional_status === 'pending' || $accessRequest->divisional_status === null)) {
            return $accessRequest->sms_to_divisional_status ?? 'pending';
        }
        
        // If at ICT Director stage
        if (in_array($accessRequest->divisional_status, ['approved', 'skipped']) && 
            ($accessRequest->ict_director_status === 'pending' || $accessRequest->ict_director_status === null)) {
            return $accessRequest->sms_to_ict_director_status ?? 'pending';
        }
        
        // If at Head IT stage
        if ($accessRequest->ict_director_status === 'approved' && 
            ($accessRequest->head_it_status === 'pending' || $accessRequest->head_it_status === null)) {
            return $accessRequest->sms_to_head_it_status ?? 'pending';
        }
        
        // If at ICT Officer stage
        if ($accessRequest->head_it_status === 'approved' && 
            ($accessRequest->ict_officer_status === 'pending' || $accessRequest->ict_officer_status === null)) {
            return $accessRequest->sms_to_ict_officer_status ?? 'pending';
        }
        
        // Default: show HOD status as it's the first stage
        return $accessRequest->sms_to_hod_status ?? 'pending';
    }
}
