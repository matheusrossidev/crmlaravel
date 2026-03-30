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

class PartnerApproved extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly string $code,
    ) {
        $this->loginUrl = route('login');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu cadastro de parceiro foi aprovado! 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-approved',
        );
    }
}
