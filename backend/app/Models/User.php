<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method \Laravel\Sanctum\NewAccessToken createToken(string $name, array $abilities = [])
 */

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'pf_number',
        'department_id',
        'is_active',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function onboarding()
    {
        return $this->hasOne(UserOnboarding::class);
    }

    public function declaration()
    {
        return $this->hasOne(Declaration::class);
    }

    /**
     * Get the department this user belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the departments where this user is the head of department.
     */
    public function departmentsAsHOD()
    {
        return $this->hasMany(Department::class, 'hod_user_id');
    }

    /**
     * Get the departments where this user is the divisional director.
     */
    public function departmentsAsDivisionalDirector()
    {
        return $this->hasMany(Department::class, 'divisional_director_id');
    }

    /**
     * Roles assigned to this user (many-to-many relationship) - PRIMARY SYSTEM
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * Role changes made by this user
     */
    public function roleChanges()
    {
        return $this->hasMany(RoleChangeLog::class, 'changed_by');
    }

    /**
     * Role changes for this user
     */
    public function roleHistory()
    {
        return $this->hasMany(RoleChangeLog::class, 'user_id');
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roleNames): bool
    {
        $userRoles = $this->roles()->pluck('name')->toArray();
        return empty(array_diff($roleNames, $userRoles));
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()->get()->some(function ($role) use ($permission) {
            return $role->hasPermission($permission);
        });
    }

    /**
     * Get all permissions for this user
     */
    public function getAllPermissions(): array
    {
        $permissions = $this->roles()->get()
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
            
        // If no permissions from many-to-many roles, return basic permissions
        if (empty($permissions)) {
            return ['view_users']; // Basic staff permissions
        }
        
        return $permissions;
    }

    /**
     * Check if user is admin (use new many-to-many system)
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is HOD (use new many-to-many system)
     */
    public function isHOD(): bool
    {
        return $this->hasRole('head_of_department');
    }

    /**
     * Get user's primary role (use new many-to-many system with priority)
     */
    public function getPrimaryRole()
    {
        // Get the highest priority role from assigned roles
        $roles = $this->roles;
        return Role::getHighestPriorityRole($roles);
    }

    /**
     * Get role names as array (use new many-to-many system)
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->roles()->pluck('name')->toArray();
    }

    /**
     * Get primary role name (use new many-to-many system)
     */
    public function getPrimaryRoleName(): ?string
    {
        $primaryRole = $this->getPrimaryRole();
        
        if ($primaryRole) {
            return $primaryRole->name;
        }
        
        // Last resort: return 'staff' as default
        return 'staff';
    }

    /**
     * Get display role names for UI
     */
    public function getDisplayRoleNames(): string
    {
        $roleNames = $this->roles()->pluck('name')->toArray();
        return implode(', ', array_map(function($name) {
            return ucwords(str_replace('_', ' ', $name));
        }, $roleNames));
    }

    /**
     * Check if user has admin privileges
     */
    public function hasAdminPrivileges(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users') || $this->hasAdminPrivileges();
    }

    /**
     * Check if user can assign roles
     */
    public function canAssignRoles(): bool
    {
        return $this->hasPermission('assign_roles') || $this->hasAdminPrivileges();
    }

    /**
     * Check if user needs onboarding (non-admin users who haven't completed it)
     */
    public function needsOnboarding(): bool
    {
        // Admin users don't need onboarding
        if ($this->isAdmin()) {
            return false;
        }

        // Check if onboarding record exists and is completed
        $onboarding = $this->onboarding;
        return !$onboarding || !$onboarding->completed;
    }

    /**
     * Get or create onboarding record for user
     */
    public function getOrCreateOnboarding(): UserOnboarding
    {
        return $this->onboarding()->firstOrCreate(
            ['user_id' => $this->id],
            ['current_step' => 'terms-popup']
        );
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for users with specific role
     */
    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope for users with any of the given roles
     */
    public function scopeWithAnyRole($query, array $roleNames)
    {
        return $query->whereHas('roles', function ($q) use ($roleNames) {
            $q->whereIn('name', $roleNames);
        });
    }

    /**
     * Scope for users in specific department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get the URL for the user's profile photo.
     *
     * @return string|null
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
    }

    /**
     * Get department name attribute
     */
    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    /**
     * Get full department display name
     */
    public function getFullDepartmentNameAttribute(): ?string
    {
        return $this->department?->getFullNameAttribute();
    }

    /**
     * Task assignments assigned to this user (as ICT Officer)
     */
    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, 'assigned_to');
    }

    /**
     * Task assignments created by this user (as Head of IT)
     */
    public function createdTaskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, 'assigned_by');
    }

    /**
     * Current active task assignments for this user
     */
    public function currentTaskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, 'assigned_to')
            ->whereIn('status', ['assigned', 'in_progress']);
    }

    /**
     * Get access requests assigned to this ICT Officer
     */
    public function assignedAccessRequests()
    {
        return $this->hasMany(UserCombinedAccessRequest::class, 'assigned_ict_officer_id');
    }

    /**
     * Access requests approved by this user as Head of IT
     */
    public function approvedAccessRequests()
    {
        return $this->hasMany(UserCombinedAccessRequest::class, 'head_of_it_approved_by');
    }

    /**
     * Access requests rejected by this user as Head of IT
     */
    public function rejectedAccessRequests()
    {
        return $this->hasMany(UserCombinedAccessRequest::class, 'head_of_it_rejected_by');
    }

    /**
     * Check if user is ICT Officer (includes Secretary ICT and permission-based fallback)
     */
    public function isIctOfficer(): bool
    {
        // Primary check: explicit ICT Officer role
        if ($this->hasRole('ict_officer')) {
            return true;
        }

        // Treat Secretary ICT as ICT Officer-equivalent for implementation workflows
        if ($this->hasRole('secretary_ict')) {
            return true;
        }

        // Conservative fallback: if user has device booking implementation/approval permissions,
        // consider them ICT Officer-like. This avoids hard-coding every future ICT-like role.
        if (
            method_exists($this, 'hasPermission') &&
            (
                $this->hasPermission('approve_device_bookings') ||
                $this->hasPermission('view_device_bookings') ||
                $this->hasPermission('manage_device_inventory')
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is Head of IT
     */
    public function isHeadOfIt(): bool
    {
        return $this->hasRole('head_of_it');
    }

    /**
     * Get user's current workload (number of active task assignments)
     */
    public function getCurrentWorkload(): int
    {
        return $this->currentTaskAssignments()->count();
    }

    /**
     * Check if ICT Officer is available for new task assignments
     */
    public function isAvailableForTasks(): bool
    {
        if (!$this->isIctOfficer()) {
            return false;
        }

        // Available if has less than 3 active assignments
        return $this->getCurrentWorkload() < 3;
    }

    /**
     * Get ICT Officer availability status
     */
    public function getAvailabilityStatus(): string
    {
        if (!$this->isIctOfficer()) {
            return 'Not ICT Officer';
        }

        $workload = $this->getCurrentWorkload();
        
        if ($workload === 0) {
            return 'Available';
        } elseif ($workload < 3) {
            return 'Assigned';
        } else {
            return 'Busy';
        }
    }

    /**
     * ICT Task assignments assigned to this user (as ICT Officer)
     */
    public function assignedIctTasks()
    {
        return $this->hasMany(IctTaskAssignment::class, 'ict_officer_user_id');
    }

    /**
     * ICT Task assignments created by this user (as Head of IT)
     */
    public function assignedTasksByHeadOfIt()
    {
        return $this->hasMany(IctTaskAssignment::class, 'assigned_by_user_id');
    }

    /**
     * Current active ICT task assignments for this user
     */
    public function currentIctTasks()
    {
        return $this->hasMany(IctTaskAssignment::class, 'ict_officer_user_id')
            ->whereIn('status', ['assigned', 'in_progress']);
    }

    /**
     * Get user's current ICT task workload
     */
    public function getCurrentIctWorkload(): int
    {
        return $this->currentIctTasks()->count();
    }

    /**
     * Check if ICT Officer is available for new ICT task assignments
     */
    public function isAvailableForIctTasks(): bool
    {
        if (!$this->isIctOfficer()) {
            return false;
        }

        // Available if has less than 5 active ICT assignments
        return $this->getCurrentIctWorkload() < 5;
    }

    /**
     * Get ICT Officer availability status for ICT tasks
     */
    public function getIctTaskAvailabilityStatus(): string
    {
        if (!$this->isIctOfficer()) {
            return 'Not ICT Officer';
        }

        $workload = $this->getCurrentIctWorkload();
        
        if ($workload === 0) {
            return 'Available';
        } elseif ($workload < 3) {
            return 'Low Load';
        } elseif ($workload < 5) {
            return 'Moderate Load';
        } else {
            return 'High Load';
        }
    }
}
