<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserOnboarding;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Handle user login and return an authentication token.
     * Supports multiple concurrent sessions per user.
     *
     * @OA\Post(
     *     path="/api/login",
     *     summary="User Login",
     *     description="Authenticate user and return access token",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email", "password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="user@example.com",
     *                     description="User email address"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     minLength=6,
     *                     example="password123",
     *                     description="User password (minimum 6 characters)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="pf_number", type="string", example="PF12345"),
     *                 @OA\Property(property="role", type="string", example="staff"),
     *                 @OA\Property(property="primary_role", type="string", example="staff"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"staff"}),
     *                 @OA\Property(property="needs_onboarding", type="boolean", example=false),
     *                 @OA\Property(property="onboarding_step", type="string", example="completed")
     *             ),
     *             @OA\Property(property="token", type="string", description="Laravel Sanctum plain text token for API authentication", example="1|abcdef123456789abcdef123456789abcdef123456789abcdef"),
     *             @OA\Property(property="token_name", type="string", example="STAFF_Chrome_192.168.1.1_2024-01-01 12:00:00"),
     *             @OA\Property(
     *                 property="session_info",
     *                 type="object",
     *                 @OA\Property(property="ip_address", type="string", example="192.168.1.1"),
     *                 @OA\Property(property="user_agent", type="string", example="Mozilla/5.0..."),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid email or password.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="The email field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="array",
     *                     @OA\Items(type="string", example="The password field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $logData = $request->except(['password', 'password_confirmation']);
        Log::info('login() called', ['request' => $logData]);
        
        // Validate incoming request data
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        
        $credentials = $request->only('email', 'password');
        $userAgent = $request->header('User-Agent', 'Unknown');
        $ipAddress = $request->ip();

        Log::info('Login attempt for email: ' . $request->email, [
            'ip' => $ipAddress,
            'user_agent' => $userAgent
        ]);
        
        // Find user by email with eager loading to reduce queries
        $user = User::with(['roles', 'onboarding', 'department'])
                   ->where('email', $request->email)
                   ->first();
        
        if (!$user) {
            Log::warning('User not found in database: ' . $request->email);
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }
        
        Log::info('User found in database', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'primary_role_name' => $user->getPrimaryRoleName(),
            'many_to_many_roles' => $user->roles()->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()
        ]);
        
        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            Log::warning('Password verification failed for user: ' . $request->email);
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }
        
        Log::info('Password verification successful for user: ' . $request->email);
        
        // Block login for inactive/locked users
        // We only treat an explicit false as locked so that null remains backwards-compatible
        if ($user->is_active === false) {
            Log::warning('Login blocked for inactive user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Your account has been locked. Please contact the system administrator.'
            ], 403);
        }
        
        // Relationships already loaded with eager loading above
        
        // Create a unique token name for this session
        $tokenName = $this->generateTokenName($user, $userAgent, $ipAddress);
        
        // Create token with abilities based on user role
        $abilities = $this->getTokenAbilities($user);
        $token = $user->createToken($tokenName, array_values($abilities))->plainTextToken;
        
        Log::info('Token created successfully', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'token_name' => $tokenName,
            'abilities' => $abilities,
            'token_length' => strlen($token)
        ]);
        
        // Get onboarding status
        $onboarding = $user->getOrCreateOnboarding();
        
        // Get primary role for consistent role handling
        $primaryRole = $user->getPrimaryRoleName();
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        $responseData = [
            'user'  => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'pf_number' => $user->pf_number,
                'profile_photo_url' => $user->profile_photo_url,
                // Department information for auto-populating booking forms and other UIs
                'department_id' => $user->department_id,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                ] : null,
                'role_id' => null,
                'role' => $primaryRole, // Normalized role field
                'role_name' => $primaryRole, // For backward compatibility
                'primary_role' => $primaryRole, // Explicit primary role
                'roles' => $userRoles,
                'permissions' => $user->getAllPermissions(),
                'needs_onboarding' => $user->needsOnboarding(),
                'onboarding_step' => $onboarding->current_step,
                'must_change_password' => (bool) ($user->must_change_password ?? false),
            ],
            'token' => $token,
            'token_name' => $tokenName,
            'session_info' => [
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => Carbon::now()->toISOString()
            ]
        ];
        
        Log::info('Login successful, returning response', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'role_name' => $user->getPrimaryRoleName(),
            'roles' => $user->roles()->pluck('name')->toArray(),
            'token_name' => $tokenName
        ]);
        
        // Return user data and token, including role name and onboarding status
        return response()->json($responseData, 200);
    }
    
    /**
     * Generate a unique token name for this session
     */
    private function generateTokenName(User $user, string $userAgent, string $ipAddress): string
    {
        $browser = $this->getBrowserName($userAgent);
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $rolePrefix = $user->getPrimaryRoleName() ? strtoupper($user->getPrimaryRoleName()) : 'USER';
        
        return "{$rolePrefix}_{$browser}_{$ipAddress}_{$timestamp}";
    }
    
    /**
     * Extract browser name from user agent
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
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Get token abilities based on user role
     */
    private function getTokenAbilities(User $user): array
    {
        $baseAbilities = ['*'];  // Grant all abilities by default
        
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        if (empty($userRoles)) {
            return $baseAbilities;
        }
        
        $abilities = $baseAbilities;
        
        // Add abilities based on roles
        if (array_intersect($userRoles, ['admin'])) {
            $abilities = array_merge($abilities, [
                'admin-access',
                'manage-users',
                'manage-requests',
                'view-all-data',
                'system-settings'
            ]);
        }
        
        if (array_intersect($userRoles, ['divisional_director', 'head_of_department', 'ict_director', 'head_of_it', 'ict_officer'])) {
            $abilities = array_merge($abilities, [
                'approver-access',
                'review-requests',
                'approve-requests',
                'view-department-data'
            ]);
        }
        
        if (array_intersect($userRoles, ['staff'])) {
            $abilities = array_merge($abilities, [
                'staff-access',
                'create-requests',
                'view-own-requests'
            ]);
        }
        
        return array_unique($abilities);
    }

    /**
     * Logout user by revoking the current token.
     * Only affects the current session/tab.
     *
     * @OA\Post(
     *     path="/api/logout",
     *     summary="User Logout",
     *     description="Logout user from current session by revoking the current token",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={"sanctum": {}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="session_ended", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No authenticated user or token found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Logout failed")
     *         )
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();
            
            Log::info('logout called', [
                'user_id' => $user ? $user->id : null,
                'token_name' => $currentToken ? $currentToken->name : null
            ]);
            
            if ($user && $currentToken) {
                // Delete only the current access token (this session)
                $currentToken->delete();
                
                Log::info('User logged out successfully', [
                    'user_id' => $user->id,
                    'token_name' => $currentToken->name,
                    'remaining_tokens' => $user->tokens()->count()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Logged out successfully',
                    'session_ended' => true
                ], 200);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No authenticated user or token found'
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }
    
    /**
     * Logout from all sessions by revoking all tokens.
     *
     * @OA\Post(
     *     path="/api/logout-all",
     *     summary="Logout from All Sessions",
     *     description="Logout user from all sessions by revoking all tokens",
     *     operationId="logoutAll",
     *     tags={"Authentication"},
     *     security={"sanctum": {}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout from all sessions successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out from all sessions successfully"),
     *             @OA\Property(property="tokens_revoked", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No authenticated user found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Logout from all sessions failed")
     *         )
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('logoutAll called', ['user_id' => $user ? $user->id : null]);
            
            if ($user) {
                $tokenCount = $user->tokens()->count();
                
                // Delete all access tokens for this user
                $user->tokens()->delete();
                
                Log::info('User logged out from all sessions', [
                    'user_id' => $user->id,
                    'tokens_revoked' => $tokenCount
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Logged out from all sessions successfully',
                    'tokens_revoked' => $tokenCount
                ], 200);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No authenticated user found'
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('LogoutAll error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Logout from all sessions failed'
            ], 500);
        }
    }
    
    /**
     * Get active sessions for the current user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSessions(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authenticated user found'
                ], 401);
            }
            
            $sessions = $user->tokens()->select('id', 'name', 'created_at', 'last_used_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($token) use ($request) {
                    $parts = explode('_', $token->name);
                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'role' => $parts[0] ?? 'Unknown',
                        'browser' => $parts[1] ?? 'Unknown',
                        'ip_address' => $parts[2] ?? 'Unknown',
                        'created_at' => $token->created_at,
                        'last_used_at' => $token->last_used_at,
                        'is_current' => $token->id === $request->user()->currentAccessToken()->id
                    ];
                });
            
            return response()->json([
                'success' => true,
                'sessions' => $sessions,
                'total_sessions' => $sessions->count()
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('GetActiveSessions error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active sessions'
            ], 500);
        }
    }
    
    /**
     * Revoke a specific session by token ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeSession(Request $request)
    {
        try {
            $request->validate([
                'token_id' => 'required|integer'
            ]);
            
            $user = $request->user();
            $tokenId = $request->token_id;
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authenticated user found'
                ], 401);
            }
            
            $token = $user->tokens()->where('id', $tokenId)->first();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }
            
            $tokenName = $token->name;
            $token->delete();
            
            Log::info('Session revoked', [
                'user_id' => $user->id,
                'token_id' => $tokenId,
                'token_name' => $tokenName
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Session revoked successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('RevokeSession error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke session'
            ], 500);
        }
    }

    /**
     * Get current authenticated user with fresh data.
     * Used for token verification and session restoration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentUser(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authenticated user found'
                ], 401);
            }
            
            // Refresh user data with relationships
            $user->load(['roles', 'onboarding']);
            
            // Get onboarding status
            $onboarding = $user->getOrCreateOnboarding();
            
            // Get primary role for consistent role handling
            $primaryRole = $user->getPrimaryRoleName();
            $userRoles = $user->roles->pluck('name')->toArray(); // Use loaded relationship

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'pf_number' => $user->pf_number,
                'profile_photo_url' => $user->profile_photo_url,
                'role_id' => null,
                'role' => $primaryRole, // Normalized role field
                'role_name' => $primaryRole, // For backward compatibility
                'primary_role' => $primaryRole, // Explicit primary role
                'roles' => $userRoles, // Array of role names
                'permissions' => $user->getAllPermissions(),
                'needs_onboarding' => $user->needsOnboarding(),
                'onboarding_step' => $onboarding->current_step,
                'must_change_password' => (bool) ($user->must_change_password ?? false),
            ];
            
            Log::info('getCurrentUser called', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'role_name' => $user->getPrimaryRoleName(),
                'roles' => $user->roles()->pluck('name')->toArray()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $userData
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('getCurrentUser error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data'
            ], 500);
        }
    }

/**
     * Change password for the currently authenticated user.
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authenticated user found'
                ], 401);
            }

            // Validate using frontend field names
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'errors' => [
                        'current_password' => ['The current password you entered is incorrect.'],
                    ],
                ], 422);
            }

        // Update password and clear must_change_password flag if present
        $user->password = Hash::make($validated['password']);
        if (isset($user->must_change_password)) {
            $user->must_change_password = false;
        }
        $user->save();

        // Revoke all tokens so user must log in again with the new password
        $revokedTokens = $user->tokens()->count();
        $user->tokens()->delete();

        Log::info('Password changed successfully and tokens revoked', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tokens_revoked' => $revokedTokens,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please log in again.',
            'logged_out' => true,
            'tokens_revoked' => $revokedTokens,
        ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('changePassword error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password.',
            ], 500);
        }
    }

    /**
     * Get role-based redirect URL for authenticated user.
     * Used by frontend to determine where to redirect after login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoleBasedRedirect(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authenticated user found'
                ], 401);
            }
            
            $primaryRole = $user->getPrimaryRoleName();
            
            // Define role-based redirects
            $redirectMap = [
                'admin' => '/admin-dashboard',
                'head_of_department' => '/hod-dashboard',
                'divisional_director' => '/divisional-dashboard',
                'ict_director' => '/dict-dashboard',
                'head_of_it' => '/head-it-dashboard',
                'ict_officer' => '/ict-dashboard',
                'secretary_ict' => '/secretary-approval/requests',
                'staff' => '/user-dashboard'
            ];
            
            $redirectUrl = $redirectMap[$primaryRole] ?? '/user-dashboard';
            
            // Check if user needs onboarding (except admin)
            if ($primaryRole !== 'admin' && $user->needsOnboarding()) {
                $redirectUrl = '/onboarding';
            }
            
            Log::info('getRoleBasedRedirect called', [
                'user_id' => $user->id,
                'role' => $primaryRole,
                'redirect_url' => $redirectUrl,
                'needs_onboarding' => $user->needsOnboarding()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'redirect_url' => $redirectUrl,
                    'role' => $primaryRole,
                    'needs_onboarding' => $user->needsOnboarding()
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('getRoleBasedRedirect error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to determine redirect URL'
            ], 500);
        }
    }
}