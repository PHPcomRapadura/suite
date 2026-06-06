<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CfpResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public function __construct(public User $user, string $token)
    {
        $this->resetUrl = url('/cfp/reset-password')
            .'?token='.urlencode($token)
            .'&email='.urlencode($user->email);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Redefinição de senha — PHP com Rapadura CFP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cfp-reset-password',
        );
    }
}
