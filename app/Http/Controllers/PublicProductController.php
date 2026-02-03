<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicProductController extends Controller
{
    public function show(string $product_slug, Request $request)
    {
        $product = Product::where('slug', $product_slug)
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->firstOrFail();

        $seller = $product->user?->sellerProfile;
        $sellerName = $seller?->business_name ?? 'Seller';
        $currency = config('services.paystack.currency', 'GHS');
        $shortDescription = $product->description
            ? Str::limit($product->description, 120)
            : 'Beautiful product ready for payment.';

        $ogImage = $product->image_path
            ? url('storage/'.$product->image_path)
            : asset('images/og-default.png');

        return view('public.product', [
            'product' => $product,
            'seller' => $seller,
            'currency' => $currency,
            'smallDescription' => $shortDescription,
            'ogTitle' => "{$product->name} • {$sellerName}",
            'ogDescription' => "{$shortDescription} Price: {$currency} ".number_format((float) $product->price, 2, '.', ','),
            'ogImage' => $ogImage,
            'ogUrl' => route('public.product', $product->slug),
            'ogType' => 'website',
        ]);
    }
}
