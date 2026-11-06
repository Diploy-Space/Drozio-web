<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: translate('Reset your password'));
    }

    public function content(): Content
    {
        return new Content(view: 'email-templates.admin-password-reset', with: ['url' => $this->url]);
    }
}
