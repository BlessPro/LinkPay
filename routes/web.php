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
use App\Http\Controllers\PublicOrderTrackingController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\SellerProfileController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\GoalsTargetController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TwilioWebhookController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SellerPublicPreviewController;
use App\Http\Controllers\Admin\AdminOtpAuthController;
use App\Http\Controllers\Admin\AdminOrderFeedbackController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PublicOrderFeedbackController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index']);
Route::get('/version.json', function () {
    $manifestPath = public_path('build/manifest.json');
    $manifestHash = File::exists($manifestPath)
        ? md5_file($manifestPath)
        : null;

    return response()->json([
        'version' => config('app.version')
            ?? env('APP_VERSION')
            ?? $manifestHash
            ?? app()->version(),
        'generated_at' => now()->toIso8601String(),
    ]);
})->name('app.version');
Route::get('/sellers', [LandingController::class, 'sellers'])->name('marketplace.sellers');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::get('/s/{public_slug}', [PublicListingController::class, 'show'])->name('public.listing');
Route::post('/s/{public_slug}/products/{product}/pay', [PublicListingController::class, 'pay'])
    ->middleware('throttle:public-pay')
    ->name('public.products.pay');
Route::post('/s/{public_slug}/products/{product}/interest', [PublicListingController::class, 'interest'])
    ->middleware('throttle:20,1')
    ->name('public.products.interest');
Route::post('/s/{public_slug}/products/{product}/cart', [PublicListingController::class, 'addToCart'])
    ->middleware('throttle:60,1')
    ->name('public.products.cart.add');
Route::patch('/s/{public_slug}/cart', [PublicListingController::class, 'updateCart'])
    ->middleware('throttle:40,1')
    ->name('public.cart.update');
Route::delete('/s/{public_slug}/products/{product}/cart', [PublicListingController::class, 'removeFromCart'])
    ->middleware('throttle:40,1')
    ->name('public.products.cart.remove');
Route::post('/s/{public_slug}/cart/checkout', [PublicListingController::class, 'checkoutCart'])
    ->middleware('throttle:public-checkout')
    ->name('public.cart.checkout');
Route::get('/orders/track', [PublicOrderTrackingController::class, 'show'])->name('public.orders.track');
Route::get('/order-feedback/{token}', [PublicOrderFeedbackController::class, 'show'])->name('public.order.feedback.show');
Route::post('/order-feedback/{token}/received', [PublicOrderFeedbackController::class, 'received'])
    ->middleware('throttle:8,1')
    ->name('public.order.feedback.received');
Route::post('/order-feedback/{token}/report', [PublicOrderFeedbackController::class, 'report'])
    ->middleware('throttle:8,1')
    ->name('public.order.feedback.report');

Route::get('/pay/success', [PublicInvoiceController::class, 'success'])->name('pay.success');
Route::get('/pay/{token}', [PublicInvoiceController::class, 'show'])->name('public.invoice');
Route::post('/pay/{token}', [PublicInvoiceController::class, 'pay'])
    ->middleware('throttle:public-pay')
    ->name('public.invoice.pay');
Route::get('/p/{product_slug}', [PublicProductController::class, 'show'])->name('public.product');

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');
Route::post('/webhooks/twilio/status', [TwilioWebhookController::class, 'status'])->name('webhooks.twilio.status');
Route::post('/telemetry/client', TelemetryController::class)
    ->middleware('throttle:120,1')
    ->name('telemetry.client');

Route::middleware('auth')->group(function () {
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::post('/billing/activate/{plan}', [BillingController::class, 'activate'])->name('billing.activate');
});

