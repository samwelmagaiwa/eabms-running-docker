<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('user_access', 'status')) {
            Schema::table('user_access', function (Blueprint $table) {
                // Add status column after request_type
                $table->string('status')->default('pending')->after('request_type');
            });

            // Optional: Backfill status based on other columns if needed
            // For now, default 'pending' handles new rows, but existing rows might need logic.
            // Let's do a basic update for implemented requests.
            DB::table('user_access')
                ->where('ict_officer_status', 'implemented')
                ->update(['status' => 'completed']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_access', 'status')) {
            Schema::table('user_access', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
