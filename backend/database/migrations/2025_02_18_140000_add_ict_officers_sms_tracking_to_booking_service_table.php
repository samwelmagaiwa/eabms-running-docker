<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds SMS tracking for ICT Officers notification when a booking is created.
     * This is separate from sms_to_requester_status which tracks SMS sent to the
     * requester when their booking is approved.
     */
    public function up(): void
    {
        Schema::table('booking_service', function (Blueprint $table) {
            // Track SMS notification to ICT Officers (sent on booking creation)
            $table->timestamp('sms_sent_to_ict_officers_at')->nullable()->after('sms_notifications');
            $table->string('sms_to_ict_officers_status')->default('pending')->after('sms_sent_to_ict_officers_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_service', function (Blueprint $table) {
            $table->dropColumn([
                'sms_sent_to_ict_officers_at',
                'sms_to_ict_officers_status',
            ]);
        });
    }
};
