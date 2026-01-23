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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('assigned_to');  // ICT Officer user ID
            $table->unsignedBigInteger('assigned_by');  // Head of IT user ID
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('description')->nullable();
            $table->text('progress_notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('estimated_completion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('request_id')->references('id')->on('user_access')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better performance
            $table->index(['request_id']);
            $table->index(['assigned_to', 'status']);
            $table->index(['assigned_by']);
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['assigned_at']);
            $table->index(['estimated_completion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
