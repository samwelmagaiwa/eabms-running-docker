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
        // Only rename on legacy databases where `request_types` exists
        // and `request_type` does not yet exist. On fresh installs, the
        // canonical column is already `request_type`, so we skip.
        if (Schema::hasTable('user_access') &&
            Schema::hasColumn('user_access', 'request_types') &&
            ! Schema::hasColumn('user_access', 'request_type')) {
            Schema::table('user_access', function (Blueprint $table) {
                // Rename request_types column to request_type
                $table->renameColumn('request_types', 'request_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_access') &&
            Schema::hasColumn('user_access', 'request_type') &&
            ! Schema::hasColumn('user_access', 'request_types')) {
            Schema::table('user_access', function (Blueprint $table) {
                // Revert back: rename request_type to request_types
                $table->renameColumn('request_type', 'request_types');
            });
        }
    }
};
