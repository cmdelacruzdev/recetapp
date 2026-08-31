<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $inviterName;
    public $houseName;
    public $activationUrl;
    public $tempPassword;
    public $email;

    public function __construct($inviterName, $houseName, $token, $tempPassword, $email)
    {
        $this->inviterName = $inviterName;
        $this->houseName = $houseName;
        $this->tempPassword = $tempPassword;
        $this->email = $email;
        // Apuntamos a la SPA para activar la cuenta con nombre y contraseña nueva
        $this->activationUrl = url("/api/activate/{$token}");
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->inviterName} te ha invitado a RecetAPP");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'inviterName' => $this->inviterName,
                'houseName' => $this->houseName,
                'activationUrl' => $this->activationUrl,
                'tempPassword' => $this->tempPassword,
                'email' => $this->email,
            ],
        );
    }
}