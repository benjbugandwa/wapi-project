<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $testMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Wapi] Test de notification Email (Resend)',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family: sans-serif; padding: 20px; line-height: 1.5; color: #111827;">'
                .'<h2 style="color: #4f46e5;">Wapi - Test de notification</h2>'
                .'<p>'.e($this->testMessage).'</p>'
                .'<hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">'
                .'<small style="color: #6b7280;">Envoyé via le transport Resend configuré sur Wapi.</small>'
                .'</div>',
        );
    }
}
