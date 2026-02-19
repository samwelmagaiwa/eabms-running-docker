<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminDepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Display a listing of departments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Department::with(['headOfDepartment.roles', 'divisionalDirector.roles', 'users']);

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by has_divisional_director flag
            if ($request->has('has_divisional_director')) {
                $query->where('has_divisional_director', $request->boolean('has_divisional_director'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'code', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('name', 'asc');
            }

            // Include counts
            $query->withCount([
                'users as users_count',
                'userAccessRequests as total_requests_count',
                'userAccessRequests as pending_requests_count' => function ($q) {
                    $q->where('hod_status', 'pending');
                }
            ]);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $departments = $query->paginate($perPage);

            // Transform the data to include HOD status information
            $departments->getCollection()->transform(function ($department) {
                $departmentArray = $department->toArray();
                
                // Add HOD status information
                if ($department->headOfDepartment) {
                    $departmentArray['hod'] = [
                        'id' => $department->headOfDepartment->id,
                        'name' => $department->headOfDepartment->name,
                        'email' => $department->headOfDepartment->email,
                        'pf_number' => $department->headOfDepartment->pf_number,
                        'roles' => $department->headOfDepartment->roles->pluck('name')->toArray(),
                    ];
                    $departmentArray['hod_status'] = 'Head of Department: ' . $department->headOfDepartment->name;
                } else {
                    $departmentArray['hod'] = null;
                    $departmentArray['hod_status'] = 'Head of Department: No HOD assigned';
                }
                
                // Add Divisional Director status information
                if ($department->divisionalDirector) {
                    $departmentArray['divisional_director'] = [
                        'id' => $department->divisionalDirector->id,
                        'name' => $department->divisionalDirector->name,
                        'email' => $department->divisionalDirector->email,
                        'pf_number' => $department->divisionalDirector->pf_number,
                        'roles' => $department->divisionalDirector->roles->pluck('name')->toArray(),
                    ];
                } else {
                    $departmentArray['divisional_director'] = null;
                }
                
                return $departmentArray;
            });

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
     * Store a newly created department.
     */
    public function store(DepartmentRequest $request): JsonResponse
    {

        DB::beginTransaction();
        
        try {
            $validatedData = $request->validated();
            
            $departmentData = [
                'name' => $validatedData['name'],
                'code' => $validatedData['code'], // Already uppercase from DepartmentRequest
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'],
                'hod_user_id' => $validatedData['hod_user_id'] ?? null,
                'has_divisional_director' => $validatedData['has_divisional_director'],
                'divisional_director_id' => $validatedData['divisional_director_id'] ?? null,
            ];

            $department = Department::create($departmentData);

            // Assign department_id to HOD if specified and ensure they have HOD role
            if ($department->hod_user_id) {
                $hod = \App\Models\User::find($department->hod_user_id);
                if ($hod) {
                    $hod->update(['department_id' => $department->id]);
                    
                    // Ensure HOD has the head_of_department role
                    $hodRole = \App\Models\Role::where('name', 'head_of_department')->first();
                    if ($hodRole && !$hod->hasRole('head_of_department')) {
                        $hod->roles()->attach($hodRole->id, [
                            'assigned_at' => now(),
                            'assigned_by' => $request->user()->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Log role assignment
                        \App\Models\RoleChangeLog::create([
                            'user_id' => $hod->id,
                            'role_id' => $hodRole->id,
                            'action' => 'assigned',
                            'changed_by' => $request->user()->id,
                            'changed_at' => now(),
                            'metadata' => [
                                'user_email' => $hod->email,
                                'changed_by_email' => $request->user()->email,
                                'context' => 'hod_department_assignment'
                            ]
                        ]);
                    }
                    
                    Log::info('Assigned department to HOD during creation', [
                        'user_id' => $department->hod_user_id,
                        'department_id' => $department->id,
                        'role_assigned' => $hodRole ? true : false
                    ]);
                }
            }

            // Assign department_id to Divisional Director if specified and ensure they have the role
            if ($department->divisional_director_id) {
                $director = \App\Models\User::find($department->divisional_director_id);
                if ($director) {
                    $director->update(['department_id' => $department->id]);
                    
                    // Ensure Director has the divisional_director role
                    $directorRole = \App\Models\Role::where('name', 'divisional_director')->first();
                    if ($directorRole && !$director->hasRole('divisional_director')) {
                        $director->roles()->attach($directorRole->id, [
                            'assigned_at' => now(),
                            'assigned_by' => $request->user()->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Log role assignment
                        \App\Models\RoleChangeLog::create([
                            'user_id' => $director->id,
                            'role_id' => $directorRole->id,
                            'action' => 'assigned',
                            'changed_by' => $request->user()->id,
                            'changed_at' => now(),
                            'metadata' => [
                                'user_email' => $director->email,
                                'changed_by_email' => $request->user()->email,
                                'context' => 'director_department_assignment'
                            ]
                        ]);
                    }
                    
                    Log::info('Assigned department to Divisional Director during creation', [
                        'user_id' => $department->divisional_director_id,
                        'department_id' => $department->id,
                        'role_assigned' => $directorRole ? true : false
                    ]);
                }
            }

            // Log the creation
            Log::info('Department created', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'department_code' => $department->code,
                'hod_assigned' => $department->hod_user_id ? true : false,
                'hod_user_id' => $department->hod_user_id,
                'director_assigned' => $department->divisional_director_id ? true : false,
                'director_user_id' => $department->divisional_director_id,
                'created_by' => $request->user()->id
            ]);

            DB::commit();

            // Load relationships for response
            $department->load(['headOfDepartment.roles', 'divisionalDirector.roles']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'is_active' => $department->is_active,
                    'has_divisional_director' => $department->has_divisional_director,
                    'hod' => $department->headOfDepartment ? [
                        'id' => $department->headOfDepartment->id,
                        'name' => $department->headOfDepartment->name,
                        'email' => $department->headOfDepartment->email,
                        'pf_number' => $department->headOfDepartment->pf_number,
                        'roles' => $department->headOfDepartment->roles->pluck('name')->toArray(),
                    ] : null,
                    'divisional_director' => $department->divisionalDirector ? [
                        'id' => $department->divisionalDirector->id,
                        'name' => $department->divisionalDirector->name,
                        'email' => $department->divisionalDirector->email,
                        'pf_number' => $department->divisionalDirector->pf_number,
                        'roles' => $department->divisionalDirector->roles->pluck('name')->toArray(),
                    ] : null,
                    'created_at' => $department->created_at,
                    'updated_at' => $department->updated_at,
                ],
                'message' => $department->hod_user_id 
                    ? "Department '{$department->name}' created successfully with HOD assigned."
                    : "Department '{$department->name}' created successfully."
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating department: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create department.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department): JsonResponse
    {
        try {
            $department->load([
                'headOfDepartment.roles',
                'divisionalDirector.roles',
                'users.roles'
            ]);

            $department->loadCount([
                'users as users_count',
                'userAccessRequests as total_requests_count',
                'userAccessRequests as pending_requests_count' => function ($q) {
                    $q->where('hod_status', 'pending');
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'is_active' => $department->is_active,
                    'has_divisional_director' => $department->has_divisional_director,
                    'users_count' => $department->users_count,
                    'total_requests_count' => $department->total_requests_count,
                    'pending_requests_count' => $department->pending_requests_count,
                    'hod' => $department->headOfDepartment ? [
                        'id' => $department->headOfDepartment->id,
                        'name' => $department->headOfDepartment->name,
                        'email' => $department->headOfDepartment->email,
                        'pf_number' => $department->headOfDepartment->pf_number,
                        'roles' => $department->headOfDepartment->roles->pluck('name')->toArray(),
                    ] : null,
                    'divisional_director' => $department->divisionalDirector ? [
                        'id' => $department->divisionalDirector->id,
                        'name' => $department->divisionalDirector->name,
                        'email' => $department->divisionalDirector->email,
                        'pf_number' => $department->divisionalDirector->pf_number,
                        'roles' => $department->divisionalDirector->roles->pluck('name')->toArray(),
                    ] : null,
                    'users' => $department->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'pf_number' => $user->pf_number,
                            'roles' => $user->roles->pluck('name')->toArray(),
                        ];
                    }),
                    'created_at' => $department->created_at,
                    'updated_at' => $department->updated_at,
                ],
                'message' => 'Department retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving department: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve department.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified department.
     */
    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {

        DB::beginTransaction();
        
        try {
            $originalData = $department->toArray();
            $validatedData = $request->validated();

            // Store original HOD and Divisional Director IDs for comparison
            $originalHodId = $department->hod_user_id;
            $originalDirectorId = $department->divisional_director_id;

            $updateData = [
                'name' => $validatedData['name'],
                'code' => $validatedData['code'], // Already uppercase from DepartmentRequest
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'],
                'hod_user_id' => $validatedData['hod_user_id'] ?? null,
                'has_divisional_director' => $validatedData['has_divisional_director'],
                'divisional_director_id' => $validatedData['divisional_director_id'] ?? null,
            ];

            $department->update($updateData);

            // Handle HOD assignment changes
            $newHodId = $validatedData['hod_user_id'] ?? null;
            if ($originalHodId !== $newHodId) {
                // Remove department_id from previous HOD if they exist
                if ($originalHodId) {
                    $previousHod = \App\Models\User::find($originalHodId);
                    if ($previousHod && $previousHod->department_id == $department->id) {
                        $previousHod->update(['department_id' => null]);
                        
                        // Check if they should keep the HOD role (if they're HOD of other departments)
                        if ($previousHod->departmentsAsHOD()->where('id', '!=', $department->id)->count() === 0) {
                            // Remove HOD role if they're not HOD of any other department
                            $hodRole = \App\Models\Role::where('name', 'head_of_department')->first();
                            if ($hodRole && $previousHod->hasRole('head_of_department')) {
                                $previousHod->roles()->detach($hodRole->id);
                                
                                // Log role removal
                                \App\Models\RoleChangeLog::create([
                                    'user_id' => $previousHod->id,
                                    'role_id' => $hodRole->id,
                                    'action' => 'removed',
                                    'changed_by' => $request->user()->id,
                                    'changed_at' => now(),
                                    'metadata' => [
                                        'user_email' => $previousHod->email,
                                        'changed_by_email' => $request->user()->email,
                                        'context' => 'hod_department_unassignment'
                                    ]
                                ]);
                            }
                        }
                        
                        Log::info('Removed department assignment from previous HOD', [
                            'user_id' => $originalHodId,
                            'department_id' => $department->id
                        ]);
                    }
                }

                // Assign department_id to new HOD if they exist
                if ($newHodId) {
                    $newHod = \App\Models\User::find($newHodId);
                    if ($newHod) {
                        $newHod->update(['department_id' => $department->id]);
                        
                        // Ensure new HOD has the head_of_department role
                        $hodRole = \App\Models\Role::where('name', 'head_of_department')->first();
                        if ($hodRole && !$newHod->hasRole('head_of_department')) {
                            $newHod->roles()->attach($hodRole->id, [
                                'assigned_at' => now(),
                                'assigned_by' => $request->user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            // Log role assignment
                            \App\Models\RoleChangeLog::create([
                                'user_id' => $newHod->id,
                                'role_id' => $hodRole->id,
                                'action' => 'assigned',
                                'changed_by' => $request->user()->id,
                                'changed_at' => now(),
                                'metadata' => [
                                    'user_email' => $newHod->email,
                                    'changed_by_email' => $request->user()->email,
                                    'context' => 'hod_department_assignment'
                                ]
                            ]);
                        }
                        
                        Log::info('Assigned department to new HOD', [
                            'user_id' => $newHodId,
                            'department_id' => $department->id
                        ]);
                    }
                }
            }

            // Handle Divisional Director assignment changes
            $newDirectorId = $validatedData['divisional_director_id'] ?? null;
            if ($originalDirectorId !== $newDirectorId) {
                // Remove department_id from previous Divisional Director if they exist
                if ($originalDirectorId) {
                    $previousDirector = \App\Models\User::find($originalDirectorId);
                    if ($previousDirector && $previousDirector->department_id == $department->id) {
                        $previousDirector->update(['department_id' => null]);
                        
                        // Check if they should keep the Director role (if they're Director of other departments)
                        if ($previousDirector->departmentsAsDivisionalDirector()->where('id', '!=', $department->id)->count() === 0) {
                            // Remove Director role if they're not Director of any other department
                            $directorRole = \App\Models\Role::where('name', 'divisional_director')->first();
                            if ($directorRole && $previousDirector->hasRole('divisional_director')) {
                                $previousDirector->roles()->detach($directorRole->id);
                                
                                // Log role removal
                                \App\Models\RoleChangeLog::create([
                                    'user_id' => $previousDirector->id,
                                    'role_id' => $directorRole->id,
                                    'action' => 'removed',
                                    'changed_by' => $request->user()->id,
                                    'changed_at' => now(),
                                    'metadata' => [
                                        'user_email' => $previousDirector->email,
                                        'changed_by_email' => $request->user()->email,
                                        'context' => 'director_department_unassignment'
                                    ]
                                ]);
                            }
                        }
                        
                        Log::info('Removed department assignment from previous Divisional Director', [
                            'user_id' => $originalDirectorId,
                            'department_id' => $department->id
                        ]);
                    }
                }

                // Assign department_id to new Divisional Director if they exist
                if ($newDirectorId) {
                    $newDirector = \App\Models\User::find($newDirectorId);
                    if ($newDirector) {
                        $newDirector->update(['department_id' => $department->id]);
                        
                        // Ensure new Director has the divisional_director role
                        $directorRole = \App\Models\Role::where('name', 'divisional_director')->first();
                        if ($directorRole && !$newDirector->hasRole('divisional_director')) {
                            $newDirector->roles()->attach($directorRole->id, [
                                'assigned_at' => now(),
                                'assigned_by' => $request->user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            // Log role assignment
                            \App\Models\RoleChangeLog::create([
                                'user_id' => $newDirector->id,
                                'role_id' => $directorRole->id,
                                'action' => 'assigned',
                                'changed_by' => $request->user()->id,
                                'changed_at' => now(),
                                'metadata' => [
                                    'user_email' => $newDirector->email,
                                    'changed_by_email' => $request->user()->email,
                                    'context' => 'director_department_assignment'
                                ]
                            ]);
                        }
                        
                        Log::info('Assigned department to new Divisional Director', [
                            'user_id' => $newDirectorId,
                            'department_id' => $department->id
                        ]);
                    }
                }
            }

            // Log the update
            Log::info('Department updated', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'changes' => array_diff_assoc($department->toArray(), $originalData),
                'hod_changed' => $originalHodId !== $newHodId,
                'director_changed' => $originalDirectorId !== $newDirectorId,
                'updated_by' => $request->user()->id
            ]);

            DB::commit();

            // Load relationships for response
            $department->load(['headOfDepartment.roles', 'divisionalDirector.roles']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'is_active' => $department->is_active,
                    'has_divisional_director' => $department->has_divisional_director,
                    'hod' => $department->headOfDepartment ? [
                        'id' => $department->headOfDepartment->id,
                        'name' => $department->headOfDepartment->name,
                        'email' => $department->headOfDepartment->email,
                        'pf_number' => $department->headOfDepartment->pf_number,
                        'roles' => $department->headOfDepartment->roles->pluck('name')->toArray(),
                    ] : null,
                    'divisional_director' => $department->divisionalDirector ? [
                        'id' => $department->divisionalDirector->id,
                        'name' => $department->divisionalDirector->name,
                        'email' => $department->divisionalDirector->email,
                        'pf_number' => $department->divisionalDirector->pf_number,
                        'roles' => $department->divisionalDirector->roles->pluck('name')->toArray(),
                    ] : null,
                    'created_at' => $department->created_at,
                    'updated_at' => $department->updated_at,
                ],
                'message' => "Department '{$department->name}' updated successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating department: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update department.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            // Check if department has users
            if ($department->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department that has assigned users. Please reassign users first.'
                ], 422);
            }

            // Check if department has pending requests
            if ($department->userAccessRequests()->where('status', 'pending')->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department that has pending access requests. Please resolve pending requests first.'
                ], 422);
            }

            $departmentName = $department->name;

            // Log the deletion
            Log::info('Department deleted', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'deleted_by' => request()->user()->id
            ]);

            $department->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Department '{$departmentName}' deleted successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting department: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete department.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle department active status.
     */
    public function toggleStatus(Department $department): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $newStatus = !$department->is_active;
            $department->update(['is_active' => $newStatus]);

            // Log the status change
            Log::info('Department status toggled', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'new_status' => $newStatus ? 'active' : 'inactive',
                'changed_by' => request()->user()->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $department->id,
                    'is_active' => $department->is_active
                ],
                'message' => "Department '{$department->name}' " . ($newStatus ? 'activated' : 'deactivated') . " successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error toggling department status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle department status.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get eligible HOD users for department assignment.
     */
    public function getEligibleHods(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->get('department_id'); // For edit mode
            
            // Get all active users first
            $allUsersQuery = \App\Models\User::with(['roles', 'departmentsAsHOD', 'departmentsAsDivisionalDirector']);
            
            // Only filter by is_active if the column exists
            if (Schema::hasColumn('users', 'is_active')) {
                $allUsersQuery->where('is_active', true);
            }
            
            $allUsers = $allUsersQuery->orderBy('name')->get();

            // Filter HOD eligible users - ANY user is eligible except those already assigned as HOD to another department
            $users = $allUsers->filter(function ($user) use ($departmentId) {
                // Check if user is already assigned as HOD to another department
                $isCurrentlyHodOfOther = $user->departmentsAsHOD->where('id', '!=', $departmentId)->isNotEmpty();
                
                // User is eligible if they are not currently assigned as HOD to a DIFFERENT department
                // They CAN be a Divisional Director elsewhere or even for the same department
                return !$isCurrentlyHodOfOther;
            })->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'staff_name' => $user->staff_name,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('_', ' ', $role->name))
                        ];
                    }),
                    'current_departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'current_departments_as_director' => $user->departmentsAsDivisionalDirector->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'is_available' => $user->departmentsAsHOD->where('id', '!=', $departmentId)->isEmpty() && $user->departmentsAsDivisionalDirector->isEmpty(),
                    'display_name' => $user->name . ($user->pf_number ? " (PF: {$user->pf_number})" : '') . " - {$user->email}"
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Eligible HOD users retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving eligible HODs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eligible HODs.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get eligible divisional directors for department assignment.
     */
    public function getEligibleDivisionalDirectors(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->get('department_id'); // For edit mode
            
            // Get all active users first
            $allUsersQuery = \App\Models\User::with(['roles', 'departmentsAsHOD', 'departmentsAsDivisionalDirector']);
            
            // Only filter by is_active if the column exists
            if (Schema::hasColumn('users', 'is_active')) {
                $allUsersQuery->where('is_active', true);
            }
            
            $allUsers = $allUsersQuery->orderBy('name')->get();

                // Check if user is already assigned as Divisional Director to another department
                $isCurrentlyDirectorOfOther = $user->departmentsAsDivisionalDirector->where('id', '!=', $departmentId)->isNotEmpty();
                
                // User is eligible if they are not currently assigned as Divisional Director to a DIFFERENT department
                // They CAN be an HOD elsewhere or even for the same department
                return !$isCurrentlyDirectorOfOther;
            })->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'staff_name' => $user->staff_name,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('_', ' ', $role->name))
                        ];
                    }),
                    'current_departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'current_departments_as_director' => $user->departmentsAsDivisionalDirector->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'is_available' => $user->departmentsAsDivisionalDirector->where('id', '!=', $departmentId)->isEmpty(),
                    'display_name' => $user->name . ($user->pf_number ? " (PF: {$user->pf_number})" : '') . " - {$user->email}"
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Eligible divisional director users retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving eligible divisional directors: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eligible divisional directors.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get form data for creating/editing a department.
     */
    public function getCreateFormData(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->get('department_id'); // For edit mode
            
            // Get all active users first
            $allUsersQuery = \App\Models\User::with(['roles', 'departmentsAsHOD', 'departmentsAsDivisionalDirector']);
            
            // Only filter by is_active if the column exists
            if (Schema::hasColumn('users', 'is_active')) {
                $allUsersQuery->where('is_active', true);
            }
            
            $allUsers = $allUsersQuery->orderBy('name')->get();

            // Filter HOD eligible users - ANY user is eligible except those already assigned as HOD to another department
            $hodUsers = $allUsers->filter(function ($user) use ($departmentId) {
                // Check if user is already assigned as HOD to another department
                $isCurrentlyHodOfOther = $user->departmentsAsHOD->where('id', '!=', $departmentId)->isNotEmpty();
                
                // User is eligible if they are not currently assigned as HOD to a DIFFERENT department
                return !$isCurrentlyHodOfOther;
            })->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'staff_name' => $user->staff_name,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('_', ' ', $role->name))
                        ];
                    }),
                    'current_departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'current_departments_as_director' => $user->departmentsAsDivisionalDirector->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'display_name' => $user->name . ($user->pf_number ? " (PF: {$user->pf_number})" : '') . " - {$user->email}"
                ];
            })->values();

            // Filter Divisional Director eligible users - ANY user is eligible except those already assigned as Divisional Director to another department
            $divisionalDirectorUsers = $allUsers->filter(function ($user) use ($departmentId) {
                // Check if user is already assigned as Divisional Director to another department
                $isCurrentlyDirectorOfOther = $user->departmentsAsDivisionalDirector->where('id', '!=', $departmentId)->isNotEmpty();
                
                // User is eligible if they are not currently assigned as Divisional Director to a DIFFERENT department
                return !$isCurrentlyDirectorOfOther;
            })->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pf_number' => $user->pf_number,
                    'staff_name' => $user->staff_name,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('_', ' ', $role->name))
                        ];
                    }),
                    'current_departments_as_hod' => $user->departmentsAsHOD->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'current_departments_as_director' => $user->departmentsAsDivisionalDirector->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'code' => $dept->code,
                            'display_name' => $dept->getFullNameAttribute()
                        ];
                    }),
                    'display_name' => $user->name . ($user->pf_number ? " (PF: {$user->pf_number})" : '') . " - {$user->email}"
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'eligible_hods' => $hodUsers,
                    'eligible_divisional_directors' => $divisionalDirectorUsers,
                    'department_statuses' => [
                        ['value' => true, 'label' => 'Active'],
                        ['value' => false, 'label' => 'Inactive']
                    ],
                    'total_users_checked' => $allUsers->count(),
                    'hod_eligible_count' => $hodUsers->count(),
                    'director_eligible_count' => $divisionalDirectorUsers->count()
                ],
                'message' => 'Department form data retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving department form data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve form data.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}