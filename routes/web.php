<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\PublicListingController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\SellerProfileController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SellerPublicPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'plans' => config('plans'),
    ]);
});

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::get('/s/{public_slug}', [PublicListingController::class, 'show'])->name('public.listing');
Route::post('/s/{public_slug}/products/{product}/pay', [PublicListingController::class, 'pay'])
    ->name('public.products.pay');
Route::post('/s/{public_slug}/products/{product}/interest', [PublicListingController::class, 'interest'])
    ->name('public.products.interest');

Route::get('/pay/success', [PublicInvoiceController::class, 'success'])->name('pay.success');
Route::get('/pay/{token}', [PublicInvoiceController::class, 'show'])->name('public.invoice');
Route::post('/pay/{token}', [PublicInvoiceController::class, 'pay'])->name('public.invoice.pay');
Route::get('/p/{product_slug}', [PublicProductController::class, 'show'])->name('public.product');

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

Route::middleware('auth')->group(function () {
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::post('/billing/activate/{plan}', [BillingController::class, 'activate'])->name('billing.activate');
});

Route::middleware(['auth', 'active_access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/seller', [SellerProfileController::class, 'update'])->name('profile.seller.update');
    Route::post('/profile/seller/test-connection', [SellerProfileController::class, 'testConnection'])->name('profile.seller.test');

    Route::middleware('promotion_access')->group(function () {
        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::post('/products/export-pdf', [ProductController::class, 'exportPdf'])->name('products.exportPdf');
        Route::get('/public-preview', [SellerPublicPreviewController::class, 'show'])->name('public.preview');
    });

    Route::middleware('payments_plan')->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
});

require __DIR__.'/auth.php';
