<?php

use App\Mail\WeeklySellerPerformanceSummary;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:seed {--reset} {--force}', function () {
    if (app()->environment('production') && ! $this->option('force')) {
        $this->error('Refusing to run demo seeding in production without --force.');
        return 1;
    }

    if ($this->option('reset')) {
        DB::statement('TRUNCATE TABLE payments, order_items, orders, coupons, products, invoices, seller_profiles, users RESTART IDENTITY CASCADE');
        $this->warn('Demo reset completed.');
    }

    Artisan::call('db:seed', [
        '--class' => DemoDataSeeder::class,
        '--force' => true,
    ]);

    $this->info('Demo data seeded.');
    $this->line('Admin: admin@8kommerce.demo / password');
    $this->line('Seller sample: apex@8kommerce.demo / password');
})->purpose('Seed deterministic demo data for staging/local demos');

Artisan::command('ops:smoke:http {--base-url=} {--timeout=10}', function () {
    $base = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
    $timeout = max(2, (int) $this->option('timeout'));

    if ($base === '') {
        $this->error('Missing base URL. Set APP_URL or pass --base-url=');
        return 1;
    }

    $checks = [
        ['label' => 'Landing', 'path' => '/'],
        ['label' => 'Pricing', 'path' => '/pricing'],
        ['label' => 'Sellers', 'path' => '/sellers'],
        ['label' => 'Privacy', 'path' => '/privacy'],
        ['label' => 'Terms', 'path' => '/terms'],
        ['label' => 'Register', 'path' => '/register'],
        ['label' => 'Login', 'path' => '/login'],
        ['label' => 'Admin Login', 'path' => '/admin/login'],
    ];

    $rows = [];
    $hasFailure = false;

    foreach ($checks as $check) {
        $url = $base.$check['path'];
        try {
            $response = Http::timeout($timeout)->get($url);
            $ok = $response->successful();
            $rows[] = [$check['label'], $url, $response->status(), $ok ? 'PASS' : 'FAIL'];
            if (! $ok) {
                $hasFailure = true;
            }
        } catch (\Throwable $exception) {
            $rows[] = [$check['label'], $url, 'ERR', 'FAIL'];
            $hasFailure = true;
        }
    }

    $this->table(['Check', 'URL', 'Status', 'Result'], $rows);

    if ($hasFailure) {
        $this->error('Smoke HTTP checks failed.');
        return 1;
    }

    $this->info('Smoke HTTP checks passed.');
})->purpose('Run launch smoke checks against public/admin entry routes');

Artisan::command('ops:smoke:test-suite', function () {
    $this->line('Running auth + checkout + webhook smoke feature tests...');

    $exitCode = Artisan::call('test', [
        '--filter' => 'AuthenticationTest|PublicCheckoutSmokeTest|WebhookIdempotencyTest',
    ]);

    $this->output->write(Artisan::output());

    if ($exitCode !== 0) {
        $this->error('Smoke test suite failed.');
        return $exitCode;
    }

    $this->info('Smoke test suite passed.');
    return 0;
})->purpose('Run key smoke feature tests (register/login/checkout/webhook)');

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

        $critical = (int) ($report['severityBuckets']['critical'] ?? 0);
        $high = (int) ($report['severityBuckets']['high'] ?? 0);
        $criticalThreshold = (int) config('monitoring.reconciliation.critical_threshold', 1);
        $highThreshold = (int) config('monitoring.reconciliation.high_threshold', 3);

        if ($critical >= $criticalThreshold || $high >= $highThreshold) {
            AdminAuditLog::create([
                'admin_user_id' => $adminId,
                'action' => 'payment.reconciliation.alert',
                'target_type' => 'system',
                'target_id' => null,
                'title' => 'Reconciliation threshold breached',
                'meta' => [
                    'days' => $report['days'],
                    'critical' => $critical,
                    'high' => $high,
                    'critical_threshold' => $criticalThreshold,
                    'high_threshold' => $highThreshold,
                ],
                'ip_address' => null,
            ]);
        }
    }
})->purpose('Run Paystack vs DB reconciliation report');

Schedule::command('payments:reconcile --days=1')->dailyAt('03:30');

