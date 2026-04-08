<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerNotification;
use App\Support\Phone;
use Illuminate\Http\Request;

class PublicOrderTrackingController extends Controller
{
    public function show(Request $request)
    {
        $reference = trim((string) $request->query('reference', ''));
        $phoneInput = trim((string) $request->query('phone_number', ''));
        $lookupError = null;
        $order = null;
        $timeline = collect();

        if ($reference !== '' || $phoneInput !== '') {
            if ($reference === '' || $phoneInput === '') {
                $lookupError = 'Enter both order reference and phone number to track an order.';
            } else {
                $phone = Phone::normalize($phoneInput, '+233');
                if (! $phone) {
                    $lookupError = 'Enter a valid Ghana phone number (example: 0541900229).';
                } else {
                    $order = Order::query()
                        ->with(['payments' => function ($query) {
                            $query->orderBy('created_at');
                        }])
                        ->where('reference', $reference)
                        ->where('customer_phone', $phone)
                        ->first();

                    if (! $order) {
                        $lookupError = 'Order not found. Check the reference and phone number, then try again.';
                    } else {
                        $timeline = $this->buildTimeline($order);
                    }
                }
            }
        }

        return view('public.order-tracking', [
            'reference' => $reference,
            'phoneInput' => $phoneInput,
            'lookupError' => $lookupError,
            'order' => $order,
            'timeline' => $timeline,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    private function buildTimeline(Order $order)
    {
        $timeline = collect([
            [
                'title' => 'Order created',
                'body' => 'Order was placed and reference was generated.',
                'time' => $order->created_at,
                'state' => 'done',
            ],
        ]);

        $successPayment = $order->payments->firstWhere('status', Payment::STATUS_SUCCESS);
        if ($successPayment || in_array($order->status, [
            Order::STATUS_PAID,
            Order::STATUS_ACCEPTED,
            Order::STATUS_CANNOT_FULFILL,
            Order::STATUS_COMPLETED,
            Order::STATUS_DISPUTED_PENDING_ADMIN,
            Order::STATUS_REFUNDED,
            Order::STATUS_COMPLAINT_REJECTED,
        ], true)) {
            $timeline->push([
                'title' => 'Payment confirmed',
                'body' => 'Payment was received successfully.',
                'time' => $successPayment?->paid_at ?? $order->paid_at,
                'state' => 'done',
            ]);
        }

        $decisionNotification = SellerNotification::query()
            ->where('user_id', $order->user_id)
            ->whereIn('type', [SellerNotification::TYPE_ORDER_ACCEPTED, SellerNotification::TYPE_ORDER_REJECTED])
            ->whereRaw("data->>'order_id' = ?", [(string) $order->id])
            ->latest()
            ->first();

        if ($order->status === Order::STATUS_ACCEPTED) {
            $timeline->push([
                'title' => 'Order accepted',
                'body' => 'Seller confirmed they can fulfill this order.',
                'time' => $decisionNotification?->created_at ?? $order->updated_at,
                'state' => 'done',
            ]);
        } elseif ($order->status === Order::STATUS_COMPLETED) {
            $timeline->push([
                'title' => 'Delivered confirmed',
                'body' => 'Customer confirmed delivery and completed this order.',
                'time' => $order->updated_at,
                'state' => 'done',
            ]);
        } elseif ($order->status === Order::STATUS_DISPUTED_PENDING_ADMIN) {
            $timeline->push([
                'title' => 'Issue under review',
                'body' => 'Customer reported an issue. Admin review is in progress.',
                'time' => $order->updated_at,
                'state' => 'pending',
            ]);
        } elseif ($order->status === Order::STATUS_REFUNDED) {
            $timeline->push([
                'title' => 'Refund approved',
                'body' => 'Admin approved complaint and initiated refund.',
                'time' => $order->updated_at,
                'state' => 'done',
            ]);
        } elseif ($order->status === Order::STATUS_COMPLAINT_REJECTED) {
            $timeline->push([
                'title' => 'Complaint rejected',
                'body' => 'Admin reviewed and rejected this complaint.',
                'time' => $order->updated_at,
                'state' => 'failed',
            ]);
        } elseif ($order->status === Order::STATUS_CANNOT_FULFILL) {
            $timeline->push([
                'title' => 'Cannot fulfill',
                'body' => 'Seller marked this order as unavailable to fulfill.',
                'time' => $decisionNotification?->created_at ?? $order->updated_at,
                'state' => 'failed',
            ]);
        } elseif ($order->status === Order::STATUS_PAID) {
            $timeline->push([
                'title' => 'Waiting for seller update',
                'body' => 'Payment is complete. Seller confirmation is pending.',
                'time' => null,
                'state' => 'pending',
            ]);
        } else {
            $timeline->push([
                'title' => 'Waiting for payment',
                'body' => 'Complete payment to move this order forward.',
                'time' => null,
                'state' => 'pending',
            ]);
        }

        return $timeline;
    }
}
