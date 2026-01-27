<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\SmsModule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * Request a password reset OTP using a registered phone number.
     * This endpoint is public and does not require authentication.
     */
    public function requestByPhone(Request $request, SmsModule $smsModule): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
                'errors' => [
                    'phone' => ['The provided phone number is not registered.'],
                ],
            ], 404);
        }

        try {
            $otpCode = random_int(100000, 999999);
            // OTP expiry set to 3 minutes to match frontend countdown
            $expiresAt = Carbon::now()->addMinutes(3);

            PasswordResetOtp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $user->phone,
                    'otp_hash' => Hash::make((string) $otpCode),
                    'attempts' => 0,
                    'expires_at' => $expiresAt,
                    'consumed_at' => null,
                ]
            );

            try {
                if (!empty($user->phone)) {
                    $message = sprintf(
                        'Dear %s, your EABMS password reset code is %s. It will expire in 3 minutes. Do not share this code with anyone.',
                        $user->name,
                        $otpCode
                    );

                    $smsResult = $smsModule->sendSms($user->phone, $message, 'password_reset_otp');

                    Log::info('Password reset OTP SMS result', [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'success' => $smsResult['success'] ?? null,
                        'message' => $smsResult['message'] ?? null,
                    ]);
                } else {
                    Log::warning('Password reset OTP SMS skipped - user has no phone number', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            } catch (\Exception $smsException) {
                Log::error('Failed to send password reset OTP SMS', [
                    'user_id' => $user->id,
                    'error' => $smsException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your registered phone number.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating password reset OTP', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify OTP for password reset (does not change the password).
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:50',
            'otp' => 'required|string|size:6',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
                'errors' => [
                    'phone' => ['The provided phone number is not registered.'],
                ],
            ], 404);
        }

        $record = PasswordResetOtp::where('user_id', $user->id)->first();

        if (!$record || $record->consumed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'errors' => [
                    'otp' => ['The code is invalid or has already been used. Please request a new code.'],
                ],
            ], 422);
        }

        // Lock after too many invalid attempts
        if ($record->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many incorrect attempts.',
                'errors' => [
                    'otp' => ['You have entered an incorrect code too many times. Please request a new code.'],
                ],
            ], 422);
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired.',
                'errors' => [
'otp' => ['Your code has expired (3 minutes). Please request a new code and try again.'],
                ],
            ], 422);
        }

        if (!Hash::check($validated['otp'], $record->otp_hash)) {
            $record->increment('attempts');
            $remaining = max(0, 5 - $record->attempts);
            $detail = $remaining > 0
                ? 'The code you entered is incorrect. You have ' . $remaining . ' attempt(s) remaining before it locks.'
                : 'You have entered an incorrect code too many times. Please request a new code.';

            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP.',
                'errors' => [
                    'otp' => [$detail],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }

    /**
     * Reset password using phone + OTP and log user in (returns user + token).
     */
    public function resetWithOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:50',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
                'errors' => [
                    'phone' => ['The provided phone number is not registered.'],
                ],
            ], 404);
        }

        $record = PasswordResetOtp::where('user_id', $user->id)->first();

        if (!$record || $record->consumed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'errors' => [
                    'otp' => ['The code is invalid or has already been used. Please request a new code.'],
                ],
            ], 422);
        }

        // Lock after too many invalid attempts
        if ($record->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many incorrect attempts.',
                'errors' => [
                    'otp' => ['You have entered an incorrect code too many times. Please request a new code.'],
                ],
            ], 422);
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired.',
                'errors' => [
'otp' => ['Your code has expired (3 minutes). Please request a new code and try again.'],
                ],
            ], 422);
        }

        if (!Hash::check($validated['otp'], $record->otp_hash)) {
            $record->increment('attempts');
            $remaining = max(0, 5 - $record->attempts);
            $detail = $remaining > 0
                ? 'The code you entered is incorrect. You have ' . $remaining . ' attempt(s) remaining before it locks.'
                : 'You have entered an incorrect code too many times. Please request a new code.';

            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP.',
                'errors' => [
                    'otp' => [$detail],
                ],
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update password
            $user->password = Hash::make($validated['password']);
            if (isset($user->must_change_password)) {
                $user->must_change_password = false;
            }
            $user->save();

            // Mark OTP as consumed
            $record->consumed_at = Carbon::now();
            $record->save();

            // Create Sanctum token similar to AuthController::login
            $userAgent = $request->header('User-Agent', 'Unknown');
            $ipAddress = $request->ip();
            $tokenName = $this->generateTokenName($user, $userAgent, $ipAddress);
            $abilities = $this->getTokenAbilities($user);
            $token = $user->createToken($tokenName, array_values($abilities))->plainTextToken;

            // Load relationships for response
            $user->load(['roles', 'onboarding']);
            $onboarding = $user->getOrCreateOnboarding();
            $primaryRole = $user->getPrimaryRoleName();
            $userRoles = $user->roles->pluck('name')->toArray();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'pf_number' => $user->pf_number,
                        'profile_photo_url' => $user->profile_photo_path 
                            ? rtrim(config('app.url'), '/') . '/storage/' . $user->profile_photo_path 
                            : null,
                        'role_id' => null,
                        'role' => $primaryRole,
                        'role_name' => $primaryRole,
                        'primary_role' => $primaryRole,
                        'roles' => $userRoles,
                        'permissions' => $user->getAllPermissions(),
                        'needs_onboarding' => $user->needsOnboarding(),
                        'onboarding_step' => $onboarding->current_step,
                        'must_change_password' => (bool) ($user->must_change_password ?? false),
                    ],
                    'token' => $token,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resetting password with OTP', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password.',
            ], 500);
        }
    }

    /**
     * Generate a unique token name (copy of AuthController logic).
     */
    private function generateTokenName(User $user, string $userAgent, string $ipAddress): string
    {
        $browser = $this->getBrowserName($userAgent);
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $rolePrefix = $user->getPrimaryRoleName() ? strtoupper($user->getPrimaryRoleName()) : 'USER';

        return "{$rolePrefix}_{$browser}_{$ipAddress}_{$timestamp}";
    }

    /**
     * Extract browser name from user agent.
     */
    private function getBrowserName(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }

        return 'Unknown';
    }

    /**
     * Get token abilities based on user role (copy of AuthController logic).
     */
    private function getTokenAbilities(User $user): array
    {
        $baseAbilities = ['*'];

        $userRoles = $user->roles()->pluck('name')->toArray();

        if (empty($userRoles)) {
            return $baseAbilities;
        }

        $abilities = $baseAbilities;

        if (array_intersect($userRoles, ['admin'])) {
            $abilities = array_merge($abilities, [
                'admin-access',
                'manage-users',
                'manage-requests',
                'view-all-data',
                'system-settings',
            ]);
        }

        if (array_intersect($userRoles, ['divisional_director', 'head_of_department', 'ict_director', 'head_of_it', 'ict_officer'])) {
            $abilities = array_merge($abilities, [
                'approver-access',
                'review-requests',
                'approve-requests',
                'view-department-data',
            ]);
        }

        if (array_intersect($userRoles, ['staff'])) {
            $abilities = array_merge($abilities, [
                'staff-access',
                'create-requests',
                'view-own-requests',
            ]);
        }

        return array_unique($abilities);
    }

    /**
     * Normalize phone numbers to +2557... format for lookups.
     * Accepts inputs like 07..., 7..., 2557..., +2557... and
     * returns a unified +2557XXXXXXXX format where possible.
     */
    private function normalizePhone(string $phone): string
    {
        // Remove everything except digits
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return $phone;
        }

        // If starts with 0 (e.g. 07...), drop the leading zero
        if ($digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        // If starts with 255 already, just ensure + prefix
        if (strpos($digits, '255') === 0) {
            return '+'.$digits;
        }

        // If now looks like 7XXXXXXXX (9 digits starting with 7), treat as local TZ mobile
        if (strlen($digits) === 9 && $digits[0] === '7') {
            return '+255'.$digits;
        }

        // Fallback: prefix + and return digits as-is
        return '+'.$digits;
    }
}
