<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('module_access_requests')) {
            return; // nothing to do on fresh installs without the base table
        }

        Schema::table('module_access_requests', function (Blueprint $table) {
            // Add personal information fields (auto-populated)
            if (! Schema::hasColumn('module_access_requests', 'pf_number')) {
                $table->string('pf_number')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'staff_name')) {
                $table->string('staff_name')->nullable()->after('pf_number');
            }
            if (! Schema::hasColumn('module_access_requests', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('staff_name');
            }
            if (! Schema::hasColumn('module_access_requests', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('phone_number');
            }
            if (! Schema::hasColumn('module_access_requests', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('department_id');
            }
            
            // Add form type to distinguish both-service-form from regular module access requests
            if (! Schema::hasColumn('module_access_requests', 'form_type')) {
                $table->enum('form_type', ['module_access', 'both_service_form'])->default('module_access')->after('signature_path');
            }
            
            // Add services requested (for both-service-form)
            if (! Schema::hasColumn('module_access_requests', 'services_requested')) {
                $table->json('services_requested')->nullable()->after('form_type'); // ['wellsoft', 'jeeva', 'internet_access']
            }
            
            // HOD Approval (required)
            if (! Schema::hasColumn('module_access_requests', 'hod_approval_status')) {
                $table->enum('hod_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('services_requested');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_comments')) {
                $table->text('hod_comments')->nullable()->after('hod_approval_status');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_signature_path')) {
                $table->string('hod_signature_path')->nullable()->after('hod_comments');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_approved_at')) {
                $table->timestamp('hod_approved_at')->nullable()->after('hod_signature_path');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_user_id')) {
                $table->foreignId('hod_user_id')->nullable()->constrained('users')->onDelete('set null')->after('hod_approved_at');
            }
            
            // Divisional Director Approval
            if (! Schema::hasColumn('module_access_requests', 'divisional_director_approval_status')) {
                $table->enum('divisional_director_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('hod_user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'divisional_director_comments')) {
                $table->text('divisional_director_comments')->nullable()->after('divisional_director_approval_status');
            }
            if (! Schema::hasColumn('module_access_requests', 'divisional_director_signature_path')) {
                $table->string('divisional_director_signature_path')->nullable()->after('divisional_director_comments');
            }
            if (! Schema::hasColumn('module_access_requests', 'divisional_director_approved_at')) {
                $table->timestamp('divisional_director_approved_at')->nullable()->after('divisional_director_signature_path');
            }
            if (! Schema::hasColumn('module_access_requests', 'divisional_director_user_id')) {
                $table->foreignId('divisional_director_user_id')->nullable()->constrained('users')->onDelete('set null')->after('divisional_director_approved_at');
            }
            
            // DICT Approval
            if (! Schema::hasColumn('module_access_requests', 'dict_approval_status')) {
                $table->enum('dict_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('divisional_director_user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'dict_comments')) {
                $table->text('dict_comments')->nullable()->after('dict_approval_status');
            }
            if (! Schema::hasColumn('module_access_requests', 'dict_signature_path')) {
                $table->string('dict_signature_path')->nullable()->after('dict_comments');
            }
            if (! Schema::hasColumn('module_access_requests', 'dict_approved_at')) {
                $table->timestamp('dict_approved_at')->nullable()->after('dict_signature_path');
            }
            if (! Schema::hasColumn('module_access_requests', 'dict_user_id')) {
                $table->foreignId('dict_user_id')->nullable()->constrained('users')->onDelete('set null')->after('dict_approved_at');
            }
            
            // Head of IT Approval
            if (! Schema::hasColumn('module_access_requests', 'hod_it_approval_status')) {
                $table->enum('hod_it_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('dict_user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_it_comments')) {
                $table->text('hod_it_comments')->nullable()->after('hod_it_approval_status');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_it_signature_path')) {
                $table->string('hod_it_signature_path')->nullable()->after('hod_it_comments');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_it_approved_at')) {
                $table->timestamp('hod_it_approved_at')->nullable()->after('hod_it_signature_path');
            }
            if (! Schema::hasColumn('module_access_requests', 'hod_it_user_id')) {
                $table->foreignId('hod_it_user_id')->nullable()->constrained('users')->onDelete('set null')->after('hod_it_approved_at');
            }
            
            // ICT Officer Approval (final)
            if (! Schema::hasColumn('module_access_requests', 'ict_officer_approval_status')) {
                $table->enum('ict_officer_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('hod_it_user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'ict_officer_comments')) {
                $table->text('ict_officer_comments')->nullable()->after('ict_officer_approval_status');
            }
            if (! Schema::hasColumn('module_access_requests', 'ict_officer_signature_path')) {
                $table->string('ict_officer_signature_path')->nullable()->after('ict_officer_comments');
            }
            if (! Schema::hasColumn('module_access_requests', 'ict_officer_approved_at')) {
                $table->timestamp('ict_officer_approved_at')->nullable()->after('ict_officer_signature_path');
            }
            if (! Schema::hasColumn('module_access_requests', 'ict_officer_user_id')) {
                $table->foreignId('ict_officer_user_id')->nullable()->constrained('users')->onDelete('set null')->after('ict_officer_approved_at');
            }
            
            // Overall status tracking
            if (! Schema::hasColumn('module_access_requests', 'overall_status')) {
                $table->enum('overall_status', ['pending', 'in_review', 'approved', 'rejected'])->default('pending')->after('ict_officer_user_id');
            }
            if (! Schema::hasColumn('module_access_requests', 'current_approval_stage')) {
                $table->string('current_approval_stage')->default('hod')->after('overall_status'); // hod, divisional_director, dict, hod_it, ict_officer, completed
            }
            
            // Add indexes for better performance (let later index migrations handle duplicates if any)
            $table->index(['form_type', 'overall_status']);
            $table->index(['current_approval_stage', 'created_at']);
            $table->index(['hod_approval_status', 'created_at']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_access_requests', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['department_id']);
            $table->dropForeign(['hod_user_id']);
            $table->dropForeign(['divisional_director_user_id']);
            $table->dropForeign(['dict_user_id']);
            $table->dropForeign(['hod_it_user_id']);
            $table->dropForeign(['ict_officer_user_id']);
            
            // Drop indexes
            $table->dropIndex(['form_type', 'overall_status']);
            $table->dropIndex(['current_approval_stage', 'created_at']);
            $table->dropIndex(['hod_approval_status', 'created_at']);
            $table->dropIndex(['department_id']);
            
            // Drop columns
            $table->dropColumn([
                'pf_number',
                'staff_name',
                'phone_number',
                'department_id',
                'signature_path',
                'form_type',
                'services_requested',
                'hod_approval_status',
                'hod_comments',
                'hod_signature_path',
                'hod_approved_at',
                'hod_user_id',
                'divisional_director_approval_status',
                'divisional_director_comments',
                'divisional_director_signature_path',
                'divisional_director_approved_at',
                'divisional_director_user_id',
                'dict_approval_status',
                'dict_comments',
                'dict_signature_path',
                'dict_approved_at',
                'dict_user_id',
                'hod_it_approval_status',
                'hod_it_comments',
                'hod_it_signature_path',
                'hod_it_approved_at',
                'hod_it_user_id',
                'ict_officer_approval_status',
                'ict_officer_comments',
                'ict_officer_signature_path',
                'ict_officer_approved_at',
                'ict_officer_user_id',
                'overall_status',
                'current_approval_stage'
            ]);
        });
    }
};