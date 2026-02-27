<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly ?PlanDefinition $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assinatura cancelada — sentiremos sua falta',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-cancelled',
        );
    }
}
