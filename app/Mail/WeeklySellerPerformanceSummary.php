<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklySellerPerformanceSummary extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $summary
    ) {
    }

    public function build(): self
    {
        return $this->subject('Your weekly performance summary')
            ->view('emails.seller-weekly-summary', [
                'summary' => $this->summary,
            ]);
    }
}
