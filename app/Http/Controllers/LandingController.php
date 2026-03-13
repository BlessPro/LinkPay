<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LandingController extends Controller
{
    public function index()
    {
        $currency = config('services.paystack.currency', 'GHS');
        $allSellers = $this->buildSellerCollection();

        $featuredSellers = $allSellers
            ->sortBy([
                ['revenue', 'desc'],
                ['business_name', 'asc'],
            ])
            ->values()
            ->take(4);

        $topProducts = $featuredSellers
            ->map(function (array $seller) {
                $product = $this->topProductForSeller((int) $seller['profile']->user_id);
                if (! $product) {
                    return null;
                }

                return [
                    'seller_name' => $seller['business_name'],
                    'seller_slug' => $seller['slug'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'price' => $product['price'],
                    'image_path' => $product['image_path'],
                    'revenue' => $product['revenue'],
                ];
            })
            ->filter()
            ->values();

        return view('welcome', [
            'currency' => $currency,
            'featuredSellers' => $featuredSellers,
            'topProducts' => $topProducts,
        ]);
    }

    public function sellers(Request $request)
    {
        $sellers = $this->buildSellerCollection();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $sellers = $sellers->filter(function (array $seller) use ($needle) {
                return str_contains(mb_strtolower($seller['business_name']), $needle)
                    || str_contains(mb_strtolower($seller['category']), $needle);
            })->values();
        }

        $categories = $this->categoryOptions($this->buildSellerCollection());
        $category = trim((string) $request->query('category', ''));
        if ($category !== '') {
            $sellers = $sellers->where('category', $category)->values();
        }

        $sort = (string) $request->query('sort', 'alpha');
        if ($sort === 'performance') {
            $sellers = $sellers->sortBy([
                ['revenue', 'desc'],
                ['business_name', 'asc'],
            ])->values();
        } else {
            $sort = 'alpha';
            $sellers = $sellers->sortBy('business_name')->values();
        }

        $perPage = 16;
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $pageItems = $sellers->slice($offset, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $sellers->count(),
            $perPage,
            $page,
            [
                'path' => route('marketplace.sellers'),
                'query' => $request->query(),
            ]
        );

        return view('marketplace.sellers', [
            'sellers' => $paginator,
            'search' => $search,
            'selectedCategory' => $category,
            'sort' => $sort,
            'categories' => $categories,
        ]);
    }

    private function buildSellerCollection(): Collection
    {
        $sellerProfiles = SellerProfile::query()
            ->with(['user.products'])
            ->whereNotNull('public_slug')
            ->where('public_slug', '!=', '')
            ->get();

        $sellerPerformance = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->selectRaw('user_id, SUM(amount) as total_revenue, COUNT(*) as payment_count')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return $sellerProfiles->map(function (SellerProfile $profile) use ($sellerPerformance) {
            $perf = $sellerPerformance->get($profile->user_id);
            $revenue = (float) ($perf->total_revenue ?? 0);
            $payments = (int) ($perf->payment_count ?? 0);
            $productCount = $profile->user?->products?->where('is_active', true)->count() ?? 0;
            $category = $this->inferCategory($profile);

            return [
                'profile' => $profile,
                'business_name' => $profile->business_name,
                'slug' => $profile->public_slug,
                'category' => $category,
                'product_count' => $productCount,
                'rating' => $this->inferRating($revenue, $payments),
                'revenue' => $revenue,
                'payments' => $payments,
            ];
        })->values();
    }

    private function categoryOptions(Collection $sellers): array
    {
        return $sellers
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function topProductForSeller(int $userId): ?array
    {
        $orderItemRows = OrderItem::query()
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('payment_status', Payment::STATUS_SUCCESS);
            })
            ->selectRaw('product_id, product_name, SUM(line_total) as total_revenue')
            ->groupBy('product_id', 'product_name')
            ->get();

        $directProductRows = Payment::query()
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereNull('order_id')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(amount) as total_revenue')
            ->groupBy('product_id')
            ->get();

        $products = Product::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get(['id', 'name', 'slug', 'price', 'image_path'])
            ->keyBy('id');

        $revenues = [];

        foreach ($orderItemRows as $row) {
            if (! $row->product_id) {
                continue;
            }
            $id = (int) $row->product_id;
            $revenues[$id] = ($revenues[$id] ?? 0.0) + (float) ($row->total_revenue ?? 0);
        }

        foreach ($directProductRows as $row) {
            if (! $row->product_id) {
                continue;
            }
            $id = (int) $row->product_id;
            $revenues[$id] = ($revenues[$id] ?? 0.0) + (float) ($row->total_revenue ?? 0);
        }

        if (empty($revenues)) {
            $fallback = $products->first();
            if (! $fallback) {
                return null;
            }

            return [
                'id' => $fallback->id,
                'name' => $fallback->name,
                'slug' => $fallback->slug,
                'price' => (float) $fallback->price,
                'image_path' => $fallback->image_path,
                'revenue' => 0.0,
            ];
        }

        arsort($revenues);
        $topProductId = (int) array_key_first($revenues);
        $topProduct = $products->get($topProductId);

        if (! $topProduct) {
            return null;
        }

        return [
            'id' => $topProduct->id,
            'name' => $topProduct->name,
            'slug' => $topProduct->slug,
            'price' => (float) $topProduct->price,
            'image_path' => $topProduct->image_path,
            'revenue' => (float) ($revenues[$topProductId] ?? 0.0),
        ];
    }

    private function inferCategory(SellerProfile $profile): string
    {
        $text = strtolower($profile->business_name.' '.($profile->user?->products?->pluck('name')->implode(' ') ?? ''));

        $map = [
            'electronics' => ['gadget', 'phone', 'earbud', 'tech', 'electronic', 'laptop'],
            'fashion' => ['fashion', 'cloth', 'wear', 'shirt', 'shoe', 'bag'],
            'beauty' => ['beauty', 'hair', 'skin', 'cosmetic', 'makeup'],
            'home' => ['home', 'kitchen', 'furniture', 'decor'],
        ];

        foreach ($map as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return match ($category) {
                        'electronics' => 'Electronics',
                        'fashion' => 'Fashion',
                        'beauty' => 'Beauty & Care',
                        'home' => 'Home Essentials',
                        default => 'General',
                    };
                }
            }
        }

        return 'General';
    }

    private function inferRating(float $revenue, int $payments): float
    {
        if ($payments <= 0) {
            return 4.6;
        }

        $score = 4.6 + min(0.4, ($payments / 300)) + min(0.2, ($revenue / 50000));

        return round(min(5.0, $score), 1);
    }
}
