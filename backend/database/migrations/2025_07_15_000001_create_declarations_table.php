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
        // Ensure users table exists before creating foreign key
        if (! Schema::hasTable('users')) {
            throw new Exception('Users table must exist before creating declarations table. Please run users migration first.');
        }

        // If declarations table already exists (e.g. on production), do not
        // attempt to recreate it. This makes the migration idempotent.
        if (! Schema::hasTable('declarations')) {
            Schema::create('declarations', function (Blueprint $table) {
                $table->id();
                
                // Create user_id column with explicit type to ensure compatibility
                $table->unsignedBigInteger('user_id');
                
                $table->string('full_name');
                $table->string('pf_number'); // Remove unique constraint initially to avoid conflicts
                $table->string('department');
                $table->string('job_title');
                $table->date('signature_date');
                $table->boolean('agreement_accepted')->default(false);
                $table->json('signature_info')->nullable(); // Store signature file information
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('submitted_at');
                $table->timestamps();

                // Add indexes for better performance
                $table->index(['user_id', 'pf_number']);
                $table->index('submitted_at');
                $table->index('pf_number');
                
                // Add foreign key constraint
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            });

            // Add unique constraint on pf_number after table creation (safer).
            try {
                Schema::table('declarations', function (Blueprint $table) {
                    $table->unique('pf_number');
                });
            } catch (Exception $e) {
                // Log warning but don't fail the migration
                error_log('Warning: Could not add unique constraint on pf_number: ' . $e->getMessage());
            }
        }
    }

    /**\n     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declarations');
    }
};