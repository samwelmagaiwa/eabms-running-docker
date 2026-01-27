<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\SmsLog;
use App\Models\UserAccess;
use App\Models\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Display a listing of users with their roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'sometimes|string|max:255',
                'role' => 'sometimes|string|max:100',
                'status' => 'sometimes|in:active,inactive,all',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'sort_by' => 'sometimes|in:name,email,created_at,updated_at',
                'sort_order' => 'sometimes|in:asc,desc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build query - Include ALL users including current admin
            $query = User::with(['roles', 'department', 'departmentsAsHOD', 'onboarding']);

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('pf_number', 'like', "%{$search}%");
                });
            }

            // Apply role filter
            if ($request->has('role') && $request->role) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Apply status filter
            if ($request->has('status') && $request->status !== 'all') {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            // Only exclude super admin users if current user is not super admin
            if (!$request->user()->hasRole('super_admin')) {
                $query->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'super_admin');
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $users = $query->paginate($perPage);

            // Transform the data
            $transformedUsers = $users->getCollection()->map(function ($user) {
                $onboarding = $user->onboarding;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'phone' => $user->phone,
                    'department_id' => $user->department_id,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                        'code' => $user->department->code,
                        'display_name' => $user->department->getFullNameAttribute()
                    ] : null,
                    'is_active' => $user->is_active ?? true,
                    'profile_photo_url' => $user->profile_photo_url,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->getDisplayName(),
                            'assigned_at' => $role->pivot->assigned_at,
                        ];
                    }),
                    'role_names' => $user->roles->pluck('name')->toArray(),
                    'display_roles' => $user->getDisplayRoleNames(),
                    'departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                        ];
                    }),
                    'is_hod' => $user->departmentsAsHOD->isNotEmpty(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'onboarding_status' => [
                        'needs_onboarding' => $user->needsOnboarding(),
                        'completed' => $onboarding ? $onboarding->completed : false,
                        'current_step' => $onboarding ? $onboarding->current_step : 'terms-popup'
                    ]
                ];
            });

            Log::info('Admin retrieved users list', [
                'admin_id' => $request->user()->id,
                'total_users' => $users->total(),
                'search' => $request->search,
                'role_filter' => $request->role,
                'status_filter' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $transformedUsers,
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving users for admin', [
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users'
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'pf_number' => 'nullable|string|max:50|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'boolean'
        ], [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'pf_number.unique' => 'This PF number is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
            'role_ids.required' => 'At least one role must be assigned.',
            'role_ids.array' => 'Roles must be provided as an array.',
            'role_ids.min' => 'At least one role must be assigned.',
            'role_ids.*.exists' => 'One or more selected roles do not exist.',
            'department_id.exists' => 'Selected department does not exist.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Additional validation for privilege escalation
        $currentUser = $request->user();
        $roleIds = $request->input('role_ids', []);

        if (!$currentUser->isAdmin()) {
            $adminRoles = Role::whereIn('name', ['admin'])->pluck('id')->toArray();
            $hasAdminRole = !empty(array_intersect($roleIds, $adminRoles));
            
            if ($hasAdminRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'role_ids' => ['You cannot assign admin roles.']
                    ]
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'pf_number' => $request->pf_number,
                'department_id' => $request->department_id,
                'password' => Hash::make($request->password),
                'is_active' => $request->get('is_active', true),
            ]);

            // Assign roles
            $roleData = [];
            foreach ($request->role_ids as $roleId) {
                $roleData[$roleId] = [
                    'assigned_at' => now(),
                    'assigned_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            $user->roles()->attach($roleData);

            // Log role assignments
            foreach ($request->role_ids as $roleId) {
                \App\Models\RoleChangeLog::create([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'action' => 'assigned',
                    'changed_by' => $request->user()->id,
                    'changed_at' => now(),
                    'metadata' => [
                        'user_email' => $user->email,
                        'changed_by_email' => $request->user()->email,
                        'context' => 'user_creation'
                    ]
                ]);
            }

            DB::commit();

            // Load relationships for response
            $user->load('roles', 'department', 'departmentsAsHOD');

            Log::info('User created by admin', [
                'admin_id' => $request->user()->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_roles' => $request->role_ids,
                'department_id' => $request->department_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'pf_number' => $user->pf_number,
                        'phone' => $user->phone,
                        'department_id' => $user->department_id,
                        'department' => $user->department ? [
                            'id' => $user->department->id,
                            'name' => $user->department->name,
                            'code' => $user->department->code,
                            'display_name' => $user->department->getFullNameAttribute()
                        ] : null,
                        'is_active' => $user->is_active,
                        'profile_photo_url' => $user->profile_photo_url,
                        'roles' => $user->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                                'assigned_at' => $role->pivot->assigned_at,
                            ];
                        }),
                        'display_roles' => $user->getDisplayRoleNames(),
                        'created_at' => $user->created_at
                    ]
                ],
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user', [
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user'
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::with(['roles', 'department', 'departmentsAsHOD', 'onboarding', 'roleHistory.role', 'roleHistory.changedBy'])
                ->find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Don't allow viewing other super admin users
            if ($user->hasRole('super_admin') && $user->id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot view super admin users'
                ], 403);
            }

            $onboarding = $user->onboarding;

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'pf_number' => $user->pf_number,
                'phone' => $user->phone,
                'department_id' => $user->department_id,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                    'display_name' => $user->department->getFullNameAttribute()
                ] : null,
                'is_active' => $user->is_active ?? true,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                        'description' => $role->description,
                        'assigned_at' => $role->pivot->assigned_at,
                        'assigned_by' => $role->pivot->assigned_by,
                    ];
                }),
                'display_roles' => $user->getDisplayRoleNames(),
                'departments_as_hod' => $user->departmentsAsHOD,
                'permissions' => $user->getAllPermissions(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'onboarding_status' => [
                    'needs_onboarding' => $user->needsOnboarding(),
                    'terms_accepted' => $onboarding ? $onboarding->terms_accepted : false,
                    'ict_policy_accepted' => $onboarding ? $onboarding->ict_policy_accepted : false,
                    'declaration_submitted' => $onboarding ? $onboarding->declaration_submitted : false,
                    'completed' => $onboarding ? $onboarding->completed : false,
                    'current_step' => $onboarding ? $onboarding->current_step : 'terms-popup'
                ],
                'role_history' => $user->roleHistory->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'role_name' => $log->role ? $log->role->name : 'Unknown',
                        'changed_by' => $log->changedBy ? $log->changedBy->name : 'System',
                        'changed_at' => $log->changed_at,
                        'metadata' => $log->metadata
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user details', [
                'admin_id' => $request->user()->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details'
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Track previous active status so we can detect lock events
        $previousIsActive = $user->is_active ?? true;

        // Prevent editing super admin users
        if ($user->hasRole('super_admin') && $user->id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit super admin users'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => 'nullable|string|max:20',
            'pf_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users')->ignore($userId)],
            'department_id' => 'nullable|exists:departments,id',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updateData = $request->only(['name', 'email', 'phone', 'pf_number', 'department_id', 'is_active']);
            
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // If the user was active and has just been locked (is_active explicitly set to false),
            // revoke all active Sanctum tokens so they are logged out from all sessions.
            $becameInactive = array_key_exists('is_active', $updateData)
                && $previousIsActive !== false
                && $updateData['is_active'] === false;

            if ($becameInactive) {
                $tokenCount = $user->tokens()->count();
                $user->tokens()->delete();

                Log::info('User tokens revoked due to account lock by admin', [
                    'admin_id' => $request->user()->id,
                    'user_id' => $user->id,
                    'revoked_tokens' => $tokenCount,
                ]);
            }

            DB::commit();

            Log::info('User updated by admin', [
                'admin_id' => $request->user()->id,
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);

            // Load relationships for response
            $user->load('roles', 'department', 'departmentsAsHOD');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'pf_number' => $user->pf_number,
                        'phone' => $user->phone,
                        'department_id' => $user->department_id,
                        'department' => $user->department ? [
                            'id' => $user->department->id,
                            'name' => $user->department->name,
                            'code' => $user->department->code,
                            'display_name' => $user->department->getFullNameAttribute()
                        ] : null,
                        'is_active' => $user->is_active,
                        'roles' => $user->roles,
                        'display_roles' => $user->getDisplayRoleNames(),
                        'updated_at' => $user->updated_at
                    ]
                ],
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user', [
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user'
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deleting super admin users
        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete super admin users'
            ], 403);
        }

        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $userName = $user->name;
            $userEmail = $user->email;

            // Remove role assignments (will be handled by cascade)
            $user->roles()->detach();

            // Remove HOD assignments
            Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);

            // Delete user
            $user->delete();

            DB::commit();

            Log::info('User deleted by admin', [
                'admin_id' => $request->user()->id,
                'deleted_user_id' => $userId,
                'deleted_user_name' => $userName,
                'deleted_user_email' => $userEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user', [
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user'
            ], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deactivating super admin users
        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate super admin users'
            ], 403);
        }

        // Prevent self-deactivation
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate your own account'
            ], 403);
        }

        try {
            $newStatus = !($user->is_active ?? true);
            $user->update(['is_active' => $newStatus]);

            // When toggling to inactive, revoke all Sanctum tokens so the user
            // is logged out from all active sessions immediately.
            if ($newStatus === false) {
                $tokenCount = $user->tokens()->count();
                $user->tokens()->delete();

                Log::info('User tokens revoked due to status toggle to inactive', [
                    'admin_id' => $request->user()->id,
                    'user_id' => $userId,
                    'revoked_tokens' => $tokenCount,
                ]);
            }

            Log::info('User status toggled by admin', [
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'new_status' => $newStatus ? 'active' : 'inactive'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'is_active' => $user->is_active,
                    'status' => $user->is_active ? 'active' : 'inactive'
                ],
                'message' => 'User status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling user status', [
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status'
            ], 500);
        }
    }

    /**
     * Get available roles for user assignment
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'display_name', 'description', 'is_system_role']);

            $transformedRoles = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->getDisplayName(),
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedRoles
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving roles', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles'
            ], 500);
        }
    }

    /**
     * Get available departments for user assignment
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = Department::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description']);

            $transformedDepartments = $departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'display_name' => $department->name . ($department->code ? " ({$department->code})" : '')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedDepartments
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving departments', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve departments'
            ], 500);
        }
    }

    /**
     * Get form data for creating a new user
     */
    public function getCreateFormData(): JsonResponse
    {
        try {
            // Get roles
            $roles = Role::orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'display_name', 'description', 'is_system_role']);

            $transformedRoles = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->getDisplayName(),
                    'description' => $role->description,
                    'is_system_role' => $role->is_system_role
                ];
            });

            // Get departments
            $departments = Department::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description']);

            $transformedDepartments = $departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'display_name' => $department->name . ($department->code ? " ({$department->code})" : '')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $transformedRoles,
                    'departments' => $transformedDepartments
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving form data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form data'
            ], 500);
        }
    }

    /**
     * Get comprehensive admin dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            Log::info('Admin dashboard stats requested');
            
            // Get total users count (excluding super admins for cleaner stats)
            $totalUsers = User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->count();
            
            // Initialize counters
            $totalRequests = 0;
            $pendingRequests = 0;
            $completedRequests = 0;
            $todaysRequests = 0;
            
            // Try to get UserAccess statistics safely
            try {
                $userAccessCount = UserAccess::count();
                $totalRequests += $userAccessCount;
                
                $pendingUserAccess = UserAccess::where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere('hod_status', 'pending')
                        ->orWhere('divisional_status', 'pending')
                        ->orWhere('ict_director_status', 'pending')
                        ->orWhere('head_it_status', 'pending')
                        ->orWhere('ict_officer_status', 'pending');
                })->count();
                $pendingRequests += $pendingUserAccess;
                
                $completedUserAccess = UserAccess::where('status', 'completed')->count();
                $completedRequests += $completedUserAccess;
                
                $todayUserAccess = UserAccess::whereDate('created_at', today())->count();
                $todaysRequests += $todayUserAccess;
                
                Log::info('UserAccess stats retrieved', [
                    'total' => $userAccessCount,
                    'pending' => $pendingUserAccess,
                    'completed' => $completedUserAccess,
                    'today' => $todayUserAccess
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to get UserAccess statistics', ['error' => $e->getMessage()]);
            }
            
            // Try to get BookingService statistics safely
            try {
                $bookingCount = BookingService::count();
                $totalRequests += $bookingCount;
                
                $pendingBookings = BookingService::whereIn('status', [
                    'pending', 'ict_approval_pending'
                ])->count();
                $pendingRequests += $pendingBookings;
                
                $completedBookings = BookingService::where('status', 'completed')->count();
                $completedRequests += $completedBookings;
                
                $todayBookings = BookingService::whereDate('created_at', today())->count();
                $todaysRequests += $todayBookings;
                
                Log::info('BookingService stats retrieved', [
                    'total' => $bookingCount,
                    'pending' => $pendingBookings,
                    'completed' => $completedBookings,
                    'today' => $todayBookings
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to get BookingService statistics', ['error' => $e->getMessage()]);
            }
            
            // Get active users (users active in last 30 days)
            $activeUsers = User::where('updated_at', '>=', now()->subDays(30))
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'super_admin');
                })->count();
            
            // Calculate completion rate
            $completionRate = $totalRequests > 0 
                ? round(($completedRequests / $totalRequests) * 100, 1) 
                : 0;

            // SMS statistics (from sms_logs table with optional date filters)
            $totalSmsSent = 0;
            $smsFromDate = request()->query('sms_from_date');
            $smsToDate = request()->query('sms_to_date');
            $firstSmsDate = null;

            try {
                $smsQuery = SmsLog::successful();

                // Determine the date of the very first successful SMS
                $firstSms = SmsLog::successful()
                    ->orderBy('created_at', 'asc')
                    ->value('created_at');
                if ($firstSms) {
                    $firstSmsDate = Carbon::parse($firstSms)->toDateString();
                }

                if (!empty($smsFromDate)) {
                    $from = Carbon::parse($smsFromDate)->startOfDay();
                    $smsQuery->where('created_at', '>=', $from);
                }

                if (!empty($smsToDate)) {
                    $to = Carbon::parse($smsToDate)->endOfDay();
                    $smsQuery->where('created_at', '<=', $to);
                }

                $totalSmsSent = $smsQuery->count();

                Log::info('SmsLog stats retrieved for admin dashboard', [
                    'total_sms_sent' => $totalSmsSent,
                    'from' => $smsFromDate,
                    'to' => $smsToDate,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to get SmsLog statistics', ['error' => $e->getMessage()]);
            }
            
            $stats = [
                'total_users' => $totalUsers,
                'total_requests' => $totalRequests,
                'pending_requests' => $pendingRequests,
                'active_users' => $activeUsers,
                'todays_requests' => $todaysRequests,
                'completed_requests' => $completedRequests,
                'completion_rate' => $completionRate,
                'total_sms_sent' => $totalSmsSent,
                'sms_from_date' => $smsFromDate,
                'sms_to_date' => $smsToDate,
                'sms_first_date' => $firstSmsDate,
                'generated_at' => now()->toISOString()
            ];
            
            Log::info('Admin dashboard stats generated successfully', $stats);
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving admin dashboard statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'users_with_roles' => User::has('roles')->count(),
                'users_without_roles' => User::doesntHave('roles')->count(),
                'hod_users' => User::has('departmentsAsHOD')->count(),
                'users_needing_onboarding' => User::whereDoesntHave('onboarding', function ($q) {
                    $q->where('completed', true);
                })->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                })->count(),
                'role_distribution' => Role::withCount('users')->get()->map(function ($role) {
                    return [
                        'role_name' => $role->name,
                        'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                        'user_count' => $role->users_count,
                    ];
                }),
                'department_distribution' => Department::withCount('users')->get()->map(function ($department) {
                    return [
                        'department_name' => $department->name,
                        'department_code' => $department->code,
                        'user_count' => $department->users_count,
                    ];
                }),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }
}