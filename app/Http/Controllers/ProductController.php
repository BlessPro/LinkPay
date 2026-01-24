<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->products()->latest()->paginate(10);

        return view('dashboard.products.index', [
            'products' => $products,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function create()
    {
        return view('dashboard.products.create');
    }

    public function store(CreateProductRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('status', 'product-created');
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);

        $stats = $this->buildStats($product);

        return view('dashboard.products.edit', [
            'product' => $product,
            'stats' => $stats,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function update(CreateProductRequest $request, Product $product)
    {
        $this->authorizeProduct($product);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('status', 'product-updated');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'product-deleted');
    }

    private function authorizeProduct(Product $product): void
    {
        abort_unless($product->user_id === auth()->id(), 403);
    }

    private function buildStats(Product $product): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $events = AnalyticsEvent::where('user_id', $product->user_id)
            ->where('entity_type', 'product')
            ->where('entity_id', (string) $product->id)
            ->whereBetween('created_at', [$start, $end]);

        $impressions = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)->count();
        $impressionsUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)
            ->distinct('session_hash')->count('session_hash');
        $clicks = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count();
        $clicksUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)
            ->distinct('session_hash')->count('session_hash');

        $payments = Payment::where('product_id', $product->id)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $paymentTotal = '0.00';
        foreach ($payments as $payment) {
            $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
        }

        return [
            'impressions' => $impressions,
            'impressionsUnique' => $impressionsUnique,
            'clicks' => $clicks,
            'clicksUnique' => $clicksUnique,
            'payments' => $payments->count(),
            'paymentTotal' => $paymentTotal,
            'conversion' => $clicks > 0 ? round(($payments->count() / $clicks) * 100, 1) : 0.0,
        ];
    }
}
