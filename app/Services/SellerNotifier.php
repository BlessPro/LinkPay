<?php

namespace App\Services;

use App\Mail\SellerAlert;
use App\Models\SellerNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SellerNotifier
{
    public function notify(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        bool $sendEmail = true,
        bool $sendWhatsApp = true
    ): void
    {
        $user->loadMissing('sellerProfile');

        SellerNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $phone = $user->phone ?: $user->sellerProfile?->phone;

        // WhatsApp-first. If WhatsApp fails (common in sandbox/outside 24h window), fall back to SMS,
        // and finally to email. In-app notification is always stored above.
        $whatsAppSent = false;
        $smsSent = false;

        if ($sendWhatsApp && $phone) {
            try {
                app(TwilioMessagingService::class)->sendWhatsApp($phone, $title."\n".$body, [
                    'user_id' => $user->id,
                    'context_type' => 'seller_notification',
                    'context_id' => $type,
                ]);
                $whatsAppSent = true;

                Log::info('Seller WhatsApp notify sent', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'type' => $type,
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Seller WhatsApp notify failed', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        } elseif ($sendWhatsApp) {
            Log::warning('Seller WhatsApp notify skipped (no phone)', [
                'user_id' => $user->id,
                'type' => $type,
            ]);
        }

        if (! $whatsAppSent && $phone) {
            try {
                app(TwilioMessagingService::class)->sendSms($phone, $title.' - '.$body, [
                    'user_id' => $user->id,
                    'context_type' => 'seller_notification_fallback',
                    'context_id' => $type,
                ]);
                $smsSent = true;

                Log::info('Seller SMS notify sent (fallback)', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'type' => $type,
                ]);
            } catch (\Throwable $smsException) {
                Log::warning('Seller SMS notify failed (fallback)', [
                    'user_id' => $user->id,
                    'message' => $smsException->getMessage(),
                ]);
            }
        }

        if ($sendEmail && $user->email && ! $whatsAppSent && ! $smsSent) {
            Mail::to($user->email)->send(new SellerAlert($title, $body));
        }
    }
}
