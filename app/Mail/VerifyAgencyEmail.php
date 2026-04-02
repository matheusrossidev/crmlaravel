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

class VerifyAgencyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
    ) {
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
        $this->verifyUrl = route('verify.email', ['token' => $user->verification_token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.verify_agency.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-agency',
        );
    }
}
