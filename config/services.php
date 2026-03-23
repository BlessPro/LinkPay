<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'currency' => env('PAYSTACK_CURRENCY', 'GHS'),
        'platform_fee_flat' => env('PLATFORM_FEE_FLAT', '0'),
        'subaccount_percent_charge' => env('PAYSTACK_SUBACCOUNT_PERCENT_CHARGE', '0'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'status_callback_url' => env('TWILIO_STATUS_CALLBACK_URL'),
        // Verify default channel for OTP (sms or whatsapp).
        'verify_default_channel' => env('TWILIO_VERIFY_DEFAULT_CHANNEL', 'sms'),
        // Optional (recommended): allow SMS fallback for OTP/notifications.
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        // Fallback channel used when Verify is missing/unavailable (sms or whatsapp).
        'otp_fallback_channel' => env('TWILIO_OTP_FALLBACK_CHANNEL', 'sms'),
        'default_country' => env('TWILIO_DEFAULT_COUNTRY', '+233'),
    ],

    'hubtel' => [
        'client_id' => env('HUBTEL_CLIENT_ID'),
        'client_secret' => env('HUBTEL_CLIENT_SECRET'),
        'sender_id' => env('HUBTEL_SENDER_ID'),
        'base_url' => env('HUBTEL_BASE_URL', 'https://smsc.hubtel.com/v1/messages/send'),
    ],

];
