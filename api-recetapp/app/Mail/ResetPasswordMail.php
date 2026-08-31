<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;

    public function __construct(string $token)
    {
        $frontendUrl = config('recetapp.frontend_url', 'http://localhost:4200');
        $this->resetUrl = "{$frontendUrl}/reset-password?token={$token}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Restablece tu contraseña en RecetAPP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: ['resetUrl' => $this->resetUrl],
        );
    }
}
