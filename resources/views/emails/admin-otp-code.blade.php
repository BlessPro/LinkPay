<x-mail::message>
# Admin Login Code

Use this OTP to sign in to your 8Kommerce admin account:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

This code expires in {{ config('admin.otp_ttl_minutes', 10) }} minutes.

If you did not request this code, you can ignore this email.

Thanks,<br>
{{ config('app.name', '8Kommerce') }}
</x-mail::message>

