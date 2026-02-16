<?php

namespace App\Http\Controllers;

use App\Models\TwilioMessageLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    public function status(Request $request): Response
    {
        $sid = (string) $request->input('MessageSid', '');
        $status = (string) $request->input('MessageStatus', '');
        $errorCode = $request->input('ErrorCode');
        $errorMessage = $request->input('ErrorMessage');

        if ($sid === '' || $status === '') {
            return response('ok', 200);
        }

        $messageLog = TwilioMessageLog::query()
            ->where('provider_sid', $sid)
            ->latest()
            ->first();

        if (! $messageLog) {
            Log::warning('Twilio status callback for unknown message sid', [
                'sid' => $sid,
                'status' => $status,
                'to' => $request->input('To'),
            ]);

            return response('ok', 200);
        }

        $messageLog->status = $status;
        $messageLog->error_code = $errorCode !== null ? (string) $errorCode : $messageLog->error_code;
        $messageLog->error_message = $errorMessage ?: $messageLog->error_message;
        $messageLog->payload = array_merge($messageLog->payload ?? [], [
            'callback' => $request->all(),
        ]);
        $messageLog->save();

        return response('ok', 200);
    }
}

