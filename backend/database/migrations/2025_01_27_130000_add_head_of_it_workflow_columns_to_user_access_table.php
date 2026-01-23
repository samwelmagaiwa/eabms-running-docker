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
        // Make this migration safe on databases where some columns may
        // already exist (e.g. from earlier patches or partial runs).
        Schema::table('user_access', function (Blueprint $table) {
            // Core identifiers / cached fields
            if (! Schema::hasColumn('user_access', 'request_id')) {
                $table->string('request_id')->nullable()->after('id'); // Generate unique request IDs
            }
            if (! Schema::hasColumn('user_access', 'department_name')) {
                $table->string('department_name')->nullable()->after('department_id'); // Cache department name
            }
            if (! Schema::hasColumn('user_access', 'position')) {
                $table->string('position')->nullable()->after('staff_name'); // Staff position
            }

            // Head of IT approval fields
            if (! Schema::hasColumn('user_access', 'head_of_it_approved_at')) {
                $table->timestamp('head_of_it_approved_at')->nullable()->after('ict_director_approved_at');
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_approved_by')) {
                $table->unsignedBigInteger('head_of_it_approved_by')->nullable()->after('head_of_it_approved_at');
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_signature_path')) {
                $table->string('head_of_it_signature_path')->nullable()->after('head_of_it_approved_by');
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_comments')) {
                $table->text('head_of_it_comments')->nullable()->after('head_of_it_signature_path');
            }

            // Head of IT rejection fields
            if (! Schema::hasColumn('user_access', 'head_of_it_rejected_at')) {
                $table->timestamp('head_of_it_rejected_at')->nullable()->after('head_of_it_comments');
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_rejected_by')) {
                $table->unsignedBigInteger('head_of_it_rejected_by')->nullable()->after('head_of_it_rejected_at');
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_rejection_reason')) {
                $table->text('head_of_it_rejection_reason')->nullable()->after('head_of_it_rejected_by');
            }

            // Task assignment fields
            if (! Schema::hasColumn('user_access', 'assigned_ict_officer_id')) {
                $table->unsignedBigInteger('assigned_ict_officer_id')->nullable()->after('head_of_it_rejection_reason');
            }
            if (! Schema::hasColumn('user_access', 'task_assigned_at')) {
                $table->timestamp('task_assigned_at')->nullable()->after('assigned_ict_officer_id');
            }

            // Request types field (legacy support)
            if (! Schema::hasColumn('user_access', 'request_types')) {
                $table->text('request_types')->nullable()->after('request_type'); // Store as JSON or comma-separated
            }

            // Foreign key constraints: only add if the column exists and users table is present
            if (Schema::hasTable('users')) {
                if (Schema::hasColumn('user_access', 'head_of_it_approved_by')) {
                    $table->foreign('head_of_it_approved_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('user_access', 'head_of_it_rejected_by')) {
                    $table->foreign('head_of_it_rejected_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('user_access', 'assigned_ict_officer_id')) {
                    $table->foreign('assigned_ict_officer_id')->references('id')->on('users')->onDelete('set null');
                }
            }

            // Indexes – only create when the corresponding column was just
            // introduced in this migration. This keeps us safe on DBs where
            // indexes may already exist.
            if (! Schema::hasColumn('user_access', 'request_id')) {
                $table->index(['request_id']);
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_approved_at')) {
                $table->index(['head_of_it_approved_at']);
            }
            if (! Schema::hasColumn('user_access', 'head_of_it_rejected_at')) {
                $table->index(['head_of_it_rejected_at']);
            }
            if (! Schema::hasColumn('user_access', 'assigned_ict_officer_id')) {
                $table->index(['assigned_ict_officer_id']);
            }
            if (! Schema::hasColumn('user_access', 'task_assigned_at')) {
                $table->index(['task_assigned_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_access', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['head_of_it_approved_by']);
            $table->dropForeign(['head_of_it_rejected_by']);
            $table->dropForeign(['assigned_ict_officer_id']);
            
            // Drop indexes
            $table->dropIndex(['request_id']);
            $table->dropIndex(['head_of_it_approved_at']);
            $table->dropIndex(['head_of_it_rejected_at']);
            $table->dropIndex(['assigned_ict_officer_id']);
            $table->dropIndex(['task_assigned_at']);
            
            // Drop columns
            $table->dropColumn([
                'request_id',
                'department_name',
                'position',
                'head_of_it_approved_at',
                'head_of_it_approved_by',
                'head_of_it_signature_path',
                'head_of_it_comments',
                'head_of_it_rejected_at',
                'head_of_it_rejected_by',
                'head_of_it_rejection_reason',
                'assigned_ict_officer_id',
                'task_assigned_at',
                'request_types'
            ]);
        });
    }
};
