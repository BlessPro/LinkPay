<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currency = config('services.paystack.currency', 'GHS');

        $customers = $this->buildCustomers($user->id);

        $filter = (string) $request->query('filter', 'all');
        $allowedFilters = ['all', 'new', 'returning', 'inactive', 'top_spenders'];
        if (! in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        $search = trim((string) $request->query('search', ''));

        $filtered = $customers;

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = $filtered->filter(function (array $customer) use ($needle) {
                return str_contains(mb_strtolower($customer['name']), $needle)
                    || str_contains(mb_strtolower($customer['phone'] ?? ''), $needle)
                    || str_contains(mb_strtolower($customer['email'] ?? ''), $needle);
            })->values();
        }

        $filtered = match ($filter) {
            'new' => $filtered->where('orders_count', 1)->values(),
            'returning' => $filtered->where('orders_count', '>', 1)->values(),
            'inactive' => $filtered->filter(fn (array $c) => $c['days_since_last'] > 30)->values(),
            'top_spenders' => $filtered->sortByDesc('total_spent')->values()->take(50),
            default => $filtered,
        };

        $filtered = $filtered->sortByDesc('last_purchase_at')->values();

        $overview = $this->buildOverview($customers);
        $segments = $this->buildSegments($customers);
        $topCustomers = $customers->sortByDesc('total_spent')->take(8)->values();
        $growth = $this->buildCustomerGrowth($customers, 6);
        $retention = $this->buildRetention($customers);
        $recentlyActive = $customers->sortByDesc('last_activity_at')->take(8)->values();
        $locations = $this->buildLocations($customers);

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $pageItems = $filtered->slice($offset, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => route('customers.index'),
                'query' => $request->query(),
            ]
        );

        return view('dashboard.customers.index', [
            'currency' => $currency,
            'customers' => $paginator,
            'overview' => $overview,
            'segments' => $segments,
            'topCustomers' => $topCustomers,
            'growth' => $growth,
            'retention' => $retention,
            'recentlyActive' => $recentlyActive,
            'locations' => $locations,
            'filter' => $filter,
            'search' => $search,
            'totalCustomers' => $customers->count(),
        ]);
    }

    public function show(Request $request, string $customer)
    {
        $decodedKey = $this->decodeCustomerKey($customer);
        if ($decodedKey === null) {
            abort(404);
        }

        $customers = $this->buildCustomers($request->user()->id)->keyBy('customer_key');
        $record = $customers->get($decodedKey);

        if (! $record) {
            abort(404);
        }

        return view('dashboard.customers.show', [
            'currency' => config('services.paystack.currency', 'GHS'),
            'customer' => $record,
        ]);
    }

    private function buildCustomers(int $userId): Collection
    {
        $orders = Order::query()
            ->with(['items', 'payments'])
            ->where('user_id', $userId)
            ->where('payment_status', Payment::STATUS_SUCCESS)
            ->latest('created_at')
            ->get();

        $directPayments = Payment::query()
            ->with('product')
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereNull('order_id')
            ->latest('created_at')
            ->get();

        $customers = [];

        foreach ($orders as $order) {
            $payment = $order->payments->firstWhere('status', Payment::STATUS_SUCCESS) ?? $order->payments->first();

            $name = trim((string) ($order->customer_name ?: data_get($payment?->raw_payload, 'customer.name') ?: 'Customer'));
            $phone = trim((string) ($order->customer_phone ?: data_get($payment?->raw_payload, 'customer.phone') ?: ''));
            $email = trim((string) (data_get($payment?->raw_payload, 'customer.email') ?: data_get($payment?->raw_payload, 'metadata.customer.email') ?: ''));
            $location = trim((string) ($order->customer_location ?: data_get($payment?->raw_payload, 'customer.location') ?: ''));

            $key = $this->makeCustomerKey($name, $phone, $email);
            $this->initCustomer($customers, $key, $name, $phone, $email, $location);

            $amount = (float) $order->total;
            $this->applyOrderToCustomer(
                $customers[$key],
                $order->created_at,
                $amount,
                $order->status,
                $order->items->map(function ($item) {
                    return [
                        'name' => (string) $item->product_name,
                        'quantity' => (int) $item->quantity,
                    ];
                })->all()
            );
        }

        foreach ($directPayments as $payment) {
            $name = trim((string) (data_get($payment->raw_payload, 'customer.name') ?: 'Customer'));
            $phone = trim((string) (data_get($payment->raw_payload, 'customer.phone') ?: data_get($payment->raw_payload, 'metadata.customer.phone') ?: ''));
            $email = trim((string) (data_get($payment->raw_payload, 'customer.email') ?: data_get($payment->raw_payload, 'metadata.customer.email') ?: data_get($payment->raw_payload, 'metadata.email') ?: ''));
            $location = trim((string) (data_get($payment->raw_payload, 'customer.location') ?: ''));

            $key = $this->makeCustomerKey($name, $phone, $email);
            $this->initCustomer($customers, $key, $name, $phone, $email, $location);

            $amount = (float) $payment->amount;
            $productName = $payment->product?->name ?? data_get($payment->raw_payload, 'product.name') ?? 'Payment';

            $this->applyOrderToCustomer(
                $customers[$key],
                $payment->created_at,
                $amount,
                'DELIVERED',
                [[
                    'name' => (string) $productName,
                    'quantity' => 1,
                ]]
            );
        }

        $collection = collect(array_values($customers))->map(function (array $customer) {
            $customer['avg_order_value'] = $customer['orders_count'] > 0
                ? round($customer['total_spent'] / $customer['orders_count'], 2)
                : 0.0;

            $customer['days_since_last'] = $customer['last_purchase_at']
                ? $customer['last_purchase_at']->diffInDays(now())
                : 999;
            $customer['status'] = $this->deriveStatus($customer);
            $customer['favorite_products'] = collect($customer['favorite_products'])
                ->sortByDesc('count')
                ->values()
                ->all();
            $customer['encoded_key'] = $this->encodeCustomerKey($customer['customer_key']);

            return $customer;
        });

        return $collection->values();
    }

    private function buildOverview(Collection $customers): array
    {
        $totalCustomers = $customers->count();
        $thisMonthStart = now()->startOfMonth();
        $newThisMonth = $customers->filter(fn (array $c) => $c['first_purchase_at'] && $c['first_purchase_at']->gte($thisMonthStart))->count();

        $returning = $customers->filter(fn (array $c) => $c['orders_count'] > 1)->count();
        $returnRate = $totalCustomers > 0 ? round(($returning / $totalCustomers) * 100, 1) : 0.0;

        $totalRevenue = (float) $customers->sum('total_spent');
        $totalOrders = (int) $customers->sum('orders_count');

        return [
            'totalCustomers' => $totalCustomers,
            'newThisMonth' => $newThisMonth,
            'returningCustomers' => $returning,
            'returnRate' => $returnRate,
            'avgCustomerValue' => $totalCustomers > 0 ? round($totalRevenue / $totalCustomers, 2) : 0.0,
            'ordersPerCustomer' => $totalCustomers > 0 ? round($totalOrders / $totalCustomers, 1) : 0.0,
        ];
    }

    private function buildSegments(Collection $customers): array
    {
        return [
            'vip' => $customers->filter(fn (array $c) => $c['total_spent'] > 500)->count(),
            'regular' => $customers->filter(fn (array $c) => $c['orders_count'] >= 2 && $c['orders_count'] <= 4)->count(),
            'new' => $customers->filter(fn (array $c) => $c['orders_count'] === 1)->count(),
            'inactive' => $customers->filter(fn (array $c) => $c['days_since_last'] > 30)->count(),
        ];
    }

    private function buildCustomerGrowth(Collection $customers, int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $rows = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $count = $customers->filter(function (array $customer) use ($month) {
                return $customer['first_purchase_at']
                    && $customer['first_purchase_at']->year === $month->year
                    && $customer['first_purchase_at']->month === $month->month;
            })->count();

            $rows[] = [
                'month' => $month->format('F'),
                'count' => $count,
            ];
        }

        return $rows;
    }

    private function buildRetention(Collection $customers): array
    {
        $total = $customers->count();
        $returning = $customers->filter(fn (array $c) => $c['orders_count'] > 1)->count();

        return [
            'total' => $total,
            'returning' => $returning,
            'rate' => $total > 0 ? round(($returning / $total) * 100, 1) : 0.0,
        ];
    }

    private function buildLocations(Collection $customers): array
    {
        return $customers
            ->filter(fn (array $c) => ! empty($c['location']))
            ->map(function (array $c) {
                $location = trim((string) $c['location']);
                $parts = array_map('trim', explode(',', $location));

                return $parts[0] ?? $location;
            })
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->map(fn ($count, $city) => ['city' => $city, 'count' => $count])
            ->values()
            ->all();
    }

    private function initCustomer(array &$customers, string $key, string $name, string $phone, string $email, string $location): void
    {
        if (isset($customers[$key])) {
            if ($customers[$key]['name'] === 'Customer' && $name !== '' && $name !== 'Customer') {
                $customers[$key]['name'] = $name;
            }
            if ($customers[$key]['phone'] === '' && $phone !== '') {
                $customers[$key]['phone'] = $phone;
            }
            if ($customers[$key]['email'] === '' && $email !== '') {
                $customers[$key]['email'] = $email;
            }
            if ($customers[$key]['location'] === '' && $location !== '') {
                $customers[$key]['location'] = $location;
            }

            return;
        }

        $customers[$key] = [
            'customer_key' => $key,
            'name' => $name !== '' ? $name : 'Customer',
            'phone' => $phone,
            'email' => $email,
            'location' => $location,
            'orders_count' => 0,
            'total_spent' => 0.0,
            'first_purchase_at' => null,
            'last_purchase_at' => null,
            'last_activity_at' => null,
            'purchase_history' => [],
            'favorite_products' => [],
        ];
    }

    private function applyOrderToCustomer(array &$customer, Carbon $date, float $amount, string $status, array $items): void
    {
        $customer['orders_count']++;
        $customer['total_spent'] += $amount;

        if (! $customer['first_purchase_at'] || $date->lt($customer['first_purchase_at'])) {
            $customer['first_purchase_at'] = $date->copy();
        }

        if (! $customer['last_purchase_at'] || $date->gt($customer['last_purchase_at'])) {
            $customer['last_purchase_at'] = $date->copy();
        }

        if (! $customer['last_activity_at'] || $date->gt($customer['last_activity_at'])) {
            $customer['last_activity_at'] = $date->copy();
        }

        $itemNames = collect($items)
            ->map(fn (array $item) => trim((string) ($item['name'] ?? 'Item')))
            ->filter()
            ->values();

        foreach ($items as $item) {
            $itemName = trim((string) ($item['name'] ?? 'Item'));
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            if (! isset($customer['favorite_products'][$itemName])) {
                $customer['favorite_products'][$itemName] = [
                    'name' => $itemName,
                    'count' => 0,
                ];
            }
            $customer['favorite_products'][$itemName]['count'] += $qty;
        }

        $customer['purchase_history'][] = [
            'date' => $date->copy(),
            'product' => $itemNames->isNotEmpty() ? $itemNames->join(', ') : 'Payment',
            'amount' => round($amount, 2),
            'status' => $this->mapOrderStatus($status),
        ];

        usort($customer['purchase_history'], function (array $a, array $b) {
            return $b['date']->timestamp <=> $a['date']->timestamp;
        });
    }

    private function deriveStatus(array $customer): string
    {
        $daysSinceLast = (int) ($customer['days_since_last'] ?? (
            isset($customer['last_purchase_at']) && $customer['last_purchase_at']
                ? $customer['last_purchase_at']->diffInDays(now())
                : 999
        ));

        if ($daysSinceLast > 30) {
            return 'Inactive';
        }

        if ($customer['orders_count'] >= 4 || $customer['total_spent'] > 500) {
            return 'Loyal';
        }

        return 'Active';
    }

    private function mapOrderStatus(string $status): string
    {
        return match ($status) {
            Order::STATUS_ACCEPTED, Order::STATUS_PAID => 'Delivered',
            Order::STATUS_PENDING_PAYMENT => 'Pending',
            Order::STATUS_CANNOT_FULFILL => 'Failed',
            default => 'Delivered',
        };
    }

    private function makeCustomerKey(string $name, string $phone, string $email): string
    {
        if ($phone !== '') {
            return 'phone:'.preg_replace('/\s+/', '', $phone);
        }

        if ($email !== '') {
            return 'email:'.strtolower($email);
        }

        return 'name:'.md5(strtolower(trim($name)));
    }

    private function encodeCustomerKey(string $key): string
    {
        return rtrim(strtr(base64_encode($key), '+/', '-_'), '=');
    }

    private function decodeCustomerKey(string $encoded): ?string
    {
        $padded = strtr($encoded, '-_', '+/');
        $padding = strlen($padded) % 4;
        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