Artisan::command('ops:failed-jobs:alert {--hours=24} {--threshold=1}', function () {
    $hours = max(1, (int) $this->option('hours'));
    $threshold = max(1, (int) $this->option('threshold'));

    $since = now()->subHours($hours);
    $total = DB::table(config('queue.failed.table', 'failed_jobs'))
        ->where('failed_at', '>=', $since)
        ->count();

    $this->info('Failed jobs in last '.$hours.'h: '.$total);

    if ($total < $threshold) {
        return;
    }

    Log::warning('Failed jobs threshold reached', [
        'hours' => $hours,
        'threshold' => $threshold,
        'total' => $total,
    ]);

    $adminId = User::query()->where('is_admin', true)->value('id');
    if (! $adminId) {
        return;
    }

    AdminAuditLog::create([
        'admin_user_id' => $adminId,
        'action' => 'queue.failed_jobs.alert',
        'target_type' => 'system',
        'target_id' => null,
        'title' => 'Failed jobs threshold reached',
        'meta' => [
            'hours' => $hours,
            'threshold' => $threshold,
            'total' => $total,
        ],
        'ip_address' => null,
    ]);
})->purpose('Alert when failed jobs exceed threshold in a time window');

Schedule::command('ops:failed-jobs:alert --hours=24 --threshold=1')->hourly();

Artisan::command('ops:failed-jobs:retry-latest {--limit=25} {--dry-run}', function () {
    $limit = max(1, min(200, (int) $this->option('limit')));
    $dryRun = (bool) $this->option('dry-run');
    $table = config('queue.failed.table', 'failed_jobs');

    $failed = DB::table($table)
        ->orderByDesc('failed_at')
        ->limit($limit)
        ->get(['id', 'queue', 'failed_at']);

    if ($failed->isEmpty()) {
        $this->info('No failed jobs found.');
        return 0;
    }

    $this->table(
        ['ID', 'Queue', 'Failed At'],
        $failed->map(fn ($job) => [$job->id, $job->queue, $job->failed_at])->all()
    );

    if ($dryRun) {
        $this->warn('Dry run only; no retries executed.');
        return 0;
    }

    $retried = 0;
    foreach ($failed as $job) {
        Artisan::call('queue:retry', ['id' => (string) $job->id]);
        $retried++;
    }

    $adminId = User::query()->where('is_admin', true)->value('id');
    if ($adminId) {
        AdminAuditLog::create([
            'admin_user_id' => $adminId,
            'action' => 'queue.failed_jobs.retry_latest',
            'target_type' => 'system',
            'target_id' => null,
            'title' => 'Retry latest failed jobs',
            'meta' => [
                'limit' => $limit,
                'retried' => $retried,
            ],
            'ip_address' => null,
        ]);
    }

    $this->info("Retried {$retried} failed job(s).");
    return 0;
})->purpose('Retry latest failed jobs in bulk with optional dry-run');

Artisan::command('sellers:weekly-performance-email', function () {
    $from = now()->subDays(6)->startOfDay();
    $to = now()->endOfDay();
    $currency = config('services.paystack.currency', 'GHS');

    $sent = 0;
    $skipped = 0;

    User::query()
        ->whereNotNull('email')
        ->has('sellerProfile')
        ->chunkById(100, function ($sellers) use ($from, $to, $currency, &$sent, &$skipped) {
            foreach ($sellers as $seller) {
                $revenue = (string) $seller->payments()
                    ->where('status', \App\Models\Payment::STATUS_SUCCESS)
                    ->whereBetween('created_at', [$from, $to])
                    ->sum('amount');

                $paidOrders = $seller->orders()
                    ->where('status', Order::STATUS_PAID)
                    ->whereBetween('created_at', [$from, $to])
                    ->count();

                $newCustomers = $seller->orders()
                    ->where('status', Order::STATUS_PAID)
                    ->whereBetween('created_at', [$from, $to])
                    ->distinct('customer_phone')
                    ->count('customer_phone');

                $topProduct = OrderItem::query()
                    ->selectRaw('product_name, SUM(quantity) as qty')
                    ->whereHas('order', function ($query) use ($seller, $from, $to) {
                        $query->where('user_id', $seller->id)
                            ->where('status', Order::STATUS_PAID)
                            ->whereBetween('created_at', [$from, $to]);
                    })
                    ->groupBy('product_name')
                    ->orderByDesc('qty')
                    ->value('product_name');

                if ($revenue === '0' && $paidOrders === 0 && $newCustomers === 0 && $topProduct === null) {
                    $skipped++;
                    continue;
                }

                Mail::to($seller->email)->send(new WeeklySellerPerformanceSummary([
                    'seller_name' => $seller->sellerProfile?->business_name ?? $seller->name,
                    'revenue' => $revenue,
                    'paid_orders' => $paidOrders,
                    'new_customers' => $newCustomers,
                    'top_product' => $topProduct,
                    'currency' => $currency,
                ]));

                $sent++;
            }
        });

    $this->info("Weekly summaries sent: {$sent}. Skipped: {$skipped}.");
})->purpose('Send weekly seller performance summary emails');

Schedule::command('sellers:weekly-performance-email')->weeklyOn(1, '08:00');
