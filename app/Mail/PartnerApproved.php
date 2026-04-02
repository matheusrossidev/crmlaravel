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
        $locale = $user->tenant?->locale ?? $tenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
        $this->loginUrl = route('login');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.partner_approved.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.partner-approved',
        );
    }
}