Route::middleware(['auth', 'active_access'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/state', [OnboardingController::class, 'updateState'])
        ->middleware('throttle:60,1')
        ->name('onboarding.state');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/privacy/data-deletion-request', [LegalController::class, 'storeDataDeletionRequest'])
        ->middleware('throttle:3,10')
        ->name('legal.data-deletion.store');
    Route::put('/profile/seller', [SellerProfileController::class, 'update'])->name('profile.seller.update');
    Route::post('/profile/seller/test-connection', [SellerProfileController::class, 'testConnection'])->name('profile.seller.test');

    Route::middleware('profile_onboarding_complete')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('promotion_access')->group(function () {
            Route::get('/products/orders', [ProductController::class, 'orders'])->name('products.orders');
            Route::resource('products', ProductController::class)->except(['show']);
            Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
            Route::post('/products/export-pdf', [ProductController::class, 'exportPdf'])->name('products.exportPdf');
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
            Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
            Route::patch('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
            Route::get('/public-preview', [SellerPublicPreviewController::class, 'show'])->name('public.preview');
        });

        Route::middleware('payments_plan')->group(function () {
            Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
            Route::post('/payments/{payment}/refund-request', [PaymentController::class, 'requestRefund'])
                ->middleware('throttle:20,1')
                ->name('payments.refund.request');
        });

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/orders/{order}/accept', [NotificationController::class, 'acceptOrder'])->name('notifications.orders.accept');
        Route::post('/notifications/orders/{order}/reject', [NotificationController::class, 'rejectOrder'])->name('notifications.orders.reject');
        Route::get('/goals-target', [GoalsTargetController::class, 'index'])->name('goals.index');
        Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminOtpAuthController::class, 'show'])->name('login');
    Route::post('/login/send', [AdminOtpAuthController::class, 'send'])
        ->middleware('throttle:5,1')
        ->name('login.send');
    Route::post('/login/verify', [AdminOtpAuthController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('login.verify');
});

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/payments/reconciliation', [AdminDashboardController::class, 'reconciliation'])->name('payments.reconciliation');
    Route::get('/payments/reconciliation/export', [AdminDashboardController::class, 'reconciliationExport'])->name('payments.reconciliation.export');
    Route::post('/payments/reconciliation/bulk-retry', [AdminDashboardController::class, 'bulkRetryReconciliation'])
        ->middleware('throttle:20,1')
        ->name('payments.reconciliation.bulk-retry');
    Route::post('/payments/reconciliation/bulk-mark-failed', [AdminDashboardController::class, 'bulkMarkFailedReconciliation'])
        ->middleware('throttle:20,1')
        ->name('payments.reconciliation.bulk-mark-failed');
    Route::get('/jobs/failed', [AdminDashboardController::class, 'failedJobs'])->name('jobs.failed');
    Route::post('/jobs/failed/retry-all', [AdminDashboardController::class, 'retryAllFailedJobs'])
        ->middleware('throttle:10,1')
        ->name('jobs.failed.retry-all');
    Route::post('/jobs/failed/{id}/retry', [AdminDashboardController::class, 'retryFailedJob'])
        ->middleware('throttle:30,1')
        ->name('jobs.failed.retry');
    Route::delete('/jobs/failed/{id}', [AdminDashboardController::class, 'forgetFailedJob'])
        ->middleware('throttle:30,1')
        ->name('jobs.failed.forget');
    Route::get('/sellers/{seller}', [AdminDashboardController::class, 'seller'])->name('sellers.show');
    Route::post('/sellers/{seller}/sync-paystack', [AdminDashboardController::class, 'syncSellerPaystack'])
        ->middleware('throttle:20,1')
        ->name('sellers.sync-paystack');
    Route::post('/sellers/{seller}/suspend', [AdminDashboardController::class, 'suspendSeller'])
        ->middleware('throttle:20,1')
        ->name('sellers.suspend');
    Route::post('/sellers/{seller}/unsuspend', [AdminDashboardController::class, 'unsuspendSeller'])
        ->middleware('throttle:20,1')
        ->name('sellers.unsuspend');
    Route::post('/sellers/{seller}/notify', [AdminDashboardController::class, 'notifySeller'])
        ->middleware('throttle:30,1')
        ->name('sellers.notify');
    Route::post('/payments/{payment}/retry', [AdminDashboardController::class, 'retryPayment'])
        ->middleware('throttle:30,1')
        ->name('payments.retry');
    Route::post('/payments/{payment}/confirm', [AdminDashboardController::class, 'confirmPayment'])
        ->middleware('throttle:30,1')
        ->name('payments.confirm');
    Route::post('/payments/{payment}/mark-failed', [AdminDashboardController::class, 'markPaymentFailed'])
        ->middleware('throttle:30,1')
        ->name('payments.mark-failed');
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/order-feedback', [AdminOrderFeedbackController::class, 'index'])->name('order-feedback.index');
    Route::post('/order-feedback/{feedback}/refund', [AdminOrderFeedbackController::class, 'approveRefund'])
        ->middleware('throttle:20,1')
        ->name('order-feedback.refund');
    Route::post('/order-feedback/{feedback}/ignore', [AdminOrderFeedbackController::class, 'ignore'])
        ->middleware('throttle:20,1')
        ->name('order-feedback.ignore');
});

require __DIR__.'/auth.php';
