<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFeedbackToken;
use Illuminate\Support\Str;

class OrderFeedbackService
{
    public function createOneTimeToken(Order $order, ?string $phone = null): OrderFeedbackToken
    {
        return OrderFeedbackToken::create([
            'order_id' => $order->id,
            'token' => Str::random(48),
            'phone' => $phone ?: $order->customer_phone,
            'expires_at' => now()->addDays(7),
            'meta' => [
                'reference' => $order->reference,
            ],
        ]);
    }

    public function feedbackUrl(OrderFeedbackToken $token): string
    {
        return route('public.order.feedback.show', $token->token);
    }
}

