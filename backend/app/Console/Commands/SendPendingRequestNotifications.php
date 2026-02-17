<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SendPendingRequestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-pending {--request-id= : Specific request ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send database notifications for pending HOD approval requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Sending database notifications for pending requests...');
        
        $requestId = $this->option('request-id');
        
        if ($requestId) {
            return $this->processSingleRequest($requestId);
        } else {
            return $this->processAllPendingRequests();
        }
    }
    
    protected function processSingleRequest($requestId)
    {
        try {
            $request = UserAccess::with(['department'])->find($requestId);
            
            if (!$request) {
                $this->error("❌ Request ID {$requestId} not found!");
                return 1;
            }
            
            return $this->sendNotification($request);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }
    }
    
    protected function processAllPendingRequests()
    {
        $pendingRequests = UserAccess::with(['department'])
            ->where(function($q) {
                $q->whereNull('hod_status')
                  ->orWhere('hod_status', 'pending');
            })
            ->get();
        
        if ($pendingRequests->isEmpty()) {
            $this->info('✅ No pending requests found.');
            return 0;
        }
        
        $this->info("📊 Found {$pendingRequests->count()} pending request(s)");
        
        $sent = 0;
        $failed = 0;
        
        foreach ($pendingRequests as $request) {
            try {
                $this->line("\n📤 Processing Request #{$request->id} - {$request->staff_name}");
                
                if ($this->sendNotification($request) === 0) {
                    $sent++;
                } else {
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: {$e->getMessage()}");
                $failed++;
            }
        }
        
        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("  ✅ Sent: {$sent}");
        $this->info("  ❌ Failed: {$failed}");
        
        return 0;
    }
    
    protected function sendNotification($request)
    {
        // Get HOD for this department - try direct relationship first (more reliable)
        $department = \App\Models\Department::with('hod')->find($request->department_id);
        $hod = $department?->hod;
        
        // Fallback: try the reverse relationship query if hod relation didn't work
        if (!$hod) {
            $hod = User::whereHas('departmentsAsHOD', function($q) use ($request) {
                $q->where('id', $request->department_id);
            })->first();
        }
        
        if (!$hod) {
            $this->warn("  ⚠️  No HOD found for department ID: {$request->department_id}");
            $this->warn("     Department name: " . ($department?->name ?? 'Unknown'));
            $this->warn("     Department hod_user_id: " . ($department?->hod_user_id ?? 'Not set'));
            return 1;
        }
        
        $this->line("  📱 Sending notification to HOD: {$hod->name}");
        
        // Determine request type
        $types = [];
        if ($request->jeeva_access) $types[] = 'Jeeva';
        if ($request->wellsoft_access) $types[] = 'Wellsoft';
        if ($request->internet_access) $types[] = 'Internet';
        $requestType = implode(' & ', $types) ?: 'Access';
        
        $requestId = 'REQ-' . str_pad($request->id, 6, '0', STR_PAD_LEFT);
        $department = $request->department->name ?? 'Unknown';
        
        // Insert notification directly into database
        DB::table('notifications')->insert([
            'recipient_id' => $hod->id,
            'sender_id' => $request->user_id,
            'access_request_id' => $request->id,
            'type' => 'new_access_request',
            'title' => 'New Access Request',
            'message' => "New {$requestType} request from {$request->staff_name} ({$department})",
            'data' => json_encode([
                'request_id' => $request->id,
                'request_number' => $requestId,
                'staff_name' => $request->staff_name,
                'department' => $department,
                'request_type' => $requestType,
                'status' => 'pending',
                'action_url' => "/hod/combined-access-requests/{$request->id}",
            ]),
            'read_at' => null,
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $hod->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->info("  ✅ Notification sent!");
        return 0;
    }
}
