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
use App\Http\Controllers\SellerProfileController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/s/{public_slug}', [PublicListingController::class, 'show'])->name('public.listing');
Route::post('/s/{public_slug}/products/{product}/pay', [PublicListingController::class, 'pay'])
    ->name('public.products.pay');

Route::get('/pay/success', [PublicInvoiceController::class, 'success'])->name('pay.success');
Route::get('/pay/{token}', [PublicInvoiceController::class, 'show'])->name('public.invoice');
Route::post('/pay/{token}', [PublicInvoiceController::class, 'pay'])->name('public.invoice.pay');

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/seller', [SellerProfileController::class, 'update'])->name('profile.seller.update');
    Route::post('/profile/seller/test-connection', [SellerProfileController::class, 'testConnection'])->name('profile.seller.test');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
