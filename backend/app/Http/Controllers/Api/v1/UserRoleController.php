<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRoleRequest;
use App\Models\User;
use App\Models\Role;
use App\Models\RoleChangeLog;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Display users with their roles.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'department', 'departmentsAsHOD']);

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('pf_number', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role') && $request->role) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Filter by department HOD
            if ($request->has('is_hod') && $request->boolean('is_hod')) {
                $query->has('departmentsAsHOD');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'email', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('name', 'asc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            // Transform the data to include role information
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'department_id' => $user->department_id,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                        'code' => $user->department->code,
                        'display_name' => $user->department->getFullNameAttribute(),
                    ] : null,
                    'created_at' => $user->created_at,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->getDisplayName(),
                            'description' => $role->description,
                            'assigned_at' => $role->pivot->assigned_at,
                            'assigned_by' => $role->pivot->assigned_by,
                        ];
                    }),
                    'departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                        ];
                    }),
                    'is_hod' => $user->departmentsAsHOD->isNotEmpty(),
                    'primary_role' => $user->getPrimaryRole()?->name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Users retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(AssignRoleRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $roleIds = $request->validated()['role_ids'];
            $currentUser = $request->user();

            // Get current roles for comparison
            $currentRoleIds = $user->roles()->pluck('role_id')->toArray();
            
            // Determine which roles to add and remove
            $rolesToAdd = array_diff($roleIds, $currentRoleIds);
            $rolesToRemove = array_diff($currentRoleIds, $roleIds);

            // Remove roles that are no longer assigned
            if (!empty($rolesToRemove)) {
                $user->roles()->detach($rolesToRemove);
                
                // Log role removals
                foreach ($rolesToRemove as $roleId) {
                    RoleChangeLog::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'action' => 'removed',
                        'changed_by' => $currentUser->id,
                        'changed_at' => now(),
                        'metadata' => [
                            'user_email' => $user->email,
                            'changed_by_email' => $currentUser->email,
                        ]
                    ]);
                }
            }

            // Add new roles
            if (!empty($rolesToAdd)) {
                $roleData = [];
                foreach ($rolesToAdd as $roleId) {
                    $roleData[$roleId] = [
                        'assigned_at' => now(),
                        'assigned_by' => $currentUser->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                $user->roles()->attach($roleData);
                
                // Log role assignments
                foreach ($rolesToAdd as $roleId) {
                    RoleChangeLog::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'action' => 'assigned',
                        'changed_by' => $currentUser->id,
                        'changed_at' => now(),
                        'metadata' => [
                            'user_email' => $user->email,
                            'changed_by_email' => $currentUser->email,
                        ]
                    ]);
                }
            }

            // Log the overall change
            Log::info('User roles updated', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'roles_added' => $rolesToAdd,
                'roles_removed' => $rolesToRemove,
                'updated_by' => $currentUser->id,
                'new_primary_role' => $user->getPrimaryRoleName()
            ]);

            DB::commit();

            // Return updated user with roles
            $user->load('roles', 'departmentsAsHOD');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'changes' => [
                        'added' => Role::whereIn('id', $rolesToAdd)->pluck('name')->toArray(),
                        'removed' => Role::whereIn('id', $rolesToRemove)->pluck('name')->toArray(),
                    ]
                ],
                'message' => 'User roles updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove a specific role from a user.
     */
    public function removeRole(Request $request, User $user, Role $role): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $currentUser = $request->user();

            // Check if user has this role
            if (!$user->roles()->where('role_id', $role->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have this role.'
                ], 422);
            }

            // Prevent removing admin role from self
            if ($user->id === $currentUser->id && $role->name === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot remove admin role from yourself.'
                ], 422);
            }

            // Remove the role
            $user->roles()->detach($role->id);

            // Log the change
            RoleChangeLog::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'action' => 'removed',
                'changed_by' => $currentUser->id,
                'changed_at' => now(),
                'metadata' => [
                    'user_email' => $user->email,
                    'role_name' => $role->name,
                    'changed_by_email' => $currentUser->email,
                ]
            ]);

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'removed_by' => $currentUser->id,
                'new_primary_role' => $user->getPrimaryRoleName()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' removed from user successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's role history.
     */
    public function roleHistory(User $user): JsonResponse
    {
        try {
            $history = RoleChangeLog::where('user_id', $user->id)
                ->with(['role', 'changedBy'])
                ->orderBy('changed_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'User role history retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving role history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role history.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get role assignment statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'users_with_roles' => User::has('roles')->count(),
                'users_without_roles' => User::doesntHave('roles')->count(),
                'hod_users' => User::has('departmentsAsHOD')->count(),
                'recent_role_changes' => RoleChangeLog::recent(7)->count(),
                'role_distribution' => Role::withCount('users')->get()->map(function ($role) {
                    return [
                        'role_name' => $role->name,
                        'user_count' => $role->users_count,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'User role statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user role statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all departments for dropdown selection.
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = Department::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description']);

            return response()->json([
                'success' => true,
                'data' => $departments,
                'message' => 'Departments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving departments: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve departments.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new user with roles and department assignment.
     */
    public function createUser(Request $request): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'pf_number' => 'nullable|string|max:50|unique:users,pf_number',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        DB::beginTransaction();
        
        try {
            $currentUser = $request->user();

            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'pf_number' => $validated['pf_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'is_active' => true,
            ]);

            // Assign roles if provided
            if (!empty($validated['role_ids'])) {
                $roleData = [];
                foreach ($validated['role_ids'] as $roleId) {
                    $roleData[$roleId] = [
                        'assigned_at' => now(),
                        'assigned_by' => $currentUser->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                $user->roles()->attach($roleData);
                
                // Log role assignments
                foreach ($validated['role_ids'] as $roleId) {
                    RoleChangeLog::create([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'action' => 'assigned',
                        'changed_by' => $currentUser->id,
                        'changed_at' => now(),
                        'metadata' => [
                            'user_email' => $user->email,
                            'changed_by_email' => $currentUser->email,
                            'context' => 'user_creation'
                        ]
                    ]);
                }
            }

            // Log user creation
            Log::info('New user created', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'department_id' => $validated['department_id'] ?? null,
                'roles_assigned' => $validated['role_ids'] ?? [],
                'created_by' => $currentUser->id
            ]);

            DB::commit();

            // Load relationships for response
            $user->load(['roles', 'departmentsAsHOD']);

            // Get department info if assigned
            $department = null;
            if ($validated['department_id']) {
                $department = Department::find($validated['department_id']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'pf_number' => $user->pf_number,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at,
                        'roles' => $user->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'description' => $role->description,
                            ];
                        }),
                        'department' => $department ? [
                            'id' => $department->id,
                            'name' => $department->name,
                            'code' => $department->code,
                        ] : null,
                        'primary_role' => $user->getPrimaryRole()?->name,
                    ]
                ],
                'message' => 'User created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}