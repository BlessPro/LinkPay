<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OgImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PublicProductController extends Controller
{
    public function show(string $product_slug, Request $request)
    {
        $product = Product::query()
            ->where('slug', $product_slug)
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->whereHas('user.sellerProfile', fn ($q) => $q->whereNotNull('public_slug'))
            ->with(['user.sellerProfile'])
            ->firstOrFail();

        $profile = $product->user->sellerProfile;

        $sellerName = $profile?->business_name ?: 'Seller';
        $currency = config('services.paystack.currency', 'GHS');

        $shortDescription = $product->description
            ? Str::limit($product->description, 120)
            : 'Beautiful product ready for payment.';

        $ogService = app(OgImageService::class);
        $ogPath = $ogService->publicProductOgPath($product->id);
        $shouldGenerate = ! Storage::disk('public')->exists($ogPath);
        if (! $shouldGenerate) {
            try {
                $absolutePath = Storage::disk('public')->path($ogPath);
                $mtime = @filemtime($absolutePath);
                $shouldGenerate = ! $mtime || $product->updated_at?->timestamp > $mtime;
            } catch (\Throwable $e) {
                $shouldGenerate = false;
            }
        }
        if ($shouldGenerate) {
            try {
                $product->loadMissing('user.sellerProfile');
                $ogService->generateProduct($product);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        $ogImage = Storage::disk('public')->exists($ogPath)
            ? url(Storage::url($ogPath))
            : url('/images/og-default.jpg');

        return view('public.product', [
            'product' => $product,
            'profile' => $profile,
            'currency' => $currency,
            'paymentsEnabled' => $product->user?->canUsePaymentsFeature() ?? false,
            'smallDescription' => $shortDescription,
            'ogTitle' => "{$product->name} - {$sellerName}",
            'ogDescription' => "{$shortDescription} Price: {$currency} ".number_format((float) $product->price, 2, '.', ','),
            'ogImage' => $ogImage,
            'ogUrl' => route('public.product', $product->slug),
            'ogType' => 'website',
        ]);
    }
}
