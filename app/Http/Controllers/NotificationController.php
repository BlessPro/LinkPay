<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SellerNotification;
use App\Services\HubtelSmsService;
use App\Services\OrderFeedbackService;
use App\Services\SellerNotifier;
use App\Services\TwilioMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->sellerNotifications()->latest()->paginate(10);
        $pendingOrders = $request->user()->orders()
            ->with(['items', 'payments'])
            ->where('status', Order::STATUS_PAID)
            ->latest()
            ->take(20)
            ->get();

        return view('dashboard.notifications.index', [
            'notifications' => $notifications,
            'pendingOrders' => $pendingOrders,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function acceptOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->status !== Order::STATUS_PAID) {
            return back()->withErrors(['order' => 'Order cannot be accepted in its current status.']);
        }

        $order->status = Order::STATUS_ACCEPTED;
        $order->save();

        app(SellerNotifier::class)->notify(
            $request->user(),
            SellerNotification::TYPE_ORDER_ACCEPTED,
            'Order accepted',
            'You accepted order '.$order->reference.'.',
            ['order_id' => $order->id]
        );

        $this->notifyCustomer($order, 'accepted');

        return back()->with('status', 'order-updated');
    }

    public function rejectOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->status !== Order::STATUS_PAID) {
            return back()->withErrors(['order' => 'Order cannot be rejected in its current status.']);
        }

        $order->status = Order::STATUS_CANNOT_FULFILL;
        $order->save();

        app(SellerNotifier::class)->notify(
            $request->user(),
            SellerNotification::TYPE_ORDER_REJECTED,
            'Order cannot be fulfilled',
            'You marked order '.$order->reference.' as cannot fulfill.',
            ['order_id' => $order->id]
        );

        $this->notifyCustomer($order, 'rejected');

        return back()->with('status', 'order-updated');
    }

    private function notifyCustomer(Order $order, string $action): void
    {
        $phone = $order->customer_phone;
        if (! $phone) {
            return;
        }

        if ($action === 'accepted') {
            $token = app(OrderFeedbackService::class)->createOneTimeToken($order, $phone);
            $link = app(OrderFeedbackService::class)->feedbackUrl($token);
            $message = 'Your order '.$order->reference.' has been accepted and will be shipped soon. Confirm delivery here: '.$link;
        } else {
            $message = 'Your order '.$order->reference.' could not be fulfilled. The seller will contact you with options.';
        }

        try {
            app(HubtelSmsService::class)->send($phone, $message, [
                'user_id' => $order->user_id,
                'context_type' => 'order_status',
                'context_id' => $order->id,
            ]);
            return;
        } catch (\Throwable $exception) {
            Log::warning('Order customer SMS notify failed, trying WhatsApp', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            app(TwilioMessagingService::class)->sendWhatsApp($phone, $message, [
                'user_id' => $order->user_id,
                'context_type' => 'order_status',
                'context_id' => $order->id,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Order customer WhatsApp notify failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
