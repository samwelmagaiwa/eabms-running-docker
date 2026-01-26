<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates users with proper role assignments using both:
     * 1. Many-to-many system (role_user pivot table) - Primary system
     * 2. Legacy role_id field - For backward compatibility
     * 
     * Each user gets their primary role assigned to role_id field and all roles
     * assigned through the many-to-many relationship.
     */
    public function run(): void
    {
        // Get departments for assignment
        $departments = [
            'ICT' => Department::where('code', 'ICT')->first(),
            'HR' => Department::where('code', 'HR')->first(),
            'ADMIN' => Department::where('code', 'ADMIN')->first(),
            'NURS' => Department::where('code', 'NURS')->first(),
            'LAB' => Department::where('code', 'LAB')->first(),
            'PHARM' => Department::where('code', 'PHARM')->first(),
            'MR' => Department::where('code', 'MR')->first(),
            'RAD' => Department::where('code', 'RAD')->first(),
            'ED' => Department::where('code', 'ED')->first(),
            'SURG' => Department::where('code', 'SURG')->first(),
            'OPD' => Department::where('code', 'OPD')->first(),
            'FIN' => Department::where('code', 'FIN')->first(),
        ];

        // Get roles for assignment
        $roles = [
            'admin' => Role::where('name', 'admin')->first(),
            'divisional_director' => Role::where('name', 'divisional_director')->first(),
            'head_of_department' => Role::where('name', 'head_of_department')->first(),
            'ict_director' => Role::where('name', 'ict_director')->first(),
            'head_of_it' => Role::where('name', 'head_of_it')->first(),
            'ict_officer' => Role::where('name', 'ict_officer')->first(),
            'staff' => Role::where('name', 'staff')->first(),
        ];

        // Create users with role assignments
        $users = [
            // Administrative Users
            [
                'name' => 'System Administrator',
                'email' => 'admin@mnh.go.tz',
                'phone' => '+255700000000',
                'pf_number' => 'PF2367',
                'department_id' => $departments['ADMIN']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['admin']
            ],
            
            // ICT Department Users
            [
                'name' => 'ICT Director',
                'email' => 'ict.director@mnh.go.tz',
                'phone' => '+255700000001',
                'pf_number' => 'PF8901',
                'department_id' => $departments['ICT']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['ict_director']
            ],
            [
                'name' => 'Head of IT Services',
                'email' => 'head.it@mnh.go.tz',
                'phone' => '+255743519104',
                'pf_number' => 'PF3456',
                'department_id' => $departments['ICT']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['head_of_it']
            ],
            [
                'name' => 'Senior ICT Officer',
                'email' => 'ict.officer@mnh.go.tz',
                'phone' => '+255700000002',
                'pf_number' => 'PF1289',
                'department_id' => $departments['ICT']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['ict_officer']
            ],
            [
                'name' => 'ICT Support Specialist',
                'email' => 'ict.support@mnh.go.tz',
                'phone' => '+255700000003',
                'pf_number' => 'PF7890',
                'department_id' => $departments['ICT']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            
            // HR Department Users
            [
                'name' => 'HR Director',
                'email' => 'hr.director@mnh.go.tz',
                'phone' => '+255700000004',
                'pf_number' => 'PF6372',
                'department_id' => $departments['HR']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['divisional_director']
            ],
            [
                'name' => 'Head of HR Department',
                'email' => 'hod.hr@mnh.go.tz',
                'phone' => '+255700000005',
                'pf_number' => 'PF5432',
                'department_id' => $departments['HR']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['head_of_department']
            ],
            [
                'name' => 'HR Officer',
                'email' => 'hr.officer@mnh.go.tz',
                'phone' => '+255700000006',
                'pf_number' => 'PF7654',
                'department_id' => $departments['HR']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            
            // Nursing Department Users
            [
                'name' => 'Senior Nurse',
                'email' => 'senior.nurse@mnh.go.tz',
                'phone' => '+255700000008',
                'pf_number' => 'PF1122',
                'department_id' => $departments['NURS']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Registered Nurse',
                'email' => 'nurse1@mnh.go.tz',
                'phone' => '+255700000009',
                'pf_number' => 'PF3344',
                'department_id' => $departments['NURS']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Staff Nurse',
                'email' => 'nurse2@mnh.go.tz',
                'phone' => '+255700000010',
                'pf_number' => 'PF5566',
                'department_id' => $departments['NURS']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            
            // Laboratory Department Users
            [
                'name' => 'Chief Laboratory Technologist',
                'email' => 'chief.lab@mnh.go.tz',
                'phone' => '+255700000011',
                'pf_number' => 'PF4567',
                'department_id' => $departments['LAB']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['head_of_department']
            ],
            [
                'name' => 'Senior Lab Technician',
                'email' => 'lab.tech1@mnh.go.tz',
                'phone' => '+255700000012',
                'pf_number' => 'PF7788',
                'department_id' => $departments['LAB']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Laboratory Assistant',
                'email' => 'lab.assistant@mnh.go.tz',
                'phone' => '+255700000013',
                'pf_number' => 'PF9900',
                'department_id' => $departments['LAB']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            
            // Pharmacy Department Users
            [
                'name' => 'Chief Pharmacist',
                'email' => 'chief.pharmacist@mnh.go.tz',
                'phone' => '+255700000014',
                'pf_number' => 'PF9876',
                'department_id' => $departments['PHARM']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['head_of_department']
            ],
            [
                'name' => 'Senior Pharmacist',
                'email' => 'pharmacist1@mnh.go.tz',
                'phone' => '+255700000015',
                'pf_number' => 'PF2233',
                'department_id' => $departments['PHARM']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Pharmacy Technician',
                'email' => 'pharm.tech@mnh.go.tz',
                'phone' => '+255700000016',
                'pf_number' => 'PF4455',
                'department_id' => $departments['PHARM']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            
            // Additional Staff Members
            [
                'name' => 'John Mwanga',
                'email' => 'john.mwanga@mnh.go.tz',
                'phone' => '+255700000017',
                'pf_number' => 'PF6677',
                'department_id' => $departments['NURS']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Asha Juma',
                'email' => 'asha.juma@mnh.go.tz',
                'phone' => '+255700000018',
                'pf_number' => 'PF8899',
                'department_id' => $departments['NURS']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'David Selemani',
                'email' => 'david.selemani@mnh.go.tz',
                'phone' => '+255700000019',
                'pf_number' => 'PF1010',
                'department_id' => $departments['LAB']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
            [
                'name' => 'Fatuma Bakari',
                'email' => 'fatuma.bakari@mnh.go.tz',
                'phone' => '+255700000020',
                'pf_number' => 'PF1212',
                'department_id' => $departments['PHARM']?->id,
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'roles' => ['staff']
            ],
        ];

        $this->command->info('Creating users with role assignments...');

        foreach ($users as $userData) {
            // Extract roles from user data
            $userRoles = $userData['roles'] ?? ['staff'];
            unset($userData['roles']);
            
            // Determine primary role for logging/info only
            $primaryRoleName = $userRoles[0] ?? 'staff';
            $primaryRole = $roles[$primaryRoleName] ?? $roles['staff'];
            
            // Create or update user (no legacy role_id)
            $user = User::firstOrCreate(
                ['email' => $userData['email']], 
                $userData
            );
            
            // Assign roles via many-to-many relationship
            foreach ($userRoles as $roleName) {
                $role = $roles[$roleName] ?? $roles['staff'];
                if ($role && !$user->roles()->where('role_id', $role->id)->exists()) {
                    $user->roles()->attach($role->id, [
                        'assigned_at' => now(),
                        'assigned_by' => 1, // System assignment
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            $departmentName = $user->department ? $user->department->name : 'No Department';
            $assignedRoles = implode(', ', $userRoles);
            $this->command->info("Created user: {$user->name} ({$departmentName}) - Roles: {$assignedRoles}");
        }

        // Update department relationships
        $this->updateDepartmentRelationships();

        $this->command->info('Users created successfully with role assignments!');
    }

    /**
     * Update department relationships based on role assignments
     */
    private function updateDepartmentRelationships(): void
    {
        $this->command->info('Updating department relationships...');

        // Update HOD assignments
        $hodRole = Role::where('name', 'head_of_department')->first();
        if ($hodRole) {
            foreach ($hodRole->users as $user) {
                if ($user->department_id) {
                    $department = Department::find($user->department_id);
                    if ($department && !$department->hod_user_id) {
                        $department->update(['hod_user_id' => $user->id]);
                        $this->command->info("  → Set {$user->name} as HOD of {$department->name}");
                    }
                }
            }
        }

        // Update Divisional Director assignments
        $divDirectorRole = Role::where('name', 'divisional_director')->first();
        if ($divDirectorRole) {
            foreach ($divDirectorRole->users as $user) {
                if ($user->department_id) {
                    $department = Department::find($user->department_id);
                    if ($department && !$department->divisional_director_id) {
                        $department->update([
                            'divisional_director_id' => $user->id,
                            'has_divisional_director' => true
                        ]);
                        $this->command->info("  → Set {$user->name} as Divisional Director of {$department->name}");
                    }
                }
            }
        }
    }
}