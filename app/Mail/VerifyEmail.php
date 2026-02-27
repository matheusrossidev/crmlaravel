<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
    ) {
        $this->verifyUrl = route('verify.email', ['token' => $user->verification_token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirme seu email — Syncro',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify',
        );
    }
}
