<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFeedback;
use App\Models\Payment;
use App\Services\HubtelSmsService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderFeedbackController extends Controller
{
    public function index(): View
    {
        $feedbacks = OrderFeedback::query()
            ->with(['order.user.sellerProfile', 'reviewedByAdmin'])
            ->where('type', OrderFeedback::TYPE_REPORTED)
            ->latest()
            ->paginate(20);

        return view('admin.order-feedback.index', [
            'feedbacks' => $feedbacks,
            'refundPercent' => (float) config('orders.dispute_refund_percent', 0.90),
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function approveRefund(Request $request, OrderFeedback $feedback, PaystackService $paystack): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($feedback->type !== OrderFeedback::TYPE_REPORTED) {
            return back()->withErrors(['feedback' => 'Only reported issues can be refunded.']);
        }

        $order = $feedback->order;
        if (! $order) {
            return back()->withErrors(['feedback' => 'Order not found for this feedback.']);
        }

        $payment = $order->payments()->where('status', Payment::STATUS_SUCCESS)->latest()->first();
        if (! $payment) {
            return back()->withErrors(['feedback' => 'No successful payment found for this order.']);
        }

        $refundPercent = (float) config('orders.dispute_refund_percent', 0.90);
        $refundAmount = round(((float) $payment->amount) * $refundPercent, 2);
        $refundData = null;

        try {
            $refundData = $paystack->createRefund(
                $payment->reference,
                (string) number_format($refundAmount, 2, '.', '')
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['feedback' => 'Refund failed: '.$exception->getMessage()]);
        }

        $feedback->admin_status = OrderFeedback::ADMIN_REFUND_APPROVED;
        $feedback->admin_note = $validated['admin_note'] ?? null;
        $feedback->reviewed_by_admin_id = $request->user()->id;
        $feedback->reviewed_at = now();
        $feedback->save();

        $order->status = Order::STATUS_REFUNDED;
        $order->save();

        $payload = $payment->raw_payload ?? [];
        $payload['refund'] = [
            'approved' => true,
            'amount' => (string) number_format($refundAmount, 2, '.', ''),
            'percent' => $refundPercent,
            'response' => $refundData,
            'admin_id' => $request->user()->id,
            'admin_note' => $validated['admin_note'] ?? null,
            'at' => now()->toIso8601String(),
        ];
        $payment->raw_payload = $payload;
        $payment->status = Payment::STATUS_FAILED;
        $payment->save();

        $this->sms(
            $order->customer_phone,
            'Your complaint for order '.$order->reference.' was approved. Refund of '.number_format($refundPercent * 100, 0).'% has been initiated.'
        );

        return back()->with('status', 'order-feedback-refund-approved');
    }

    public function ignore(Request $request, OrderFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        if ($feedback->type !== OrderFeedback::TYPE_REPORTED) {
            return back()->withErrors(['feedback' => 'Only reported issues can be reviewed.']);
        }

        $order = $feedback->order;
        if (! $order) {
            return back()->withErrors(['feedback' => 'Order not found for this feedback.']);
        }

        $feedback->admin_status = OrderFeedback::ADMIN_IGNORED;
        $feedback->admin_note = $validated['admin_note'];
        $feedback->reviewed_by_admin_id = $request->user()->id;
        $feedback->reviewed_at = now();
        $feedback->save();

        $order->status = Order::STATUS_COMPLAINT_REJECTED;
        $order->save();

        $this->sms(
            $order->customer_phone,
            'Your complaint for order '.$order->reference.' was reviewed and marked invalid.'
            .($this->supportPhone() ? ' For escalation call '.$this->supportPhone().'.' : '')
        );

        return back()->with('status', 'order-feedback-ignored');
    }

    private function sms(?string $phone, string $message): void
    {
        if (! $phone) {
            return;
        }

        try {
            app(HubtelSmsService::class)->send($phone, $message, [
                'context_type' => 'order_feedback_admin_decision',
            ]);
        } catch (\Throwable $exception) {
            // Best effort
        }
    }

    private function supportPhone(): string
    {
        return (string) config('orders.support_phone', '');
    }
}
