<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Request;

class SellerPublicPreviewController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        if (! $profile) {
            return redirect()->route('profile.edit')
                ->withErrors(['profile' => 'Complete your seller profile to preview your public page.']);
        }

        $template = $request->query('template', 'products');
        if (! in_array($template, ['products', 'services'], true)) {
            $template = 'products';
        }

        $products = $user->products()
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->latest()
            ->get();

        return view('dashboard.public-preview', [
            'profile' => $profile,
            'products' => $products,
            'currency' => config('services.paystack.currency', 'GHS'),
            'template' => $template,
        ]);
    }
}
