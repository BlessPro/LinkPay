<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FailedJobsAlertCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_jobs_alert_command_creates_admin_audit_log_when_threshold_is_met(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        Artisan::call('ops:failed-jobs:alert', [
            '--hours' => 24,
            '--threshold' => 1,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'queue.failed_jobs.alert',
            'title' => 'Failed jobs threshold reached',
        ]);

        $log = AdminAuditLog::query()
            ->where('action', 'queue.failed_jobs.alert')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(24, (int) ($log->meta['hours'] ?? 0));
        $this->assertSame(1, (int) ($log->meta['threshold'] ?? 0));
        $this->assertSame(1, (int) ($log->meta['total'] ?? 0));
    }
}
