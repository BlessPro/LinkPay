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

        if ($sendEmail && $user->email) {
            Mail::to($user->email)->send(new SellerAlert($title, $body));
        }

        $phone = $user->phone ?: $user->sellerProfile?->phone;
        if ($sendWhatsApp && $phone) {
            try {
                app(TwilioMessagingService::class)->sendWhatsApp($phone, $title."\n".$body);
                Log::info('Seller WhatsApp notify sent', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'type' => $type,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Seller WhatsApp notify failed', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);

                // WhatsApp freeform often fails outside the 24h window (e.g. 63016). Try SMS as a fallback.
                try {
                    app(TwilioMessagingService::class)->sendSms($phone, $title.' - '.$body);
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
        } elseif ($sendWhatsApp) {
            Log::warning('Seller WhatsApp notify skipped (no phone)', [
                'user_id' => $user->id,
                'type' => $type,
            ]);
        }
    }
}
