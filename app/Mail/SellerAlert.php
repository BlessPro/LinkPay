<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $title, public string $body)
    {
    }

    public function build()
    {
        return $this->subject($this->title)
            ->view('emails.seller-alert');
    }
}
