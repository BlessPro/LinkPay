<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $ogImage = $product->image_path
            ? url('storage/'.$product->image_path)
            : asset('images/og-default.png');

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
