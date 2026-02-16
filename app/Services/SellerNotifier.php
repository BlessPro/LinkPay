<?php

namespace App\Services;

use App\Models\SellerNotification;
use App\Models\User;

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
        SellerNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
