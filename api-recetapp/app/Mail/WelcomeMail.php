<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $houseName;

    public function __construct($userName, $houseName)
    {
        $this->userName = $userName;
        $this->houseName = $houseName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Bienvenido a RecetAPP!');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: ['userName' => $this->userName, 'houseName' => $this->houseName],
        );
    }
}