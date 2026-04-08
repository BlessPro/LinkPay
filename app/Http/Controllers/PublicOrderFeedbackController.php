<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFeedback;
use App\Models\OrderFeedbackToken;
use App\Models\SellerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicOrderFeedbackController extends Controller
{
    public function show(string $token): View
    {
        $feedbackToken = $this->resolveToken($token);
        $order = $feedbackToken?->order;

        return view('public.order-feedback', [
            'token' => $feedbackToken,
            'order' => $order,
            'expired' => ! $feedbackToken || $this->isExpired($feedbackToken),
            'alreadyUsed' => (bool) $feedbackToken?->used_at,
        ]);
    }

    public function received(Request $request, string $token): RedirectResponse
    {
        $feedbackToken = $this->resolveUsableTokenOrFail($token);

        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $feedbackToken->order;
        OrderFeedback::create([
            'order_id' => $order->id,
            'order_feedback_token_id' => $feedbackToken->id,
            'type' => OrderFeedback::TYPE_RECEIVED,
            'rating' => $data['rating'] ?? null,
            'note' => $data['note'] ?? null,
            'admin_status' => OrderFeedback::ADMIN_IGNORED,
        ]);

        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        $feedbackToken->used_at = now();
        $feedbackToken->save();

        app(\App\Services\SellerNotifier::class)->notify(
            $order->user,
            SellerNotification::TYPE_ORDER_ACCEPTED,
            'Order completed by customer',
            'Customer confirmed delivery for order '.$order->reference.'.',
            ['order_id' => $order->id, 'rating' => $data['rating'] ?? null]
        );

        return redirect()
            ->route('public.order.feedback.show', $feedbackToken->token)
            ->with('status', 'feedback-received');
    }

    public function report(Request $request, string $token): RedirectResponse
    {
        $feedbackToken = $this->resolveUsableTokenOrFail($token);

        $data = $request->validate([
            'issue_note' => ['required', 'string', 'max:1500'],
            'issue_photo' => ['nullable', 'image', 'max:6144'],
        ]);

        $order = $feedbackToken->order;
        $photoPath = null;
        if ($request->hasFile('issue_photo')) {
            $photoPath = $request->file('issue_photo')->store('order-issues', 'public');
        }

        $feedback = OrderFeedback::create([
            'order_id' => $order->id,
            'order_feedback_token_id' => $feedbackToken->id,
            'type' => OrderFeedback::TYPE_REPORTED,
            'issue_note' => $data['issue_note'],
            'issue_photo_path' => $photoPath,
            'admin_status' => OrderFeedback::ADMIN_PENDING,
        ]);

        $order->status = Order::STATUS_DISPUTED_PENDING_ADMIN;
        $order->save();

        $feedbackToken->used_at = now();
        $feedbackToken->save();

        app(\App\Services\SellerNotifier::class)->notify(
            $order->user,
            SellerNotification::TYPE_ADMIN_MESSAGE,
            'Order issue reported',
            'Customer reported an issue for order '.$order->reference.'.',
            ['order_id' => $order->id, 'feedback_id' => $feedback->id]
        );

        return redirect()
            ->route('public.order.feedback.show', $feedbackToken->token)
            ->with('status', 'feedback-reported');
    }

    private function resolveToken(string $token): ?OrderFeedbackToken
    {
        return OrderFeedbackToken::query()
            ->with('order.user')
            ->where('token', $token)
            ->first();
    }

    private function isExpired(OrderFeedbackToken $token): bool
    {
        return ($token->expires_at && now()->gt($token->expires_at))
            || (bool) $token->used_at;
    }

    private function resolveUsableTokenOrFail(string $token): OrderFeedbackToken
    {
        $record = $this->resolveToken($token);
        abort_if(! $record, 404);
        abort_if($this->isExpired($record), 410);

        return $record;
    }
}
