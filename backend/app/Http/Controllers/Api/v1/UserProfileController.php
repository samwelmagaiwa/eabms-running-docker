<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Department;

class UserProfileController extends Controller
{
    /**
     * Get current user's profile information for form auto-population
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentUserProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Load department relationship
            $user->load('department');

            // Prepare profile data for form auto-population
            $profileData = [
                'id' => $user->id,
                'pf_number' => $user->pf_number,
                'staff_name' => $user->name, // Use name as staff_name since staff_name column doesn't exist
                'full_name' => $user->name,
                'phone' => $user->phone,
                'phone_number' => $user->phone, // Alias for compatibility
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                    'full_name' => $user->department->getFullNameAttribute(),
                    'display_name' => $user->department->getFullNameAttribute()
                ] : null,
                'is_active' => $user->is_active ?? true,
                'primary_role' => $user->getPrimaryRoleName(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ];

            Log::info('User profile retrieved for auto-population', [
                'user_id' => $user->id,
                'pf_number' => $profileData['pf_number'],
                'has_staff_name' => !empty($profileData['staff_name']),
                'has_phone' => !empty($profileData['phone']),
                'has_department' => !empty($profileData['department'])
            ]);

            return response()->json([
                'success' => true,
                'data' => $profileData,
                'message' => 'User profile retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user profile by PF Number (for admin/HOD use)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserByPfNumber(Request $request)
    {
        try {
            $request->validate([
                'pf_number' => 'required|string|max:20'
            ]);

            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check if current user has permission to look up other users
            if (!$currentUser->hasAnyRole(['admin', 'head_of_department', 'divisional_director', 'ict_director', 'ict_officer'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to look up user by PF Number'
                ], 403);
            }

            $user = User::where('pf_number', $request->pf_number)
                ->where('is_active', true)
                ->with('department')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active user found with the provided PF Number',
                    'data' => null
                ], 404);
            }

            // Prepare user data
            $userData = [
                'id' => $user->id,
                'pf_number' => $user->pf_number,
                'staff_name' => $user->name,
                'full_name' => $user->name,
                'phone' => $user->phone,
                'phone_number' => $user->phone,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                    'full_name' => $user->department->getFullNameAttribute(),
                    'display_name' => $user->department->getFullNameAttribute()
                ] : null,
                'primary_role' => $user->getPrimaryRoleName(),
                'is_active' => $user->is_active
            ];

            Log::info('User looked up by PF Number', [
                'looked_up_user_id' => $user->id,
                'looked_up_by' => $currentUser->id,
                'pf_number' => $request->pf_number
            ]);

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'User found and retrieved successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error looking up user by PF Number: ' . $e->getMessage(), [
                'pf_number' => $request->pf_number ?? null,
                'current_user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to look up user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check if PF Number exists and is active
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPfNumberExists(Request $request)
    {
        try {
            $request->validate([
                'pf_number' => 'required|string|max:20'
            ]);

            $exists = User::where('pf_number', $request->pf_number)
                ->where('is_active', true)
                ->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'pf_number' => $request->pf_number,
                'message' => $exists ? 'PF Number exists' : 'PF Number not found'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid PF Number format',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error checking PF Number existence: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check PF Number',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get departments list for form dropdowns
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartments(Request $request)
    {
        try {
            $departments = Department::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(function ($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                        'code' => $department->code,
                        'display_name' => $department->getFullNameAttribute(),
                        'full_name' => $department->getFullNameAttribute()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $departments,
                'count' => $departments->count(),
                'message' => 'Departments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving departments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve departments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update current user's profile information
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCurrentUserProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->validate([
                'phone' => 'sometimes|string|max:20',
                'department_id' => 'sometimes|integer|exists:departments,id'
            ]);

            $updateData = [];
            
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            
            if ($request->has('department_id')) {
                $updateData['department_id'] = $request->department_id;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
                
                Log::info('User profile updated', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($updateData)
                ]);
            }

            // Return updated profile
            $user->refresh();
            $user->load('department');

            $profileData = [
                'id' => $user->id,
                'pf_number' => $user->pf_number,
                'staff_name' => $user->name,
                'full_name' => $user->name,
                'phone' => $user->phone,
                'phone_number' => $user->phone,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                    'full_name' => $user->department->getFullNameAttribute(),
                    'display_name' => $user->department->getFullNameAttribute()
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $profileData,
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Upload and update the current user's avatar image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $file = $request->file('avatar');

            // Delete old avatar if it exists
            if ($user->profile_photo_path) {
                try {
                    Storage::disk('public')->delete($user->profile_photo_path);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old profile photo', [
                        'user_id' => $user->id,
                        'path' => $user->profile_photo_path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Store new avatar
            $path = $file->store('avatars', 'public');
            $user->profile_photo_path = $path;
            $user->save();

            $profilePhotoUrl = rtrim(config('app.url'), '/') . '/storage/' . $path;

            Log::info('User avatar updated', [
                'user_id' => $user->id,
                'path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'data' => [
                    'profile_photo_url' => $profilePhotoUrl,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image upload data',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading user avatar: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
