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
        if (! Schema::hasTable('roles')) {
         Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, nurse, ict, etc.
            $table->string('display_name')->nullable(); // Human readable name
            $table->text('description')->nullable(); // Role description
            $table->json('permissions')->nullable(); // Role permissions
            $table->integer('sort_order')->default(0); // For ordering roles
            $table->boolean('is_system_role')->default(false); // System vs custom roles
            $table->boolean('is_deletable')->default(true); // Whether role can be deleted
            $table->timestamps();
            
            // Add indexes
            $table->index('sort_order');
            $table->index('is_system_role');
            $table->index('is_deletable');
        });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
