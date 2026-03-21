<?php

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:reconcile {--days=1}', function (PaymentReconciliationService $reconciliation) {
    $days = (int) $this->option('days');
    $report = $reconciliation->buildReport($days);

    $this->info('Reconciliation complete.');
    $this->table(
        ['Metric', 'Value'],
        [
            ['Period (days)', $report['days']],
            ['Local total', $report['localTotal']],
            ['Paystack total', $report['paystackTotal']],
            ['Matched', $report['statusCounts']['matched']],
            ['Missing in DB', $report['statusCounts']['missing_in_db']],
            ['Missing in Paystack', $report['statusCounts']['missing_in_paystack']],
            ['Amount mismatch', $report['statusCounts']['amount_mismatch']],
            ['Status mismatch', $report['statusCounts']['status_mismatch']],
            ['Duplicate reference', $report['statusCounts']['duplicate_reference']],
        ]
    );

    $adminId = User::query()->where('is_admin', true)->value('id');
    if ($adminId) {
        AdminAuditLog::create([
            'admin_user_id' => $adminId,
            'action' => 'payment.reconciliation.snapshot',
            'target_type' => 'system',
            'target_id' => null,
            'title' => 'Scheduled reconciliation snapshot',
            'meta' => [
                'days' => $report['days'],
                'localTotal' => $report['localTotal'],
                'paystackTotal' => $report['paystackTotal'],
                'statusCounts' => $report['statusCounts'],
            ],
            'ip_address' => null,
        ]);
    }
})->purpose('Run Paystack vs DB reconciliation report');

Schedule::command('payments:reconcile --days=1')->dailyAt('03:30');
