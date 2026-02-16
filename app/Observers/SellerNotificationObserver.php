<?php

namespace App\Observers;

use App\Models\SellerNotification;
use App\Services\HubtelSmsService;
use App\Services\TwilioMessagingService;
use Illuminate\Support\Facades\Log;

class SellerNotificationObserver
{
    public function created(SellerNotification $notification): void
    {
        $user = $notification->user()->with('sellerProfile')->first();
        if (! $user) {
            return;
        }

        $phone = $user->phone ?: $user->sellerProfile?->phone;
        if (! $phone) {
            Log::warning('Seller notification skipped (no phone)', [
                'user_id' => $user->id,
                'type' => $notification->type,
            ]);

            return;
        }

        $content = trim($notification->title."\n".$notification->body);
        $content .= "\nOrders: ".route('products.index').'#orders-by-customer';
        $orderId = data_get($notification->data, 'order_id');
        if (is_scalar($orderId) && (string) $orderId !== '') {
            $content .= "\nOrder link: ".route('products.index').'#order-'.(string) $orderId;
        }

        try {
            app(HubtelSmsService::class)->send($phone, $content, [
                'user_id' => $user->id,
                'context_type' => 'seller_notification',
                'context_id' => $notification->type,
            ]);

            Log::info('Seller SMS notify sent', [
                'user_id' => $user->id,
                'phone' => $phone,
                'type' => $notification->type,
                'notification_id' => $notification->id,
            ]);

            return;
        } catch (\Throwable $exception) {
            Log::warning('Seller SMS notify failed, trying WhatsApp', [
                'user_id' => $user->id,
                'type' => $notification->type,
                'notification_id' => $notification->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            app(TwilioMessagingService::class)->sendWhatsApp(
                $phone,
                $content,
                [
                    'user_id' => $user->id,
                    'context_type' => 'seller_notification',
                    'context_id' => $notification->type,
                ]
            );

            Log::info('Seller WhatsApp notify sent', [
                'user_id' => $user->id,
                'phone' => $phone,
                'type' => $notification->type,
                'notification_id' => $notification->id,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Seller WhatsApp notify failed', [
                'user_id' => $user->id,
                'type' => $notification->type,
                'notification_id' => $notification->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
