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
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo cliente cadastrado com seu código de parceiro!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agency-referral-notification',
        );
    }
}
