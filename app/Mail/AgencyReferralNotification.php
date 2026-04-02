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

class AgencyReferralNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $agencyAdminUser,
        public readonly Tenant $agencyTenant,
        public readonly Tenant $newClientTenant,
        public readonly int $totalClients,
    ) {
        $locale = $agencyAdminUser->tenant?->locale ?? $agencyTenant->locale ?? config('app.locale', 'pt_BR');
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('email.agency_referral.subject', ['client' => $this->newClientTenant->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agency-referral-notification',
        );
    }
}
